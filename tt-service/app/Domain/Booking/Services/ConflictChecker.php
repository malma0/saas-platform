<?php

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Support\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;

/**
 * Проверка занятости слота для набора ресурсов.
 *
 * Формула пересечения интервалов: A.start < B.end AND A.end > B.start.
 *
 * Используется внутри транзакций CreateBooking / CreateHold —
 * после SELECT ... FOR UPDATE на строках ресурсов.
 */
class ConflictChecker
{
    /**
     * @param array<int> $relatedResourceIds ресурсы + их предки/потомки (closure)
     * @throws SlotNotAvailableException
     */
    public function assertNoConflict(
        array   $relatedResourceIds,
        string  $startAt,
        string  $endAt,
        int     $branchId,
        ?string $ignoreHoldToken = null,
    ): void {
        if (empty($relatedResourceIds)) {
            return;
        }

        $placeholders = implode(',', array_map('intval', $relatedResourceIds));

        // Брони (pending + confirmed)
        $conflictBooking = DB::selectOne(
            "SELECT br.id
             FROM booking_resources br
             JOIN bookings b ON b.id = br.booking_id
             WHERE br.resource_id IN ($placeholders)
               AND b.branch_id = ?
               AND b.status IN (?, ?)
               AND b.deleted_at IS NULL
               AND b.start_at < ?
               AND b.end_at   > ?
             LIMIT 1",
            [
                $branchId,
                BookingStatus::Pending->value,
                BookingStatus::Confirmed->value,
                $endAt,
                $startAt,
            ]
        );

        if ($conflictBooking !== null) {
            throw new SlotNotAvailableException(
                'Слот уже занят: пересечение с существующей бронью.'
            );
        }

        // Резервы регулярных занятий (session_resources)
        $conflictSession = DB::selectOne(
            "SELECT sr.id
             FROM session_resources sr
             JOIN service_sessions ss ON ss.id = sr.service_session_id
             JOIN schedule_templates st ON st.id = ss.schedule_template_id
             WHERE sr.resource_id IN ($placeholders)
               AND st.branch_id = ?
               AND ss.is_cancelled = false
               AND ss.deleted_at IS NULL
               AND ss.start_at < ?
               AND ss.end_at   > ?
             LIMIT 1",
            [$branchId, $endAt, $startAt]
        );

        if ($conflictSession !== null) {
            throw new SlotNotAvailableException(
                'Слот занят регулярным занятием.'
            );
        }

        // Активные holds (кроме собственного)
        $holdQuery = DB::table('reservation_holds')
            ->whereIn('resource_id', $relatedResourceIds)
            ->where('expires_at', '>', now())
            ->where('start_at', '<', $endAt)
            ->where('end_at',   '>', $startAt);

        if ($ignoreHoldToken) {
            $holdQuery->where('session_token', '!=', $ignoreHoldToken);
        }

        if ($holdQuery->exists()) {
            throw new SlotNotAvailableException(
                'Слот временно заблокирован: другой пользователь оформляет бронь.'
            );
        }
    }
}
