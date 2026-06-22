<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Booking\Models\Booking;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Http\Controllers\Controller;
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

        return view('schedule', [
            'branch'     => $branch,
            'tableNames' => $tables->pluck('name')->values(),
            'events'     => $events,
            'scheduleDate' => $date->toDateString(),
            'prefTime'   => $request->query('time'),
            'prefService'=> $request->query('service'),
        ]);
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
