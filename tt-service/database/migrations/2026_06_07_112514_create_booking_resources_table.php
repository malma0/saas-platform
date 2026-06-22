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
         * Конкретные ресурсы, занятые данной бронью.
         * Одна бронь может занимать несколько ресурсов (стол + тренер).
         */
        Schema::create('booking_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['booking_id', 'resource_id']);
            $table->index('resource_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_resources');
    }
};
