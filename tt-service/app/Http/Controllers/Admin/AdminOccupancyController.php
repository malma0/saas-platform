<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Attendance\Actions\MarkAttendance;
use App\Domain\Booking\Actions\CancelBooking;
use App\Domain\Booking\Actions\ConfirmBooking;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Actions\RescheduleBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Identity\Services\AccessControlService;
use App\Domain\Payments\Actions\CreatePayment;
use App\Domain\Payments\Actions\MarkPaymentPaid;
use App\Domain\Services\Models\ServiceOffering;
use App\Domain\Services\Services\PricingService;
use App\Http\Controllers\Controller;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\BookingStatus;
use App\Support\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Сетка занятости столов для админ-панели (MoonShine).
 *
 * Тот же вид «столы × время», что и на сайте, но для администратора: видит
 * занятость на выбранную дату/филиал и бронирует кликом по свободной ячейке.
 * Бронь создаётся тем же доменным движком (CreateBooking) — попадает в общую
 * БД и видна и на сайте, и в разделе «Бронирования».
 */
class AdminOccupancyController extends Controller
{
    public function __construct(
        private readonly CreateBooking $createBooking,
        private readonly PricingService $pricing,
        private readonly AccessControlService $accessControl,
    ) {}

    /**
     * Данные сетки: столы филиала + брони на выбранную дату.
     */
    public function grid(Request $request): JsonResponse
    {
        $allowed = $this->accessControl->accessibleBranchIds($request->user());

        // Доступные администратору филиалы
        $branches = Branch::query()
            ->where('is_active', true)
            ->when($allowed !== null, fn ($q) => $q->whereIn('id', $allowed))
            ->orderBy('name')
            ->get(['id', 'name', 'club_id', 'timezone']);

        if ($branches->isEmpty()) {
            return response()->json(['ok' => true, 'branches' => [], 'tables' => [], 'events' => []]);
        }

        $branchId = $request->integer('branch_id') ?: $branches->first()->id;
        $branch   = $branches->firstWhere('id', $branchId) ?? $branches->first();
        $tz       = $branch->timezone ?: 'Europe/Moscow';

        $date     = $this->resolveDate($request->query('date'), $tz);
        $dayStart = $date->copy()->startOfDay()->utc();
        $dayEnd   = $date->copy()->endOfDay()->utc();

        // Столы филиала (тип «table»), по порядку
        $tables = Resource::query()
            ->where('branch_id', $branch->id)
            ->where('is_active', true)
            ->whereHas('resourceType', fn ($q) => $q->where('slug', 'table'))
            ->orderBy('id')
            ->get(['id', 'name']);

        $tableIds = $tables->pluck('id')->all();

        // Брони филиала на день (кроме отменённых)
        $events = [];
        $bookings = Booking::query()
            ->where('branch_id', $branch->id)
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->where('start_at', '<', $dayEnd)
            ->where('end_at', '>', $dayStart)
            ->with(['bookingResources', 'client', 'paidPayment'])
            ->get();

        foreach ($bookings as $b) {
            $client = $b->client?->fullName() ?: 'Бронь';
            $start  = $b->start_at->copy()->timezone($tz)->format('H:i');
            $end    = $b->end_at->copy()->timezone($tz)->format('H:i');

            foreach ($b->bookingResources as $br) {
                if (! in_array($br->resource_id, $tableIds, true)) {
                    continue;
                }
                $events[] = [
                    'resource_id'  => $br->resource_id,
                    'title'        => $client,
                    'phone'        => $b->client?->phone,
                    'start'        => $start,
                    'end'          => $end,
                    'status'       => $b->status->value,
                    'status_label' => $b->status->label(),
                    'public_id'    => $b->public_id,
                    'amount'       => (int) round($b->amount_minor / 100),
                    'is_paid'      => $b->paidPayment !== null,
                ];
            }
        }

        // Часы работы и услуга «аренда стола» (для формы брони)
        $wh      = $branch->getWorkingHourForDay((int) $date->dayOfWeek);
        $open    = ($wh && ! $wh->is_closed) ? substr($wh->open_time, 0, 5) : '09:00';
        $close   = ($wh && ! $wh->is_closed) ? substr($wh->close_time, 0, 5) : '22:00';
        $service = $this->tableRentalService($branch->club_id);
        $rate    = $this->hourlyRate($service, $date, $tz);

        return response()->json([
            'ok'            => true,
            'branches'      => $branches->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values(),
            'branch_id'     => $branch->id,
            'date'          => $date->toDateString(),
            'open'          => $open,
            'close'         => $close,
            'closed'        => $wh ? (bool) $wh->is_closed : false,
            'tables'        => $tables->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'events'        => $events,
            'service_id'    => $service?->id,
            'rate_per_hour' => $rate,
        ]);
    }

