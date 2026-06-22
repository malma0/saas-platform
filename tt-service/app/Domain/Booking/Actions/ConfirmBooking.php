<?php

declare(strict_types=1);

namespace App\Domain\Booking\Actions;

use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\BookingStatusHistory;
use App\Support\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Подтверждение брони администратором: pending → confirmed.
 *
 * Слот уже занят на этапе pending (бронь блокирует доступность),
 * поэтому повторная проверка конфликтов не требуется — только смена статуса.
 */
class ConfirmBooking
{
    public function handle(Booking $booking, ?int $confirmedBy = null, ?string $comment = null): Booking
    {
        if ($booking->isConfirmed()) {
            return $booking; // идемпотентно
        }

        if ($booking->isFinal()) {
            throw new RuntimeException(
                "Нельзя подтвердить бронь в статусе «{$booking->status->label()}»."
            );
        }

        return DB::transaction(function () use ($booking, $confirmedBy, $comment) {
            $booking->update(['status' => BookingStatus::Confirmed]);

            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'status'     => BookingStatus::Confirmed,
                'changed_by' => $confirmedBy,
                'comment'    => $comment ?? 'Бронь подтверждена',
                'created_at' => now(),
            ]);

            return $booking->fresh();
        });
    }
}
