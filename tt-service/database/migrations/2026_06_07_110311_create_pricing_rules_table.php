<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Правила ценообразования.
         *
         * Цена зависит от:
         *  - периода действия (valid_from / valid_to)
         *  - дня недели (day_of_week: null = все дни)
         *  - диапазона времени (time_from / time_to: null = весь день)
         *
         * Алгоритм выбора цены (PricingService):
         *  1. Найти все правила для услуги, где сегодня попадает в valid_from..valid_to
         *  2. Отфильтровать по day_of_week (если задан)
         *  3. Отфильтровать по time_from..time_to (если задан)
         *  4. Взять самое специфичное (приоритет: time > day > base)
         *  5. Если ничего — использовать базовое правило (оба null)
         *
         * Деньги — ТОЛЬКО целые числа в минорных единицах (копейки).
         */
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offering_id')->constrained()->cascadeOnDelete();
            // Цена за единицу (за слот длительностью service_offering.duration_minutes)
            $table->bigInteger('amount_minor');          // копейки/центы, BIGINT
            $table->char('currency_code', 3)->default('RUB');
            // Период действия правила
            $table->date('valid_from')->nullable();      // null = с начала времён
            $table->date('valid_to')->nullable();        // null = бессрочно
            // Конкретный день недели (0=Вс..6=Сб), null = любой день
            $table->unsignedTinyInteger('day_of_week')->nullable();
            // Диапазон времени (локальное время филиала), null = весь день
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            // Приоритет: чем выше — тем важнее правило при конфликте
            $table->unsignedTinyInteger('priority')->default(0);
            $table->timestamps();

            $table->index('service_offering_id');
            $table->index(['service_offering_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
