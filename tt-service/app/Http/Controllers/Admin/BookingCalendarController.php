<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Booking\Models\Booking;
use App\Domain\Identity\Services\AccessControlService;
use App\Domain\Scheduling\Models\ServiceSession;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Источник событий для календарного вида броней (FullCalendar).
 *
 * Отдаёт два типа событий (ТЗ Блок 5 — «различие между ручными бронями
 * и регулярными занятиями»):
 *  - брони (цвет по статусу);
 *  - регулярные занятия из расписания (фиолетовый).
 *
 * Фильтры: branch_id, resource_id. Tenant-scope применяется автоматически;
 * для admin дополнительно действует ограничение user_branch_access.
 */
class BookingCalendarController extends Controller
{
    /** Цвета по статусу брони */
    private const COLORS = [
        'pending'   => '#f59e0b',
        'confirmed' => '#10b981',
        'cancelled' => '#9ca3af',
        'completed' => '#3b82f6',
        'no_show'   => '#ef4444',
    ];

    /** Цвет регулярных занятий */
    private const SESSION_COLOR = '#8b5cf6';

    public function __construct(
        private readonly AccessControlService $accessControl,
    ) {}

    public function events(Request $request): JsonResponse
    {
        $start = $this->parseDate($request->input('start'), Carbon::now()->startOfMonth());
        $end   = $this->parseDate($request->input('end'),   Carbon::now()->endOfMonth());

        $branchId   = $request->integer('branch_id') ?: null;
        $resourceId = $request->integer('resource_id') ?: null;

        // Ограничение admin'а по филиалам (Фаза 2: user_branch_access)
        $allowedBranchIds = $this->accessControl->accessibleBranchIds($request->user());

        $events = $this->bookingEvents($start, $end, $branchId, $resourceId, $allowedBranchIds)
            ->merge($this->sessionEvents($start, $end, $branchId, $resourceId, $allowedBranchIds))
            ->values();

        return response()->json($events);
    }

    /**
     * @param array<int>|null $allowedBranchIds
     */
    private function bookingEvents(
        Carbon $start,
        Carbon $end,
        ?int $branchId,
        ?int $resourceId,
        ?array $allowedBranchIds,
    ): \Illuminate\Support\Collection {
        $bookings = Booking::query()
            ->with(['serviceOffering', 'branch'])
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->when($resourceId, fn($q) => $q->whereHas(
                'bookingResources',
                fn($q) => $q->where('resource_id', $resourceId)
            ))
            ->orderBy('start_at')
            ->limit(1000)
            ->get();

        return $bookings->map(function (Booking $b) {
            $status  = $b->status->value;
            $service = $b->serviceOffering?->name ?? 'Бронь';
            $branch  = $b->branch?->name ? " · {$b->branch->name}" : '';

            return [
                'id'              => $b->public_id,
                'title'           => $service . $branch,
                'start'           => $b->start_at?->toIso8601String(),
                'end'             => $b->end_at?->toIso8601String(),
                'backgroundColor' => self::COLORS[$status] ?? '#6b7280',
                'borderColor'     => self::COLORS[$status] ?? '#6b7280',
                'extendedProps'   => ['type' => 'booking', 'status' => $status],
            ];
        });
    }

    /**
     * Регулярные занятия (материализованные ServiceSession).
     *
     * @param array<int>|null $allowedBranchIds
     */
    private function sessionEvents(
        Carbon $start,
        Carbon $end,
        ?int $branchId,
        ?int $resourceId,
        ?array $allowedBranchIds,
    ): \Illuminate\Support\Collection {
        $sessions = ServiceSession::query()
            ->with(['template.serviceOffering', 'template.branch'])
            ->where('is_cancelled', false)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->whereHas('template', function ($q) use ($branchId, $allowedBranchIds) {
                $q->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                  ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds));
            })
            ->when($resourceId, fn($q) => $q->whereHas(
                'resources',
                fn($q) => $q->where('resources.id', $resourceId)
            ))
            ->orderBy('start_at')
            ->limit(1000)
            ->get();

        return $sessions->map(function (ServiceSession $s) {
            $service = $s->template?->serviceOffering?->name ?? 'Занятие';
            $branch  = $s->template?->branch?->name ? " · {$s->template->branch->name}" : '';

            return [
                'id'              => 'session-' . $s->public_id,
                'title'           => "Регулярное: {$service}{$branch}",
                'start'           => $s->start_at?->toIso8601String(),
                'end'             => $s->end_at?->toIso8601String(),
                'backgroundColor' => self::SESSION_COLOR,
                'borderColor'     => self::SESSION_COLOR,
                'extendedProps'   => ['type' => 'session', 'status' => $s->status->value],
            ];
        });
    }

    /**
     * Толерантный парсинг даты из query (FullCalendar шлёт ISO-8601;
     * при потере «+» в часовом поясе — откатываемся к значению по умолчанию).
     */
    private function parseDate(?string $value, Carbon $default): Carbon
    {
        if (! $value) {
            return $default;
        }
        try {
            return Carbon::parse(str_replace(' ', '+', $value));
        } catch (\Throwable) {
            return $default;
        }
    }
}
