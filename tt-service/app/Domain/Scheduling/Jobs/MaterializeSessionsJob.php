<?php

namespace App\Domain\Scheduling\Jobs;

use App\Domain\Scheduling\Models\ScheduleTemplate;
use App\Domain\Scheduling\Services\SessionMaterializer;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Ежедневный job: расширяет горизонт материализованных занятий до +90 дней.
 *
 * Запускается из Scheduler раз в день.
 * Идемпотентен: повторный запуск не создаёт дубликаты.
 */
class MaterializeSessionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(SessionMaterializer $materializer): void
    {
        $now = Carbon::now();

        // Обрабатываем все активные шаблоны (не удалённые, у которых end_date не прошёл)
        ScheduleTemplate::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $now->toDateString());
            })
            ->chunkById(50, function ($templates) use ($materializer) {
                foreach ($templates as $template) {
                    try {
                        // Берём ID ресурсов из первой сессии шаблона (они одинаковы для всей серии)
                        $resourceIds = $template->sessions()
                            ->withoutGlobalScopes()
                            ->first()
                            ?->resources()
                            ->pluck('resources.id')
                            ->toArray() ?? [];

                        $new = $materializer->materialize($template, $resourceIds);

                        if (!empty($new)) {
                            Log::info("[MaterializeSessions] Template #{$template->id}: создано " . count($new) . ' сессий.');
                        }
                    } catch (\Throwable $e) {
                        Log::error("[MaterializeSessions] Template #{$template->id}: {$e->getMessage()}");
                    }
                }
            });
    }
}
