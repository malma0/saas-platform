<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Coach;
use App\Domain\Services\Models\ServiceOffering;
use App\Http\Controllers\Controller;
use App\Support\Device;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Запись на индивидуальную тренировку к тренеру.
 *
 * Тренер — это ресурс типа «тренер», поэтому запись = обычная бронь
 * (CreateBooking) этого ресурса. Цена — по ставке тренера × длительность.
 * Те же правила, что у брони стола: вход, рабочие часы, окно недели.
 */
class CoachController extends Controller
{
    /** Окно онлайн-записи (синхронно с BookingController). */
    private const MAX_DAYS_AHEAD = 7;
    private const ADMIN_PHONE = '+7 (383) 207-86-20';

    public function __construct(
        private readonly CreateBooking $createBooking,
    ) {}

    public function index(Request $request): View
    {
        $branch = Branch::query()->where('is_active', true)->first();

        $coaches = Coach::query()
            ->where('is_active', true)
            ->when($branch, fn ($q) => $q->where('branch_id', $branch->id))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $service = $this->trainingService($branch?->club_id);
        $tz = $branch?->timezone ?: 'Europe/Moscow';

        // Мобильная страница появится следующим шагом; пока — fallback на десктоп.
        $view = Device::isMobile($request) && view()->exists('mobile.coaches') ? 'mobile.coaches' : 'coaches';

        return view($view, [
            'branch'      => $branch,
            'coaches'     => $coaches,
            'serviceId'   => $service?->id,
            'bookDate'    => Carbon::now($tz)->toDateString(),
            'maxBookDate' => Carbon::now($tz)->startOfDay()->addDays(self::MAX_DAYS_AHEAD)->toDateString(),
            'adminPhone'  => self::ADMIN_PHONE,
        ]);
    }

    public function book(Request $request): JsonResponse
    {
        $data = $request->validate([
            'coach_id' => 'required|integer',
            'name'     => 'required|string|min:2|max:120',
            'phone'    => 'required|string|min:5|max:30',
            'date'     => 'required|date_format:Y-m-d',
            'start'    => 'required|date_format:H:i',
            'end'      => 'required|date_format:H:i',
        ]);

        $coach = Coach::withoutGlobalScopes()
            ->where('is_active', true)
            ->find($data['coach_id']);
        if (! $coach) {
            return response()->json(['ok' => false, 'message' => 'Тренер не найден.'], 404);
        }

        $branch  = Branch::findOrFail($coach->branch_id);
        $service = $this->trainingService($coach->club_id);
        if (! $service) {
            return response()->json(['ok' => false, 'message' => 'Услуга тренировки не настроена.'], 422);
        }
        $tz = $branch->timezone ?: 'Europe/Moscow';

        $startLocal = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['start']}", $tz);
        $endLocal   = Carbon::createFromFormat('Y-m-d H:i', "{$data['date']} {$data['end']}", $tz);

        if ($endLocal->lessThanOrEqualTo($startLocal)) {
            return response()->json(['ok' => false, 'message' => 'Время окончания должно быть позже начала.'], 422);
        }
        if ($startLocal->isPast()) {
            return response()->json(['ok' => false, 'message' => 'Нельзя записаться на прошедшее время.'], 422);
        }

        $maxDate = Carbon::now($tz)->startOfDay()->addDays(self::MAX_DAYS_AHEAD);
        if ($startLocal->copy()->startOfDay()->greaterThan($maxDate)) {
            return response()->json([
                'ok'            => false,
                'contact_admin' => true,
                'message'       => 'Онлайн-запись доступна только на неделю вперёд. '
                    . 'Для записи на более поздний срок свяжитесь с администратором: ' . self::ADMIN_PHONE . '.',
            ], 422);
        }

        // Рабочие часы филиала
        $wh = $branch->getWorkingHourForDay((int) $startLocal->dayOfWeek);
        if (! $wh || $wh->is_closed) {
            return response()->json(['ok' => false, 'message' => 'В этот день клуб не работает.'], 422);
        }
        $openAt  = $startLocal->copy()->setTimeFromTimeString($wh->open_time);
        $closeAt = $startLocal->copy()->setTimeFromTimeString($wh->close_time);
        if ($startLocal->lt($openAt) || $endLocal->gt($closeAt)) {
            return response()->json([
                'ok'      => false,
                'message' => "Время вне рабочих часов клуба ({$wh->open_time}–{$wh->close_time}).",
            ], 422);
        }

        $hours       = $startLocal->diffInMinutes($endLocal) / 60;
        $amountMinor = (int) round($coach->hourly_rate_minor * $hours);

        $client = $this->findOrCreateClient($coach->club_id, $data['name'], $data['phone']);

        $dto = new CreateBookingDTO(
            clubId:            $coach->club_id,
            branchId:          $branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       [$coach->resource_id],
            startAt:           $startLocal->copy()->utc(),
            endAt:             $endLocal->copy()->utc(),
            amountMinor:       $amountMinor,
            currencyCode:      'RUB',
            clientId:          $client->id,
            adminId:           null,
            notes:             'Тренировка: ' . $coach->name,
        );

        try {
            $booking = $this->createBooking->handle($dto);
        } catch (SlotNotAvailableException $e) {
            return response()->json(['ok' => false, 'message' => 'Тренер занят в это время — выберите другое.'], 409);
        }

        return response()->json([
            'ok'          => true,
            'message'     => 'Вы записаны на тренировку.',
            'public_id'   => $booking->public_id,
            'coach'       => $coach->name,
            'start'       => $data['start'],
            'end'         => $data['end'],
            'amount'      => $amountMinor / 100,
            'account_url' => route('web.account', ['phone' => $data['phone']]),
        ], 201);
    }

    /** Услуга «Индивидуальная тренировка» (по названию, иначе — первая активная). */
    private function trainingService(?int $clubId): ?ServiceOffering
    {
        if (! $clubId) {
            return null;
        }

        $query = ServiceOffering::withoutGlobalScopes()
            ->where('club_id', $clubId)
            ->where('is_active', true);

        $likeOp = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return (clone $query)
            ->where('name', $likeOp, '%трениров%')
            ->orderBy('id')
            ->first()
            ?? $query->orderBy('id')->first();
    }

    /** Найти клиента по телефону в клубе или создать нового. */
    private function findOrCreateClient(int $clubId, string $name, string $phone): Client
    {
        $phone = trim($phone);

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
            'source'     => 'website',
        ]);
    }
}
