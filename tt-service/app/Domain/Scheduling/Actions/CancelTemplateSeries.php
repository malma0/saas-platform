<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Booking\Cache\AvailabilityCache;
use App\Domain\Scheduling\Models\ScheduleTemplate;
use App\Domain\Scheduling\Models\ServiceSession;
use App\Support\Enums\SessionStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Отмена всей серии регулярных занятий, начиная с указанной даты.
 *
 * - Устанавливает end_date шаблона (завтра или переданная дата).
 * - Отменяет и удаляет все будущие незавершённые сессии + их ресурсы.
 * - Завершённые/прошедшие сессии не трогает.
 */
class CancelTemplateSeries
{
    public function handle(ScheduleTemplate $template, ?Carbon $cancelFrom = null): ScheduleTemplate
    {
        if ($template->trashed()) {
            throw new RuntimeException('Шаблон уже удалён.');
        }

        $cancelFrom ??= Carbon::now();

        return DB::transaction(function () use ($template, $cancelFrom) {
            // Ставим end_date — шаблон больше не генерирует занятия
            $template->update([
                'end_date' => $cancelFrom->toDateString(),
            ]);

            // Находим будущие незавершённые сессии
            $futureSessions = ServiceSession::withoutGlobalScopes()
                ->where('schedule_template_id', $template->id)
                ->where('start_at', '>=', $cancelFrom->utc())
                ->whereNotIn('status', [SessionStatus::Completed->value, SessionStatus::Cancelled->value])
                ->whereNull('deleted_at')
                ->get();

            foreach ($futureSessions as $session) {
                // Снимаем резерв ресурсов
                $session->resources()->detach();

                // Помечаем отменённой и удаляем (soft)
                $session->update([
                    'status'       => SessionStatus::Cancelled,
                    'is_cancelled' => true,
                ]);
                $session->delete();
            }

            // Освобождение будущих слотов → сбрасываем кэш доступности (Фаза 14)
            if ($futureSessions->isNotEmpty()) {
                app(AvailabilityCache::class)->invalidateBranch($template->branch_id);
            }

            return $template->fresh()->load('sessions');
        });
    }
}
