<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Booking\Cache\AvailabilityCache;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Scheduling\Models\ServiceSession;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly AvailabilityCache $availability,
    ) {}

    /**
     * GET /api/schedule
     *
     * Query params:
     *   branch_id  (required)
     *   date_from  (Y-m-d, default = today)
     *   date_to    (Y-m-d, default = date_from + 7 days)
     *
     * Возвращает:
     *   - sessions: расписание тренировок/занятий из schedule_templates
     *   - busy_slots: занятые интервалы (только занятые ресурсы, без деталей)
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to'   => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $branchId = $request->integer('branch_id');
        $dateFrom = Carbon::parse($request->input('date_from', now()->toDateString()))->startOfDay()->utc();
        $dateTo   = Carbon::parse($request->input('date_to', $dateFrom->copy()->addDays(6)->toDateString()))->endOfDay()->utc();

        // Занятые ресурсы за период
        $busyResourceIds = $this->availability->getBusyResourceIds($branchId, $dateFrom, $dateTo);

        // Запланированные сессии (групповые занятия)
        $sessions = ServiceSession::query()
            ->whereHas('template', fn($q) => $q->where('branch_id', $branchId))
            ->where('is_cancelled', false)
            ->whereBetween('start_at', [$dateFrom, $dateTo])
            ->with(['template.serviceOffering', 'resources'])
            ->orderBy('start_at')
            ->get()
            ->map(fn($s) => [
                'id'          => $s->public_id,
                'title'       => $s->template->serviceOffering?->name ?? 'Сессия',
                'start_at'    => $s->start_at->toIso8601String(),
                'end_at'      => $s->end_at->toIso8601String(),
                'status'      => $s->status->value,
                'resources'   => $s->resources->pluck('id'),
            ]);

        return ApiResponse::success([
            'branch_id'        => $branchId,
            'date_from'        => $dateFrom->toDateString(),
            'date_to'          => $dateTo->toDateString(),
            'sessions'         => $sessions,
            'busy_resource_ids'=> array_values(array_unique($busyResourceIds)),
        ]);
    }
}
