<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();

            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();

            $table->bigInteger('amount_minor');
            $table->char('currency_code', 3)->default('RUB');

            $table->text('reason')->nullable();

            // Статус возврата: pending | completed | failed
            $table->string('status', 20)->default('pending');

            // ID возврата на стороне провайдера
            $table->string('provider_refund_id')->nullable();

            // Кто инициировал
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
