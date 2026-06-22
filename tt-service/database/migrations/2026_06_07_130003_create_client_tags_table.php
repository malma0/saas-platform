<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Справочник тегов клуба
        Schema::create('client_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->string('name', 50);
            // Цвет для UI (hex)
            $table->string('color', 7)->default('#6366f1');
            $table->timestamps();

            $table->unique(['club_id', 'name']);
        });

        // Pivot: клиент ↔ тег
        Schema::create('client_tag_pivot', function (Blueprint $table) {
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('client_tag_id')->constrained('client_tags')->cascadeOnDelete();
            $table->primary(['client_id', 'client_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_tag_pivot');
        Schema::dropIfExists('client_tags');
    }
};
