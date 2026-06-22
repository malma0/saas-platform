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
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->default(0); // макс. человек
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['club_id', 'venue_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
