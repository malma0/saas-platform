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
        Schema::create('service_offerings', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            // Стандартная длительность одного слота в минутах
            $table->unsignedSmallInteger('duration_minutes');
            // Сколько человек вмещает одна бронь (например, групповая тренировка)
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_offerings');
    }
};
