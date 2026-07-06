<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Тренеры клуба.
 *
 * Тренер — это бронируемая сущность: к каждому привязан «бэкенд»-ресурс типа
 * «тренер» (resource_id), через который движок броней (closure + конфликт-чек)
 * не даёт записать одного тренера на два занятия одновременно.
 *
 * Сама запись на тренировку — обычная бронь (CreateBooking) с услугой
 * «Индивидуальная тренировка», ресурсом тренера и ценой по ставке тренера.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 40)->unique();

            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            // Бэкенд-ресурс (тип «тренер»), которым тренер занимает время в сетке.
            // Nullable: создаётся автоматически в Coach::created (в т.ч. при добавлении из админки).
            $table->foreignId('resource_id')->nullable()->constrained('resources')->nullOnDelete();

            $table->string('name');
            $table->string('specialization')->nullable();        // «Техника и тактика»
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->string('rank', 20)->nullable();              // «МС», «КМС», «1 разряд»
            $table->text('bio')->nullable();
            $table->unsignedInteger('hourly_rate_minor')->default(0); // ставка за час, копейки
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};
