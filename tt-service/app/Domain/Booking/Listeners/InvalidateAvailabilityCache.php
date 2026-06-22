<?php

declare(strict_types=1);

namespace App\Domain\Booking\Listeners;

use App\Domain\Booking\Cache\AvailabilityCache;
use App\Domain\Booking\Events\BookingCancelled;
use App\Domain\Booking\Events\BookingCreated;

/**
 * Инвалидирует кэш занятости при изменении броней.
 *
 * Слушает оба события: BookingCreated и BookingCancelled.
 * Сбрасывает весь тег филиала — быстрее, чем вычислять конкретные ключи.
 */
class InvalidateAvailabilityCache
{
    public function __construct(
        private readonly AvailabilityCache $cache,
    ) {}

    public function handleCreated(BookingCreated $event): void
    {
        $this->cache->invalidateBranch($event->booking->branch_id);
    }

    public function handleCancelled(BookingCancelled $event): void
    {
        $this->cache->invalidateBranch($event->booking->branch_id);
    }
}
