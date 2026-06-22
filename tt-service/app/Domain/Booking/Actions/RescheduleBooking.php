<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingStatusHistory;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Перенос брони на новое время.
 *
 * Стратегия: отменяем старую, создаём новую.
 * Это гарантирует что история изменений полная,
 * и новый конфликт-чек проходит чисто.
 */
class RescheduleBooking
{
    public function __construct(
        private readonly CancelBooking $cancelBooking,
        private readonly CreateBooking $createBooking,
    ) {}

    /**
     * @param array<int> $newResourceIds — новые ресурсы (могут совпадать со старыми)
     * @throws RuntimeException | SlotNotAvailableException
     */
    public function handle(
        Booking $booking,
        Carbon  $newStartAt,
        Carbon  $newEndAt,
        array   $newResourceIds,
        ?int    $rescheduledBy = null,
    ): Booking {
        if ($booking->isFinal()) {
            throw new RuntimeException(
                "Нельзя перенести бронь в статусе «{$booking->status->label()}»."
            );
        }

        return DB::transaction(function () use (
            $booking, $newStartAt, $newEndAt, $newResourceIds, $rescheduledBy
        ) {
            // 1. Отменяем старую бронь
            $this->cancelBooking->handle($booking, $rescheduledBy, 'Перенос: отменена старая бронь');

            // 2. Создаём новую бронь с теми же деньгами (цена зафиксирована)
            $newBooking = $this->createBooking->handle(new CreateBookingDTO(
                clubId:            $booking->club_id,
                branchId:          $booking->branch_id,
                serviceOfferingId: $booking->service_offering_id,
                resourceIds:       $newResourceIds,
                startAt:           $newStartAt,
                endAt:             $newEndAt,
                amountMinor:       $booking->amount_minor,
                currencyCode:      $booking->currency_code,
                clientId:          $booking->client_id,
                adminId:           $rescheduledBy,
                notes:             $booking->notes,
            ));

            // 3. Записываем комментарий о переносе
            BookingStatusHistory::create([
                'booking_id' => $newBooking->id,
                'status'     => BookingStatus::Confirmed,
                'changed_by' => $rescheduledBy,
                'comment'    => "Перенесена с брони #{$booking->id}",
                'created_at' => now(),
            ]);

            return $newBooking;
        });
    }
}
