<?php

namespace App\Domain\Services\Services;

use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Money\Money;
use Carbon\Carbon;
use RuntimeException;

/**
 * Сервис расчёта цены услуги.
 *
 * Алгоритм выбора правила:
 *  1. Загрузить все активные правила для услуги (отсортированные по priority DESC).
 *  2. Из них оставить только те, у которых appliesTo($localDateTime) = true.
 *  3. Взять правило с наивысшим priority.
 *  4. Если несколько с одинаковым priority — взять самое специфичное
 *     (time_from задан > day_of_week задан > базовое).
 *  5. Если правил нет — выбросить исключение (услуга без цены не должна бронироваться).
 */
class PricingService
{
    /**
     * Рассчитать цену услуги на указанный момент.
     *
     * @param ServiceOffering $service
     * @param Carbon          $localDateTime локальное время начала брони в часовом поясе филиала
     * @return Money
     *
     * @throws RuntimeException если нет подходящего правила
     */
    public function priceFor(ServiceOffering $service, Carbon $localDateTime): Money
    {
        $rule = $this->findRule($service, $localDateTime);

        if ($rule === null) {
            throw new RuntimeException(
                "Нет активного правила цены для услуги «{$service->name}» на {$localDateTime->toDateTimeString()}"
            );
        }

        return $rule->money();
    }

    /**
     * Найти подходящее правило цены (или null если нет ни одного).
     */
    public function findRule(ServiceOffering $service, Carbon $localDateTime): ?PricingRule
    {
        // Загружаем все правила, сортируем priority DESC, затем по специфичности
        $rules = $service->pricingRules()
            ->orderByDesc('priority')
            ->get();

        $applicable = $rules->filter(fn (PricingRule $rule) => $rule->appliesTo($localDateTime));

        if ($applicable->isEmpty()) {
            return null;
        }

        // Среди применимых — самое специфичное: больше заданных полей = выше приоритет
        return $applicable->sortByDesc(fn (PricingRule $rule) => [
            $rule->priority,
            (int) ($rule->time_from !== null),  // time диапазон задан
            (int) ($rule->day_of_week !== null), // день задан
        ])->first();
    }

    /**
     * Получить базовую цену услуги (правило без ограничений по дню/времени).
     * Используется для отображения «цена от».
     */
    public function basePrice(ServiceOffering $service): ?Money
    {
        $rule = $service->pricingRules()
            ->whereNull('day_of_week')
            ->whereNull('time_from')
            ->whereNull('valid_from')
            ->whereNull('valid_to')
            ->orderByDesc('priority')
            ->first();

        return $rule?->money();
    }
}
