<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Очищаем истёкшие holds каждые 5 минут
Schedule::command('booking:prune-holds')->everyFiveMinutes();

// Материализуем занятия регулярного расписания раз в день
Schedule::job(\App\Domain\Scheduling\Jobs\MaterializeSessionsJob::class)->dailyAt('03:00');

// Прогреваем кэш доступности для ближайших 7 дней (каждую ночь после материализации)
Schedule::job(\App\Domain\Booking\Jobs\WarmAvailabilityCacheJob::class)->dailyAt('03:30');
