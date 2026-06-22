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
         * Требования услуги к ресурсам.
         *
         * Пример: «Индивидуальная тренировка» требует:
         *   - 1 стол (resource_type slug='table')
         *   - 1 тренера (resource_type slug='coach')
         *
         * Количество quantity — сколько ресурсов данного типа нужно одновременно.
         */
        Schema::create('service_resource_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_type_id')->constrained('resource_types')->cascadeOnDelete();
            $table->unsignedTinyInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['service_offering_id', 'resource_type_id']);
            $table->index('service_offering_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_resource_requirements');
    }
};
