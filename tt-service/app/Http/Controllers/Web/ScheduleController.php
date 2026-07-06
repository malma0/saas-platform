<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Booking\Models\Booking;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Services\Models\ServiceOffering;
use App\Domain\Services\Services\PricingService;
use App\Http\Controllers\Controller;
use App\Support\Device;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Публичная страница расписания (бронирование стола).
 *
 * Server-side рендер: контроллер достаёт реальные столы и брони филиала
 * из БД (те самые, что админ ведёт в MoonShine) и отдаёт в Blade.
 * Сетка на странице строится из этих данных, а не из захардкоженного JS.
 */
class ScheduleController extends Controller
{
    /** Тип ресурса «Стол». */
    private const RESOURCE_TYPE_TABLE = 1;

    /** Онлайн-бронь доступна на столько дней вперёд (синхронно с BookingController). */
    private const MAX_DAYS_AHEAD = 7;

    /** Телефон администратора для брони на дальний срок. */
    private const ADMIN_PHONE = '+7 (383) 207-86-20';

    public function index(Request $request): View
    {
        // Активный филиал (публичная страница — tenant-scope не применяется).
        $branch = Branch::query()
            ->where('is_active', true)
            ->first();

        $tz = $branch?->timezone ?: 'Europe/Moscow';

        // Столы филиала по порядку (зал исключаем — только тип «Стол»).
        $tables = $branch
            ? Resource::query()
                ->where('branch_id', $branch->id)
                ->where('resource_type_id', self::RESOURCE_TYPE_TABLE)
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name'])
            : collect();

        // Какую дату показываем (в часовом поясе филиала).
        // app.timezone и сессия БД совпадают с поясом филиала, поэтому
        // границы дня берём как есть — без перевода в UTC.
        $date = $this->resolveDate($request->query('date'), $tz);
        $dayStart = $date->copy()->startOfDay();
        $dayEnd   = $date->copy()->endOfDay();

        // Карта resource_id → индекс строки (стола) в сетке.
        $rowByResource = [];
        foreach ($tables as $i => $t) {
            $rowByResource[$t->id] = $i;
        }

        // Брони филиала на выбранный день (кроме отменённых), со столами и услугой.
        $events = [];
        if ($branch) {
            $bookings = Booking::query()
                ->where('branch_id', $branch->id)
                ->where('status', '!=', BookingStatus::Cancelled->value)
                ->where('start_at', '<', $dayEnd)
                ->where('end_at', '>', $dayStart)
                ->with(['bookingResources', 'serviceOffering'])
                ->get();

            foreach ($bookings as $b) {
                $title = $b->serviceOffering?->name ?? 'Бронирование';
                $type  = $this->typeForService($title);
                $start = $b->start_at->copy()->timezone($tz)->format('H:i');
                $end   = $b->end_at->copy()->timezone($tz)->format('H:i');

                foreach ($b->bookingResources as $br) {
                    if (!array_key_exists($br->resource_id, $rowByResource)) {
                        continue; // бронь на столе другого филиала — пропускаем
                    }
                    $events[] = [
                        'row'   => $rowByResource[$br->resource_id],
                        'type'  => $type,
                        'title' => $title,
                        'start' => $start,
                        'end'   => $end,
                    ];
                }
            }
        }

        // Услуга «аренда стола» и ставка за час — для оформления брони с сайта.
        $service     = $this->tableRentalService($branch?->club_id);
        $ratePerHour = $this->hourlyRate($service, $date, $tz);

        return view(Device::isMobile() ? 'mobile.schedule' : 'schedule', [
            'branch'     => $branch,
            'tableNames' => $tables->pluck('name')->values(),
            'tableIds'   => $tables->pluck('id')->values(),
            'events'     => $events,
            'scheduleDate' => $date->toDateString(),
            'clubId'      => $branch?->club_id,
            'branchId'    => $branch?->id,
            'serviceId'   => $service?->id,
            'ratePerHour' => $ratePerHour,
            // Онлайн-бронь доступна на неделю вперёд; дальше — через администратора.
            'maxBookDate' => Carbon::now($tz)->startOfDay()->addDays(self::MAX_DAYS_AHEAD)->toDateString(),
            'adminPhone'  => self::ADMIN_PHONE,
            'prefTime'   => $request->query('time'),
            'prefService'=> $request->query('service'),
        ]);
    }

    /**
     * Услуга аренды стола для клуба (по названию, иначе — первая активная).
     */
    private function tableRentalService(?int $clubId): ?ServiceOffering
    {
        if (! $clubId) {
            return null;
        }

        $query = ServiceOffering::withoutGlobalScopes()
            ->where('club_id', $clubId)
            ->where('is_active', true);

        // PostgreSQL — ilike (регистронезависимо), SQLite — like (для ASCII
        // и так регистронезависим, а искомые слова уже в нижнем регистре).
        $likeOp = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return (clone $query)
            ->where(fn ($q) => $q->where('name', $likeOp, '%стол%')->orWhere('name', $likeOp, '%аренд%'))
            ->orderBy('id')
            ->first()
            ?? $query->orderBy('id')->first();
    }

    /**
     * Ставка за час в рублях (PricingRule трактуем как почасовую). 0 — если цены нет.
     */
    private function hourlyRate(?ServiceOffering $service, Carbon $date, string $tz): int
    {
        if (! $service) {
            return 0;
        }

        try {
            $price = app(PricingService::class)->priceFor(
                $service,
                $date->copy()->setTimezone($tz)->setTime(12, 0)
            );
            return (int) round($price->amountMinor / 100);
        } catch (\RuntimeException) {
            return 0;
        }
    }

    /**
     * Дату принимаем в формате Y-m-d (?date=2026-06-18). Иначе — сегодня.
     */
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

    /**
     * Грубое сопоставление услуги → тип ячейки для раскраски сетки.
     */
    private function typeForService(string $name): string
    {
        $n = mb_strtolower($name);
        if (str_contains($n, 'трениров')) {
            return 'training-ind';
        }
        if (str_contains($n, 'турнир')) {
            return 'tournament';
        }
        if (str_contains($n, 'зал')) {
            return 'club-event';
        }
        return 'private';
    }
}