    /**
     * Бронирование стола администратором (на клиента по имени + телефону).
     */
    public function book(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|min:2|max:120',
            'phone'       => 'required|string|min:5|max:30',
            'branch_id'   => 'required|integer|exists:branches,id',
            'resource_id' => 'required|integer|exists:resources,id',
            'date'        => 'required|date_format:Y-m-d',
            'start'       => 'required|date_format:H:i',
            'end'         => 'required|date_format:H:i',
        ]);

        // Проверка прав на филиал
        $allowed = $this->accessControl->accessibleBranchIds($request->user());
        if ($allowed !== null && ! in_array((int) $data['branch_id'], $allowed, true)) {
            return response()->json(['ok' => false, 'message' => 'Нет доступа к этому филиалу.'], 403);
        }

        $branch  = Branch::findOrFail($data['branch_id']);
        $service = $this->tableRentalService($branch->club_id);
        if (! $service) {
            return response()->json(['ok' => false, 'message' => 'Для клуба не настроена услуга аренды стола.'], 422);
        }
        $tz = $branch->timezone ?: 'Europe/Moscow';

        $startLocal = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['start']}", $tz);
        $endLocal   = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['end']}", $tz);

        if ($endLocal->lessThanOrEqualTo($startLocal)) {
            return response()->json(['ok' => false, 'message' => 'Время окончания должно быть позже начала.'], 422);
        }
        if ($endLocal->isPast()) {
            return response()->json(['ok' => false, 'message' => 'Нельзя забронировать прошедшее время.'], 422);
        }

        // Рабочие часы
        $wh = $branch->getWorkingHourForDay((int) $startLocal->dayOfWeek);
        if (! $wh || $wh->is_closed) {
            return response()->json(['ok' => false, 'message' => 'В этот день клуб не работает.'], 422);
        }
        $openAt  = $startLocal->copy()->setTimeFromTimeString($wh->open_time);
        $closeAt = $startLocal->copy()->setTimeFromTimeString($wh->close_time);
        if ($startLocal->lt($openAt) || $endLocal->gt($closeAt)) {
            return response()->json([
                'ok'      => false,
                'message' => "Время вне рабочих часов ({$openAt->format('H:i')}–{$closeAt->format('H:i')}).",
            ], 422);
        }

        // Цена = ставка за час × длительность
        try {
            $hourly = $this->pricing->priceFor($service, $startLocal);
        } catch (\RuntimeException) {
            return response()->json(['ok' => false, 'message' => 'Для услуги не настроена цена.'], 422);
        }
        $hours       = $startLocal->diffInMinutes($endLocal) / 60;
        $amountMinor = (int) round($hourly->amountMinor * $hours);

        $client = $this->findOrCreateClient($branch->club_id, $data['name'], $data['phone']);

        $dto = new CreateBookingDTO(
            clubId:            $branch->club_id,
            branchId:          $branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       [(int) $data['resource_id']],
            startAt:           $startLocal->copy()->utc(),
            endAt:             $endLocal->copy()->utc(),
            amountMinor:       $amountMinor,
            currencyCode:      $hourly->currencyCode,
            clientId:          $client->id,
            adminId:           $request->user()?->id,
            notes:             'Бронь из сетки админки',
        );

        try {
            $booking = $this->createBooking->handle($dto);
        } catch (SlotNotAvailableException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 409);
        }

        return response()->json([
            'ok'        => true,
            'message'   => 'Стол забронирован.',
            'public_id' => $booking->public_id,
            'amount'    => $amountMinor / 100,
        ], 201);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Действия над бронью прямо из сетки (как «3 точки» в списке броней).
    // Переиспользуют те же доменные Action'ы, что и MoonShine-ресурс.
    // ─────────────────────────────────────────────────────────────────────

    public function confirm(Request $request, string $publicId, ConfirmBooking $action): JsonResponse
    {
        $booking = $this->resolveBooking($request, $publicId);
        if (! $booking) {
            return $this->deny();
        }
        if (! $booking->isPending()) {
            return $this->err('Бронь уже не в статусе ожидания.');
        }
        try {
            $action->handle($booking, $request->user()?->id, 'Подтверждено из сетки');
        } catch (\Throwable $e) {
            return $this->err($e->getMessage());
        }

        return $this->okMsg('Бронь подтверждена.');
    }

    public function cancel(Request $request, string $publicId, CancelBooking $action): JsonResponse
    {
        $booking = $this->resolveBooking($request, $publicId);
        if (! $booking) {
            return $this->deny();
        }
        if ($booking->isFinal()) {
            return $this->err('Бронь в финальном статусе — отменить нельзя.');
        }
        try {
            $action->handle($booking, $request->user()?->id, 'Отменено из сетки');
        } catch (\Throwable $e) {
            return $this->err($e->getMessage());
        }

        return $this->okMsg('Бронь отменена.');
    }

    public function present(Request $request, string $publicId, MarkAttendance $action): JsonResponse
    {
        return $this->attend($request, $publicId, $action, AttendanceStatus::Present, 'Отмечен приход.');
    }

    public function noShow(Request $request, string $publicId, MarkAttendance $action): JsonResponse
    {
        return $this->attend($request, $publicId, $action, AttendanceStatus::NoShow, 'Отмечена неявка.');
    }

    private function attend(Request $request, string $publicId, MarkAttendance $action, AttendanceStatus $status, string $okMsg): JsonResponse
    {
        $booking = $this->resolveBooking($request, $publicId);
        if (! $booking) {
            return $this->deny();
        }
        try {
            $action->handle($booking, $status, $request->user()?->id, 'Отмечено из сетки');
        } catch (\Throwable $e) {
            return $this->err($e->getMessage());
        }

        return $this->okMsg($okMsg);
    }

    public function paid(Request $request, string $publicId, CreatePayment $create, MarkPaymentPaid $markPaid): JsonResponse
    {
        $booking = $this->resolveBooking($request, $publicId);
        if (! $booking) {
            return $this->deny();
        }
        if ($booking->isCancelled()) {
            return $this->err('Бронь отменена.');
        }
        try {
            $payment = $booking->payments()->where('status', PaymentStatus::Pending->value)->first()
                ?? $create->handle($booking, 'manual', $request->user()?->id);
            $markPaid->handle($payment);
        } catch (\Throwable $e) {
            return $this->err($e->getMessage());
        }

        return $this->okMsg('Оплата отмечена.');
    }

    public function reschedule(Request $request, string $publicId, RescheduleBooking $action): JsonResponse
    {
        $data = $request->validate([
            'date'  => 'required|date_format:Y-m-d',
            'start' => 'required|date_format:H:i',
            'end'   => 'required|date_format:H:i',
        ]);

        $booking = $this->resolveBooking($request, $publicId);
        if (! $booking) {
            return $this->deny();
        }
        if ($booking->isFinal()) {
            return $this->err('Бронь в финальном статусе — перенос невозможен.');
        }

        $tz    = $booking->branch?->timezone ?? config('app.timezone', 'UTC');
        $start = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['start']}", $tz);
        $end   = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['end']}", $tz);
        if ($end->lessThanOrEqualTo($start)) {
            return $this->err('Время окончания должно быть позже начала.');
        }

        $resourceIds = $booking->bookingResources()->pluck('resource_id')->all();

        try {
            $action->handle(
                booking:        $booking,
                newStartAt:     $start->copy()->utc(),
                newEndAt:       $end->copy()->utc(),
                newResourceIds: $resourceIds,
                rescheduledBy:  $request->user()?->id,
            );
        } catch (\Throwable $e) {
            return $this->err($e->getMessage());
        }

        return $this->okMsg('Бронь перенесена.');
    }

    /** Загрузка брони с проверкой доступа администратора к её филиалу. */
    private function resolveBooking(Request $request, string $publicId): ?Booking
    {
        $booking = Booking::where('public_id', $publicId)->first();
        if (! $booking) {
            return null;
        }
        $allowed = $this->accessControl->accessibleBranchIds($request->user());
        if ($allowed !== null && ! in_array($booking->branch_id, $allowed, true)) {
            return null;
        }

        return $booking;
    }

    private function okMsg(string $m): JsonResponse
    {
        return response()->json(['ok' => true, 'message' => $m]);
    }

    private function err(string $m, int $code = 422): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => $m], $code);
    }

    private function deny(): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => 'Бронь не найдена или нет доступа.'], 404);
    }

    private function findOrCreateClient(int $clubId, string $name, string $phone): Client
    {
        $phone  = trim($phone);
        $client = Client::withoutGlobalScopes()
            ->where('club_id', $clubId)
            ->where('phone', $phone)
            ->first();

        if ($client) {
            return $client;
        }

        $parts = preg_split('/\s+/', trim($name), 2);

        return Client::create([
            'club_id'    => $clubId,
            'first_name' => $parts[0] ?? $name,
            'last_name'  => $parts[1] ?? null,
            'phone'      => $phone,
            'source'     => 'admin',
        ]);
    }

    private function tableRentalService(?int $clubId): ?ServiceOffering
    {
        if (! $clubId) {
            return null;
        }

        $query = ServiceOffering::withoutGlobalScopes()
            ->where('club_id', $clubId)
            ->where('is_active', true);

        $likeOp = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return (clone $query)
            ->where(fn ($q) => $q->where('name', $likeOp, '%стол%')->orWhere('name', $likeOp, '%аренд%'))
            ->orderBy('id')
            ->first()
            ?? $query->orderBy('id')->first();
    }

    private function hourlyRate(?ServiceOffering $service, Carbon $date, string $tz): int
    {
        if (! $service) {
            return 0;
        }

        try {
            $price = $this->pricing->priceFor($service, $date->copy()->setTimezone($tz)->setTime(12, 0));
            return (int) round($price->amountMinor / 100);
        } catch (\RuntimeException) {
            return 0;
        }
    }

    private function resolveDate(?string $raw, string $tz): Carbon
    {
        if ($raw && preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $raw, $tz)->startOfDay();
            } catch (\Throwable) {
                // упадём на сегодня
            }
        }

        return Carbon::now($tz)->startOfDay();
    }
}
