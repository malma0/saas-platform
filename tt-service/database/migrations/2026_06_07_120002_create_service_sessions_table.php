<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();

            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('schedule_template_id')->constrained('schedule_templates')->cascadeOnDelete();

            // Материализованное время (UTC)
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');

            // Статус: scheduled | in_progress | completed | cancelled
            $table->string('status', 20)->default('scheduled');
            $table->boolean('is_cancelled')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'start_at']);
            $table->index(['schedule_template_id', 'start_at']);
            $table->index(['club_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_sessions');
    }
};
