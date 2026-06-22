<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_templates', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();

            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('service_offering_id')->constrained('service_offerings')->cascadeOnDelete();

            // RRULE (iCal format): FREQ=WEEKLY;BYDAY=TU,TH
            $table->string('recurrence_rule', 500);

            // Диапазон действия шаблона
            $table->date('start_date');
            $table->date('end_date')->nullable();   // null = бессрочно

            // Время начала занятия (локальное — часовой пояс филиала)
            $table->time('start_time');
            $table->unsignedSmallInteger('duration_minutes');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'branch_id']);
            $table->index(['club_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_templates');
    }
};
