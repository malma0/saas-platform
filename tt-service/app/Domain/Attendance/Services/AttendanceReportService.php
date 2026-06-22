<?php

namespace App\Domain\Attendance\Services;

use App\Domain\Attendance\Models\Attendance;
use App\Support\Enums\AttendanceStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Статистика посещаемости для отчётов и карточки клиента.
 */
class AttendanceReportService
{
    /**
     * Сводка по клиенту: сколько раз был/не был/неявка.
     */
    public function clientSummary(int $clientId): array
    {
        $rows = Attendance::withoutGlobalScopes()
            ->where('client_id', $clientId)
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        return [
            'present' => (int) ($rows[AttendanceStatus::Present->value] ?? 0),
            'absent'  => (int) ($rows[AttendanceStatus::Absent->value]  ?? 0),
            'no_show' => (int) ($rows[AttendanceStatus::NoShow->value]  ?? 0),
            'total'   => array_sum($rows),
        ];
    }

    /**
     * Процент явки клиента (present / total × 100).
     */
    public function attendanceRate(int $clientId): float
    {
        $summary = $this->clientSummary($clientId);
        if ($summary['total'] === 0) {
            return 0.0;
        }
        return round($summary['present'] / $summary['total'] * 100, 1);
    }

    /**
     * Посещаемость по филиалу за период.
     *
     * @return Collection<int, array{date: string, present: int, absent: int, no_show: int}>
     */
    public function branchDailyStats(int $branchId, Carbon $from, Carbon $to): Collection
    {
        $rows = DB::table('attendance as a')
            ->join('bookings as b', 'b.id', '=', 'a.booking_id')
            ->where('b.branch_id', $branchId)
            ->whereBetween('a.marked_at', [$from->utc(), $to->utc()])
            ->selectRaw("DATE(a.marked_at) as date, a.status, COUNT(*) as cnt")
            ->groupBy('date', 'a.status')
            ->orderBy('date')
            ->get();

        return $rows->groupBy('date')->map(function ($group, $date) {
            $byStatus = $group->pluck('cnt', 'status');
            return [
                'date'    => $date,
                'present' => (int) ($byStatus[AttendanceStatus::Present->value] ?? 0),
                'absent'  => (int) ($byStatus[AttendanceStatus::Absent->value]  ?? 0),
                'no_show' => (int) ($byStatus[AttendanceStatus::NoShow->value]  ?? 0),
            ];
        })->values();
    }
}
