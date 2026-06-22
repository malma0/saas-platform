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
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            // 0 = воскресенье, 1 = понедельник, ... 6 = суббота (ISO: 1=Mon..7=Sun)
            $table->unsignedTinyInteger('day_of_week'); // 0–6
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('is_closed')->default(false); // выходной день
            $table->timestamps();

            // Один филиал — один слот на каждый день недели
            $table->unique(['branch_id', 'day_of_week']);
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
