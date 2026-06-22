<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_session_id')->constrained('service_sessions')->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['service_session_id', 'resource_id']);
            $table->index(['resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_resources');
    }
};
