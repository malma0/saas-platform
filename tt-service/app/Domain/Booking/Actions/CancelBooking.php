<?php

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Events\BookingCancelled;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingStatusHistory;
use App\Support\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CancelBooking
{
    /**
     * @throws RuntimeException если бронь уже в финальном статусе
     */
    public function handle(Booking $booking, ?int $cancelledBy = null, ?string $comment = null): Booking
    {
        if ($booking->isFinal()) {
            throw new RuntimeException(
                "Нельзя отменить бронь в статусе «{$booking->status->label()}»."
            );
        }

        return DB::transaction(function () use ($booking, $cancelledBy, $comment) {
            $booking->update(['status' => BookingStatus::Cancelled]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status'     => BookingStatus::Cancelled,
                'changed_by' => $cancelledBy,
                'comment'    => $comment ?? 'Отменено',
                'created_at' => now(),
            ]);

            $fresh = $booking->fresh();

            // Событие → инвалидация кэша
            BookingCancelled::dispatch($fresh);

            return $fresh;
        });
    }
}
