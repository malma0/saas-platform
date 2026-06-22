<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            // Предпочтения: preferred_resource_ids, preferred_time_slots, preferred_trainer_id и т.д.
            $table->string('key', 100);
            $table->jsonb('value');
            $table->timestamps();

            $table->unique(['client_id', 'key']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_preferences');
    }
};
