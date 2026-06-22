<?php

namespace App\Providers;

use App\Domain\Booking\Cache\AvailabilityCache;
use App\Domain\Booking\Events\BookingCancelled;
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\Listeners\InvalidateAvailabilityCache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Регистрируем AvailabilityCache как singleton
        $this->app->singleton(AvailabilityCache::class);
    }

    public function boot(): void
    {
        /**
         * Superadmin обходит все проверки Gate — видит и может всё.
         * club_id = null — признак суперадмина.
         */
        Gate::before(function (\App\Models\User $user, string $ability) {
            if ($user->hasRole('superadmin')) {
                return true;
            }
        });

        // Инвалидация кэша доступности при изменениях бронирований
        Event::listen(BookingCreated::class, [InvalidateAvailabilityCache::class, 'handleCreated']);
        Event::listen(BookingCancelled::class, [InvalidateAvailabilityCache::class, 'handleCancelled']);
    }
}
