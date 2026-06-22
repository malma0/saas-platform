<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();

            // Тип операции: charge | refund | chargeback | correction
            $table->string('type', 30);

            $table->bigInteger('amount_minor');
            $table->char('currency_code', 3)->default('RUB');

            // Сырой ответ от провайдера (JSON)
            $table->jsonb('raw_payload')->nullable();

            $table->timestamps();

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
