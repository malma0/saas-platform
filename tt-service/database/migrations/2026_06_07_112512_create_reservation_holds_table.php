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
         * Временная блокировка слота пока клиент оформляет/оплачивает бронь.
         *
         * Hold живёт до expires_at. После истечения — снимается Job'ом.
         * При подтверждении брони hold удаляется явно.
         *
         * Конфликт-чек ВСЕГДА проверяет: active holds (expires_at > now()) + bookings.
         */
        Schema::create('reservation_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('start_at');   // UTC
            $table->timestampTz('end_at');     // UTC
            $table->timestampTz('expires_at'); // UTC — когда hold истекает
            // Токен сессии/пользователя — владелец hold'а
            $table->string('session_token', 64)->index();
            $table->timestamps();

            $table->index(['resource_id', 'expires_at']);
            $table->index(['club_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_holds');
    }
};
