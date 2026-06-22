<?php

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTO\ReportParams;
use App\Support\Enums\BookingStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Отчёт по бронированиям за период.
 */
class BookingReportService
{
    /**
     * Сводка по статусам за период.
     *
     * @return array{
     *   total: int,
     *   confirmed: int,
     *   completed: int,
     *   cancelled: int,
     *   no_show: int,
     *   total_amount_minor: int,
     * }
     */
    public function summary(ReportParams $params): array
    {
        $query = DB::table('bookings')
            ->where('club_id', $params->clubId)
            ->whereBetween('start_at', [
                $params->dateFrom->startOfDay()->utc(),
                $params->dateTo->endOfDay()->utc(),
            ])
            ->whereNull('deleted_at');

        if ($params->branchId) {
            $query->where('branch_id', $params->branchId);
        }

        $rows = $query
            ->selectRaw('status, COUNT(*) as cnt, COALESCE(SUM(amount_minor), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $get = fn(string $status) => (int)($rows[$status]?->cnt ?? 0);
        $amt = fn(string $status) => (int)($rows[$status]?->total_amount ?? 0);

        return [
            'total'              => $rows->sum('cnt'),
            'confirmed'          => $get(BookingStatus::Confirmed->value),
            'completed'          => $get(BookingStatus::Completed->value),
            'cancelled'          => $get(BookingStatus::Cancelled->value),
            'no_show'            => $get(BookingStatus::NoShow->value),
            'total_amount_minor' => $rows->sum('total_amount'),
        ];
    }

    /**
     * Бронирования по дням (для графика).
     *
     * @return Collection<int, array{date: string, count: int, amount_minor: int}>
     */
    public function byDay(ReportParams $params): Collection
    {
        $query = DB::table('bookings')
            ->where('club_id', $params->clubId)
            ->whereBetween('start_at', [
                $params->dateFrom->startOfDay()->utc(),
                $params->dateTo->endOfDay()->utc(),
            ])
            ->whereNull('deleted_at')
            ->whereNotIn('status', [BookingStatus::Cancelled->value]);

        if ($params->branchId) {
            $query->where('branch_id', $params->branchId);
        }

        return $query
            ->selectRaw("DATE(start_at) as date, COUNT(*) as count, COALESCE(SUM(amount_minor), 0) as amount_minor")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn($row) => [
                'date'         => $row->date,
                'count'        => (int) $row->count,
                'amount_minor' => (int) $row->amount_minor,
            ]);
    }

    /**
     * Детальный список броней для выгрузки.
     *
     * @return Collection
     */
    public function rows(ReportParams $params): Collection
    {
        $query = DB::table('bookings as b')
            ->leftJoin('clients as c', 'c.id', '=', 'b.client_id')
            ->leftJoin('service_offerings as s', 's.id', '=', 'b.service_offering_id')
            ->leftJoin('branches as br', 'br.id', '=', 'b.branch_id')
            ->where('b.club_id', $params->clubId)
            ->whereBetween('b.start_at', [
                $params->dateFrom->startOfDay()->utc(),
                $params->dateTo->endOfDay()->utc(),
            ])
            ->whereNull('b.deleted_at');

        if ($params->branchId) {
            $query->where('b.branch_id', $params->branchId);
        }

        return $query
            ->select([
                'b.id',
                'b.public_id',
                'b.start_at',
                'b.end_at',
                'b.status',
                'b.amount_minor',
                'b.currency_code',
                DB::raw("CONCAT(c.first_name, ' ', COALESCE(c.last_name, '')) as client_name"),
                'c.phone as client_phone',
                's.name as service_name',
                'br.name as branch_name',
            ])
            ->orderBy('b.start_at')
            ->get();
    }
}
