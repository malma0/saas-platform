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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_offering_id')->constrained()->cascadeOnDelete();
            // client_id nullable — бронь может создать администратор без привязки к клиенту
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            // admin_id — кто создал бронь (если через админку)
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('start_at');  // UTC
            $table->timestampTz('end_at');    // UTC
            // Статус: pending|confirmed|cancelled|completed|no_show
            $table->string('status', 20)->default('pending');
            // Деньги — только целые числа (копейки)
            $table->bigInteger('amount_minor');
            $table->char('currency_code', 3)->default('RUB');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'status']);
            $table->index(['club_id', 'start_at', 'end_at']);
            $table->index(['branch_id', 'start_at']);
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
