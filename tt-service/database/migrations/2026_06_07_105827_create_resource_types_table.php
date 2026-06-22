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
        Schema::create('resource_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            // slug — строковый идентификатор типа: table|hall|coach|room|venue_whole
            $table->string('slug', 50);
            $table->string('name');
            $table->timestamps();

            $table->unique(['club_id', 'slug']);
            $table->index('club_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_types');
    }
};
