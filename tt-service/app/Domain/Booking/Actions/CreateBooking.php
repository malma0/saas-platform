<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingResource;
use App\Domain\Booking\Models\BookingStatusHistory;
use App\Domain\Booking\Models\ReservationHold;
use App\Domain\Booking\Services\ConflictChecker;
use App\Domain\Facilities\Models\Resource;
use App\Support\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;

/**
 * Создание брони — главный action движка.
 *
 * ЗАЩИТА ОТ ГОНОК (race condition / двойное бронирование):
 *
 * Используем SELECT ... FOR UPDATE — PostgreSQL блокирует строки ресурсов
 * на время транзакции. Если два запроса пришли одновременно на один слот,
 * второй ждёт пока первый завершит транзакцию, затем видит созданную бронь
 * и выбрасывает SlotNotAvailableException.
 *
 * Порядок действий внутри транзакции:
 *  1. FOR UPDATE — заблокировать строки ресурсов
 *  2. Проверить пересечение: бронь/hold в этом интервале для связанных ресурсов
 *  3. Если занято — выбросить исключение
 *  4. Создать Booking + BookingResources
 *  5. Записать начальный статус в BookingStatusHistory
 *  6. Снять hold (если был)
 */
class CreateBooking
{
    public function __construct(
        private readonly ConflictChecker $conflictChecker = new ConflictChecker(),
    ) {}

    /**
     * @throws SlotNotAvailableException
     */
    public function handle(CreateBookingDTO $dto): Booking
    {
        return DB::transaction(function () use ($dto) {

            // --- 1. SELECT FOR UPDATE: блокируем строки ресурсов ---
            $resources = Resource::whereIn('id', $dto->resourceIds)
                ->lockForUpdate()
                ->get();

            if ($resources->count() !== count($dto->resourceIds)) {
                throw new SlotNotAvailableException('Один или несколько ресурсов не найдены.');
            }

            // --- 2. Собрать все связанные ID (с учётом иерархии closure) ---
            $allRelatedIds = [];
            foreach ($resources as $resource) {
                $allRelatedIds = array_unique(
                    array_merge($allRelatedIds, $resource->relatedResourceIds())
                );
            }

            // --- 3. Проверить пересечение ---
            $this->conflictChecker->assertNoConflict(
                $allRelatedIds,
                $dto->startAt->toDateTimeString(),
                $dto->endAt->toDateTimeString(),
                $dto->branchId,
                $dto->ignoreHoldToken,
            );

            // --- 4. Создать бронь ---
            $booking = Booking::create([
                'club_id'             => $dto->clubId,
                'branch_id'           => $dto->branchId,
                'service_offering_id' => $dto->serviceOfferingId,
                'client_id'           => $dto->clientId,
                'admin_id'            => $dto->adminId,
                'start_at'            => $dto->startAt,
                'end_at'              => $dto->endAt,
                'status'              => BookingStatus::Confirmed,
                'amount_minor'        => $dto->amountMinor,
                'currency_code'       => $dto->currencyCode,
                'notes'               => $dto->notes,
            ]);

            // --- 5. Привязать ресурсы ---
            foreach ($dto->resourceIds as $resourceId) {
                BookingResource::create([
                    'booking_id'  => $booking->id,
                    'resource_id' => $resourceId,
                ]);
            }

            // --- 6. Записать статус в историю ---
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status'     => BookingStatus::Confirmed,
                'changed_by' => $dto->adminId,
                'comment'    => 'Бронь создана',
                'created_at' => now(),
            ]);

            // --- 7. Снять hold, если был (все ресурсы токена, не только первый) ---
            if ($dto->ignoreHoldToken) {
                ReservationHold::withoutGlobalScope('tenant')
                    ->where('session_token', $dto->ignoreHoldToken)
                    ->delete();
            }

            $booking->load(['bookingResources', 'statusHistory']);

            // --- 8. Событие → инвалидация кэша (after commit) ---
            BookingCreated::dispatch($booking);

            return $booking;
        });
    }
}
