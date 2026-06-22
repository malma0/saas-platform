<?php

declare(strict_types=1);

namespace App\Domain\Booking\Jobs;

use App\Domain\Booking\Cache\AvailabilityCache;
use App\Domain\Facilities\Models\Branch;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Прогревает кэш занятых ресурсов для всех активных филиалов
 * на ближайшие HORIZON_DAYS дней.
 *
 * Запускается по расписанию (ночью) через routes/console.php.
 * Результат: API /schedule и /bookings отдаёт данные мгновенно,
 * без запросов к БД на первом обращении.
 */
class WarmAvailabilityCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Горизонт прогрева — 7 дней */
    public const HORIZON_DAYS = 7;

    /** Шаг прогрева — 1 час */
    public const SLOT_HOURS = 1;

    public int $tries   = 2;
    public int $timeout = 120;

    public function handle(AvailabilityCache $cache): void
    {
        $branches = Branch::where('is_active', true)->get();
        $now      = Carbon::now()->startOfHour();

        foreach ($branches as $branch) {
            $cursor = $now->copy();
            $limit  = $now->copy()->addDays(self::HORIZON_DAYS);

            while ($cursor->lt($limit)) {
                $slotEnd = $cursor->copy()->addHours(self::SLOT_HOURS);

                // Прогреваем — если ключа нет, запрос идёт в БД и результат кэшируется
                $cache->getBusyResourceIds($branch->id, $cursor->copy(), $slotEnd->copy());

                $cursor->addHours(self::SLOT_HOURS);
            }
        }
    }
}
