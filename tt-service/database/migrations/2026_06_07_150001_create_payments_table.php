<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();

            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();

            $table->bigInteger('amount_minor');           // в копейках
            $table->char('currency_code', 3)->default('RUB');

            // pending | paid | refunded | failed | cancelled
            $table->string('status', 20)->default('pending');

            // Провайдер: manual | yookassa | stripe | …
            $table->string('provider', 30)->default('manual');

            // ID платежа на стороне провайдера (nullable — для ручных)
            $table->string('provider_payment_id')->nullable();

            // Кто принял оплату (для ручных платежей)
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['club_id', 'status']);
            $table->index(['booking_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
