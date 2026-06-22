<?php

declare(strict_types=1);

namespace App\Domain\Booking\Cache;

use App\Domain\Booking\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Кэширующая обёртка над AvailabilityService.
 *
 * Стратегия:
 *  - getBusyResourceIds() кэшируется в Redis на TTL_SECONDS секунд
 *  - Ключ содержит branch_id + округлённые до 15-минутного слота границы
 *  - При бронировании/отмене CacheInvalidator сбрасывает все теги ветки
 *
 * ВАЖНО: кэш используется только для READ-операций (UI, API).
 * Финальная проверка при создании брони — всегда в транзакции с SELECT FOR UPDATE,
 * без обращения к кэшу.
 */
class AvailabilityCache
{
    /** TTL в секундах — 60 сек достаточно для снижения нагрузки */
    public const TTL_SECONDS = 60;

    /** Префикс ключей в Redis */
    public const KEY_PREFIX = 'avail:busy:';

    /** Тег для инвалидации по филиалу */
    public const TAG_PREFIX = 'avail_branch:';

    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * Получить занятые ресурсы — из кэша или реального запроса.
     *
     * @return array<int>
     */
    public function getBusyResourceIds(int $branchId, Carbon $startAt, Carbon $endAt): array
    {
        $key = $this->makeKey($branchId, $startAt, $endAt);

        // Используем теги только если драйвер их поддерживает (Redis, Memcached)
        // При array-кэше (тесты) теги недоступны — работаем без них
        if ($this->tagsSupported()) {
            return Cache::tags([$this->branchTag($branchId)])
                ->remember($key, self::TTL_SECONDS, fn () =>
                    $this->availability->getBusyResourceIds($branchId, $startAt, $endAt)
                );
        }

        return Cache::remember($key, self::TTL_SECONDS, fn () =>
            $this->availability->getBusyResourceIds($branchId, $startAt, $endAt)
        );
    }

    /**
     * Инвалидировать весь кэш занятости для филиала.
     * Вызывается при создании/отмене брони.
     */
    public function invalidateBranch(int $branchId): void
    {
        if ($this->tagsSupported()) {
            Cache::tags([$this->branchTag($branchId)])->flush();
        }
        // При array-driver ничего не делаем — TTL 60s сам справится
    }

    // -------------------------------------------------------------------------

    /**
     * Построить ключ кэша.
     *
     * Границы округляются до 15 минут, чтобы одинаковые запросы с разницей
     * в несколько секунд попадали в один и тот же кэш-слот.
     */
    public function makeKey(int $branchId, Carbon $startAt, Carbon $endAt): string
    {
        $s = $this->floor15($startAt);
        $e = $this->floor15($endAt);
        return self::KEY_PREFIX . "{$branchId}:{$s}:{$e}";
    }

    public function branchTag(int $branchId): string
    {
        return self::TAG_PREFIX . $branchId;
    }

    private function floor15(Carbon $dt): string
    {
        $minutes = (int) floor($dt->minute / 15) * 15;
        return $dt->copy()->minute($minutes)->second(0)->format('YmdHi');
    }

    private function tagsSupported(): bool
    {
        // Tags supported: redis, memcached. Not supported: array, file, database
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached'], true);
    }
}
