<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\ReservationHold;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Сервис поиска доступных временных слотов.
 *
 * Алгоритм:
 *  1. Взять рабочие часы филиала на дату
 *  2. Получить ресурсы нужных типов в филиале
 *  3. Для каждого кандидата-ресурса проверить:
 *     - нет подтверждённых/pending броней (+ их связанных ресурсов через closure)
 *     - нет активных holds (expires_at > now())
 *  4. Вернуть слоты, где есть хотя бы один свободный ресурс каждого нужного типа
 *
 * ВАЖНО: этот сервис — для ЧТЕНИЯ/UI.
 * Финальная проверка при бронировании — всегда в транзакции с SELECT FOR UPDATE.
 */
class AvailabilityService
{
    /**
     * Найти свободные слоты для услуги в филиале на дату.
     *
     * @return Collection<int, array{start_at: Carbon, end_at: Carbon, resource_ids: array<int>}>
     */
    public function availableSlots(
        ServiceOffering $service,
        Branch $branch,
        Carbon $date,
    ): Collection {
        // Дата в часовом поясе филиала
        $localDate = $date->copy()->setTimezone($branch->timezone);
        $dayOfWeek = $localDate->dayOfWeek; // 0=Вс..6=Сб

        // 1. Рабочее время филиала на этот день
        $workingHour = $branch->workingHours->firstWhere('day_of_week', $dayOfWeek);

        if (! $workingHour || $workingHour->is_closed) {
            return collect(); // выходной
        }

        // Диапазон рабочего дня в UTC
        $openAt  = Carbon::parse($localDate->format('Y-m-d') . ' ' . $workingHour->open_time, $branch->timezone)->utc();
        $closeAt = Carbon::parse($localDate->format('Y-m-d') . ' ' . $workingHour->close_time, $branch->timezone)->utc();

        // 2. Собрать требования к ресурсам
        $requirements = $service->resourceRequirements()->with('resourceType')->get();

        if ($requirements->isEmpty()) {
            return collect();
        }

        $slots = collect();
        $stepMinutes = $service->duration_minutes;
        $current = $openAt->copy();

        // 3. Перебираем слоты шагом = длительность услуги
        while ($current->copy()->addMinutes($stepMinutes)->lte($closeAt)) {
            $slotStart = $current->copy();
            $slotEnd   = $current->copy()->addMinutes($stepMinutes);

            $slotResources = [];
            $slotAvailable = true;

            // Для каждого типа ресурса из требований
            foreach ($requirements as $req) {
                $freeResources = $this->findFreeResources(
                    branchId:       $branch->id,
                    resourceTypeId: $req->resource_type_id,
                    startAt:        $slotStart,
                    endAt:          $slotEnd,
                    quantity:       $req->quantity,
                );

                if ($freeResources->count() < $req->quantity) {
                    $slotAvailable = false;
                    break;
                }

                $slotResources[] = $freeResources->take($req->quantity)->pluck('id')->all();
            }

            if ($slotAvailable) {
                $slots->push([
                    'start_at'     => $slotStart,
                    'end_at'       => $slotEnd,
                    'resource_ids' => array_merge(...$slotResources),
                ]);
            }

            $current->addMinutes($stepMinutes);
        }

        return $slots;
    }

    /**
     * Найти свободные ресурсы указанного типа в филиале на интервал.
     * Учитывает: подтверждённые/pending брони + активные holds + closure-иерархию.
     *
     * @return Collection<int, Resource>
     */
    public function findFreeResources(
        int    $branchId,
        int    $resourceTypeId,
        Carbon $startAt,
        Carbon $endAt,
        int    $quantity = 1,
    ): Collection {
        // Получаем ID всех занятых ресурсов на этот интервал
        $busyResourceIds = $this->getBusyResourceIds($branchId, $startAt, $endAt);

        return Resource::where('branch_id', $branchId)
            ->where('resource_type_id', $resourceTypeId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotIn('id', $busyResourceIds)
            ->take($quantity)
            ->get();
    }

    /**
     * Получить ID всех занятых ресурсов на интервал.
     *
     * Формула пересечения: A.start < B.end AND A.end > B.start
     *
     * Занятость включает:
     *  1. Брони со статусом pending/confirmed (+ связанные через closure)
     *  2. Активные holds (expires_at > now()) (+ связанные через closure)
     *
     * @return array<int>
     */
    public function getBusyResourceIds(int $branchId, Carbon $startAt, Carbon $endAt): array
    {
        $start = $startAt->toDateTimeString();
        $end   = $endAt->toDateTimeString();
        $now   = now()->toDateTimeString();

        // 1. Ресурсы занятые бронями (прямые + через closure)
        $bookedDirect = DB::table('booking_resources as br')
            ->join('bookings as b', 'b.id', '=', 'br.booking_id')
            ->where('b.branch_id', $branchId)
            ->whereIn('b.status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value])
            ->whereNull('b.deleted_at')
            ->where('b.start_at', '<', $end)
            ->where('b.end_at', '>', $start)
            ->pluck('br.resource_id')
            ->all();

        // 2. Ресурсы занятые holds
        $heldDirect = DB::table('reservation_holds')
            ->where('expires_at', '>', $now)
            ->where('start_at', '<', $end)
            ->where('end_at', '>', $start)
            ->pluck('resource_id')
            ->all();

        // 3. Ресурсы зарезервированные регулярными занятиями (session_resources)
        $sessionDirect = DB::table('session_resources as sr')
            ->join('service_sessions as ss', 'ss.id', '=', 'sr.service_session_id')
            ->join('schedule_templates as st', 'st.id', '=', 'ss.schedule_template_id')
            ->where('st.branch_id', $branchId)
            ->where('ss.is_cancelled', false)
            ->whereNull('ss.deleted_at')
            ->where('ss.start_at', '<', $end)
            ->where('ss.end_at', '>', $start)
            ->pluck('sr.resource_id')
            ->all();

        $directBusy = array_unique(array_merge($bookedDirect, $heldDirect, $sessionDirect));

        if (empty($directBusy)) {
            return [];
        }

        // 3. Расширяем через closure: добавляем всех предков и потомков
        $rows = DB::select(
            'SELECT DISTINCT ancestor_id AS rid FROM resource_closure WHERE descendant_id IN (' . implode(',', $directBusy) . ')
             UNION
             SELECT DISTINCT descendant_id AS rid FROM resource_closure WHERE ancestor_id IN (' . implode(',', $directBusy) . ')',
        );

        return array_unique(array_column($rows, 'rid'));
    }

    /**
     * Проверить: свободен ли конкретный ресурс на интервал.
     * Используется при подтверждении брони (перед FOR UPDATE).
     */
    public function isResourceFree(Resource $resource, Carbon $startAt, Carbon $endAt): bool
    {
        $relatedIds = $resource->relatedResourceIds();
        $busyIds    = $this->getBusyResourceIds($resource->branch_id, $startAt, $endAt);

        return empty(array_intersect($relatedIds, $busyIds));
    }
}
