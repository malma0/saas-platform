<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_template_id')->constrained('schedule_templates')->cascadeOnDelete();

            // Дата конкретного занятия, к которому применяется исключение (локальное — дата старта)
            $table->date('session_date');

            // Тип: cancelled (отменено) | moved (перенесено)
            $table->string('type', 20);

            // Новое время (UTC) — заполняется при type=moved
            $table->timestampTz('new_start_at')->nullable();
            $table->timestampTz('new_end_at')->nullable();

            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['schedule_template_id', 'session_date']);
            $table->index(['schedule_template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_exceptions');
    }
};
