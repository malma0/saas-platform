<?php

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTO\ReportParams;
use App\Support\Enums\PaymentStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Отчёт по выручке за период.
 */
class RevenueReportService
{
    /**
     * Итоговая выручка за период (только оплаченные платежи).
     *
     * @return array{
     *   total_paid_minor: int,
     *   total_refunded_minor: int,
     *   net_minor: int,
     *   currency_code: string,
     *   payments_count: int,
     * }
     */
    public function summary(ReportParams $params): array
    {
        // Оплаченные платежи за период
        $paid = DB::table('payments as p')
            ->join('bookings as b', 'b.id', '=', 'p.booking_id')
            ->where('p.club_id', $params->clubId)
            ->where('p.status', PaymentStatus::Paid->value)
            ->whereBetween('p.updated_at', [
                $params->dateFrom->startOfDay()->utc(),
                $params->dateTo->endOfDay()->utc(),
            ]);

        if ($params->branchId) {
            $paid->where('b.branch_id', $params->branchId);
        }

        $paidResult = $paid
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(p.amount_minor), 0) as total, p.currency_code')
            ->groupBy('p.currency_code')
            ->first();

        // Возвраты за период
        $refunded = (int) DB::table('refunds as r')
            ->join('payments as p', 'p.id', '=', 'r.payment_id')
            ->where('p.club_id', $params->clubId)
            ->where('r.status', 'completed')
            ->whereBetween('r.created_at', [
                $params->dateFrom->startOfDay()->utc(),
                $params->dateTo->endOfDay()->utc(),
            ])
            ->sum('r.amount_minor');

        $totalPaid    = (int)($paidResult?->total ?? 0);
        $paymentsCount = (int)($paidResult?->cnt ?? 0);

        return [
            'total_paid_minor'     => $totalPaid,
            'total_refunded_minor' => $refunded,
            'net_minor'            => $totalPaid - $refunded,
            'currency_code'        => $paidResult?->currency_code ?? 'RUB',
            'payments_count'       => $paymentsCount,
        ];
    }

    /**
     * Выручка по дням.
     *
     * @return Collection<int, array{date: string, paid_minor: int, refunded_minor: int, net_minor: int}>
     */
    public function byDay(ReportParams $params): Collection
    {
        $paid = DB::table('payments as p')
            ->join('bookings as b', 'b.id', '=', 'p.booking_id')
            ->where('p.club_id', $params->clubId)
            ->where('p.status', PaymentStatus::Paid->value)
            ->whereBetween('p.updated_at', [
                $params->dateFrom->startOfDay()->utc(),
                $params->dateTo->endOfDay()->utc(),
            ])
            ->selectRaw("DATE(p.updated_at) as date, COALESCE(SUM(p.amount_minor), 0) as paid_minor")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('paid_minor', 'date');

        $refunded = DB::table('refunds as r')
            ->join('payments as p', 'p.id', '=', 'r.payment_id')
            ->where('p.club_id', $params->clubId)
            ->where('r.status', 'completed')
            ->whereBetween('r.created_at', [
                $params->dateFrom->startOfDay()->utc(),
                $params->dateTo->endOfDay()->utc(),
            ])
            ->selectRaw("DATE(r.created_at) as date, COALESCE(SUM(r.amount_minor), 0) as refunded_minor")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('refunded_minor', 'date');

        // Объединяем по датам
        $allDates = $paid->keys()->merge($refunded->keys())->unique()->sort()->values();

        return $allDates->map(function ($date) use ($paid, $refunded) {
            $p = (int)($paid[$date] ?? 0);
            $r = (int)($refunded[$date] ?? 0);
            return [
                'date'            => $date,
                'paid_minor'      => $p,
                'refunded_minor'  => $r,
                'net_minor'       => $p - $r,
            ];
        });
    }
}
