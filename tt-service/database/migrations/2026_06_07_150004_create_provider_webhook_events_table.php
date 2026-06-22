<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_webhook_events', function (Blueprint $table) {
            $table->id();

            // Провайдер: yookassa | stripe | …
            $table->string('provider', 30);

            // Уникальный ID события на стороне провайдера — защита от повторной обработки
            $table->string('event_id', 255);

            $table->jsonb('payload');

            // Статус обработки: received | processed | failed | skipped
            $table->string('status', 20)->default('received');
            $table->text('error')->nullable();

            $table->timestampTz('processed_at')->nullable();
            $table->timestamps();

            // Ключевое ограничение: (provider, event_id) уникально
            $table->unique(['provider', 'event_id']);
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_webhook_events');
    }
};
