<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Booking\Models\ReservationHold;
use App\Domain\Booking\Services\ConflictChecker;
use App\Domain\Facilities\Models\Resource;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

/**
 * Временная блокировка слота на время оформления/оплаты (Фаза 6).
 *
 * Та же защита от гонок, что и в CreateBooking:
 * SELECT ... FOR UPDATE + конфликт-чек в одной транзакции.
 * Все ресурсы блокируются под одним session_token — его клиент
 * передаёт в POST /bookings как hold_token.
 */
class CreateHold
{
    public const DEFAULT_TTL_MINUTES = 5;

    public function __construct(
        private readonly ConflictChecker $conflictChecker = new ConflictChecker(),
    ) {}

    /**
     * @param array<int> $resourceIds
     * @return ReservationHold первый hold группы (общие token и expires_at)
     *
     * @throws SlotNotAvailableException
     */
    public function handle(
        int    $clubId,
        int    $branchId,
        array  $resourceIds,
        Carbon $startAt,
        Carbon $endAt,
        int    $ttlMinutes = self::DEFAULT_TTL_MINUTES,
    ): ReservationHold {
        return DB::transaction(function () use ($clubId, $branchId, $resourceIds, $startAt, $endAt, $ttlMinutes) {

            $resources = Resource::whereIn('id', $resourceIds)
                ->lockForUpdate()
                ->get();

            if ($resources->count() !== count($resourceIds)) {
                throw new SlotNotAvailableException('Один или несколько ресурсов не найдены.');
            }

            $allRelatedIds = [];
            foreach ($resources as $resource) {
                $allRelatedIds = array_unique(
                    array_merge($allRelatedIds, $resource->relatedResourceIds())
                );
            }

            $this->conflictChecker->assertNoConflict(
                $allRelatedIds,
                $startAt->toDateTimeString(),
                $endAt->toDateTimeString(),
                $branchId,
            );

            $token     = strtolower((string) new Ulid());
            $expiresAt = now()->addMinutes($ttlMinutes);

            $first = null;
            foreach ($resourceIds as $resourceId) {
                $hold = ReservationHold::create([
                    'club_id'       => $clubId,
                    'resource_id'   => $resourceId,
                    'start_at'      => $startAt,
                    'end_at'        => $endAt,
                    'expires_at'    => $expiresAt,
                    'session_token' => $token,
                ]);
                $first ??= $hold;
            }

            return $first;
        });
    }
}
