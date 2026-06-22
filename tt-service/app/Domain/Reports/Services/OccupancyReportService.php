<?php

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTO\ReportParams;
use App\Support\Enums\BookingStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Отчёт загрузки столов.
 *
 * Сравнивает:
 *  1. Брони (система) — что было забронировано и оплачено
 *  2. Данные анализатора (table_occupancy_sessions) — реальная физическая занятость
 *
 * Расхождение = стол был занят физически, но брони/оплаты нет → подозрительно.
 */
class OccupancyReportService
{
    /**
     * Загрузка по ресурсам за период.
     *
     * @return Collection<int, array{
     *   resource_id: int,
     *   resource_name: string,
     *   booking_minutes: int,
     *   analyzer_minutes: int,
     *   unmatched_minutes: int,
     * }>
     */
    public function byResource(ReportParams $params): Collection
    {
        $from = $params->dateFrom->startOfDay()->utc()->toDateTimeString();
        $to   = $params->dateTo->endOfDay()->utc()->toDateTimeString();

        // Минуты из броней (completed + confirmed)
        $bookingMinutes = DB::table('booking_resources as br')
            ->join('bookings as b', 'b.id', '=', 'br.booking_id')
            ->join('resources as r', 'r.id', '=', 'br.resource_id')
            ->where('b.club_id', $params->clubId)
            ->whereIn('b.status', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])
            ->whereNull('b.deleted_at')
            ->whereBetween('b.start_at', [$from, $to])
            ->when($params->branchId, fn($q) => $q->where('b.branch_id', $params->branchId))
            ->when($params->resourceId, fn($q) => $q->where('br.resource_id', $params->resourceId))
            ->selectRaw("br.resource_id, r.name as resource_name,
                COALESCE(SUM(EXTRACT(EPOCH FROM (b.end_at - b.start_at)) / 60), 0)::INTEGER as booking_minutes")
            ->groupBy('br.resource_id', 'r.name')
            ->get()
            ->keyBy('resource_id');

        // Минуты из анализатора
        $analyzerMinutes = DB::table('table_occupancy_sessions as o')
            ->join('resources as r', 'r.id', '=', 'o.resource_id')
            ->where('o.club_id', $params->clubId)
            ->whereBetween('o.start_at', [$from, $to])
            ->when($params->branchId, fn($q) => $q->where('o.branch_id', $params->branchId))
            ->when($params->resourceId, fn($q) => $q->where('o.resource_id', $params->resourceId))
            ->selectRaw("o.resource_id, r.name as resource_name,
                COALESCE(SUM(o.duration_seconds) / 60, 0)::INTEGER as analyzer_minutes")
            ->groupBy('o.resource_id', 'r.name')
            ->get()
            ->keyBy('resource_id');

        // Объединяем
        $allResourceIds = $bookingMinutes->keys()
            ->merge($analyzerMinutes->keys())
            ->unique();

        return $allResourceIds->map(function ($resourceId) use ($bookingMinutes, $analyzerMinutes) {
            $bRow = $bookingMinutes[$resourceId] ?? null;
            $aRow = $analyzerMinutes[$resourceId] ?? null;

            $bMin = (int)($bRow?->booking_minutes ?? 0);
            $aMin = (int)($aRow?->analyzer_minutes ?? 0);

            return [
                'resource_id'        => $resourceId,
                'resource_name'      => $bRow?->resource_name ?? $aRow?->resource_name ?? "Resource #{$resourceId}",
                'booking_minutes'    => $bMin,
                'analyzer_minutes'   => $aMin,
                // Физически занят, но нет брони — подозрительно
                'unmatched_minutes'  => max(0, $aMin - $bMin),
            ];
        })->values();
    }

    /**
     * Сводка по дням (для графика загрузки).
     *
     * @return Collection<int, array{date: string, booking_minutes: int, analyzer_minutes: int}>
     */
    public function byDay(ReportParams $params): Collection
    {
        $from = $params->dateFrom->startOfDay()->utc()->toDateTimeString();
        $to   = $params->dateTo->endOfDay()->utc()->toDateTimeString();

        $bookingByDay = DB::table('booking_resources as br')
            ->join('bookings as b', 'b.id', '=', 'br.booking_id')
            ->where('b.club_id', $params->clubId)
            ->whereIn('b.status', [BookingStatus::Confirmed->value, BookingStatus::Completed->value])
            ->whereNull('b.deleted_at')
            ->whereBetween('b.start_at', [$from, $to])
            ->when($params->branchId, fn($q) => $q->where('b.branch_id', $params->branchId))
            ->selectRaw("DATE(b.start_at) as date,
                COALESCE(SUM(EXTRACT(EPOCH FROM (b.end_at - b.start_at)) / 60), 0)::INTEGER as minutes")
            ->groupBy('date')
            ->pluck('minutes', 'date');

        $analyzerByDay = DB::table('table_occupancy_sessions')
            ->where('club_id', $params->clubId)
            ->whereBetween('start_at', [$from, $to])
            ->when($params->branchId, fn($q) => $q->where('branch_id', $params->branchId))
            ->selectRaw("DATE(start_at) as date,
                COALESCE(SUM(duration_seconds) / 60, 0)::INTEGER as minutes")
            ->groupBy('date')
            ->pluck('minutes', 'date');

        $allDates = $bookingByDay->keys()->merge($analyzerByDay->keys())->unique()->sort()->values();

        return $allDates->map(fn($date) => [
            'date'             => $date,
            'booking_minutes'  => (int)($bookingByDay[$date] ?? 0),
            'analyzer_minutes' => (int)($analyzerByDay[$date] ?? 0),
        ]);
    }
}
