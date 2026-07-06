<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Факты физической занятости столов от Python-анализатора (или ручного ввода).
         *
         * Анализатор пишет сюда напрямую (или через sync-job).
         * Связь анализатора и системы — только через эту таблицу.
         * Никакого прямого вызова Python из Laravel и наоборот.
         */
        Schema::create('table_occupancy_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();

            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            // Длительность сессии в секундах — generated column. Выражение
            // зависит от драйвера: PostgreSQL (прод) умеет EXTRACT(EPOCH),
            // SQLite (локальная разработка) — julianday()*86400.
            $durationExpr = DB::getDriverName() === 'sqlite'
                ? '(CAST((julianday(end_at) - julianday(start_at)) * 86400 AS INTEGER))'
                : 'EXTRACT(EPOCH FROM (end_at - start_at))::INTEGER';
            $table->unsignedInteger('duration_seconds')->storedAs($durationExpr);

            // Источник: analyzer | manual | import
            $table->string('source', 30)->default('analyzer');

            // Внешний ID из системы анализатора (для дедупликации при синхронизации)
            $table->string('external_id')->nullable();

            $table->timestamps();

            $table->index(['club_id', 'branch_id', 'start_at']);
            $table->index(['resource_id', 'start_at']);
            $table->unique(['source', 'external_id'], 'uniq_occupancy_external');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_occupancy_sessions');
    }
};
