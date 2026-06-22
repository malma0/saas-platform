<?php

namespace App\Domain\Attendance\Actions;

use App\Domain\Attendance\Models\Attendance;
use App\Domain\Booking\Models\Booking;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Отметить факт посещения / неявку по брони.
 *
 * Правила:
 *  - Бронь должна быть в статусе confirmed или completed.
 *  - no_show автоматически меняет статус брони на no_show.
 *  - Повторный вызов — обновляет отметку (переотметка).
 */
class MarkAttendance
{
    public function handle(
        Booking          $booking,
        AttendanceStatus $status,
        ?int             $markedBy = null,
        ?string          $comment  = null,
    ): Attendance {
        // Бронь должна быть подтверждена или завершена
        if (!in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Completed])) {
            throw new RuntimeException(
                "Нельзя отметить посещение для брони в статусе «{$booking->status->label()}»."
            );
        }

        return DB::transaction(function () use ($booking, $status, $markedBy, $comment) {
            $attendance = Attendance::updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'client_id' => $booking->client_id,
                    'status'    => $status,
                    'marked_by' => $markedBy,
                    'marked_at' => Carbon::now(),
                    'comment'   => $comment,
                ]
            );

            // no_show → меняем статус брони
            if ($status === AttendanceStatus::NoShow) {
                $booking->update(['status' => BookingStatus::NoShow]);
            }

            // present → завершаем бронь
            if ($status === AttendanceStatus::Present && $booking->status === BookingStatus::Confirmed) {
                $booking->update(['status' => BookingStatus::Completed]);
            }

            return $attendance;
        });
    }
}
