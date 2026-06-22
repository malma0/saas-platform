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
         * Клиенты клуба. Полная схема — в Фазе 8 (CRM).
         * Здесь минимум для FK bookings.client_id.
         */
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('club_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
