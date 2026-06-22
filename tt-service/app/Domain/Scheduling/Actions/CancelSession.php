<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Booking\Cache\AvailabilityCache;
use App\Domain\Scheduling\Models\ScheduleException;
use App\Domain\Scheduling\Models\ServiceSession;
use App\Support\Enums\SessionStatus;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Отмена одного занятия из серии.
 *
 * Создаёт ScheduleException(type=cancelled) и помечает ServiceSession как отменённую.
 * Резерв ресурсов снимается через soft-delete сессии (AvailabilityService игнорирует deleted).
 */
class CancelSession
{
    public function handle(ServiceSession $session, ?string $comment = null): ServiceSession
    {
        if ($session->isCancelled()) {
            throw new RuntimeException('Занятие уже отменено.');
        }

        return DB::transaction(function () use ($session, $comment) {
            // Записываем исключение
            $sessionDate = $session->start_at
                ->setTimezone($session->template->branch->timezone ?? 'UTC')
                ->toDateString();

            ScheduleException::updateOrCreate(
                [
                    'schedule_template_id' => $session->schedule_template_id,
                    'session_date'         => $sessionDate,
                ],
                [
                    'type'    => 'cancelled',
                    'comment' => $comment,
                ]
            );

            // Помечаем сессию отменённой
            $session->update([
                'status'       => SessionStatus::Cancelled,
                'is_cancelled' => true,
            ]);

            // Снимаем резерв ресурсов (detach)
            $session->resources()->detach();

            // Освобождение слота → сбрасываем кэш доступности (Фаза 14)
            $branchId = $session->template?->branch_id;
            if ($branchId) {
                app(AvailabilityCache::class)->invalidateBranch($branchId);
            }

            return $session->fresh();
        });
    }
}
