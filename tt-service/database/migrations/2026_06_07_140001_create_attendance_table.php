<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            // client_id nullable — бронь может быть без клиента (анонимная)
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();

            // present | absent | no_show
            $table->string('status', 20);

            // Кто отметил (admin/trainer)
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('marked_at');

            $table->text('comment')->nullable();

            $table->timestamps();

            // Одна запись посещаемости на бронь
            $table->unique('booking_id');

            $table->index(['client_id', 'status']);
            $table->index('marked_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
