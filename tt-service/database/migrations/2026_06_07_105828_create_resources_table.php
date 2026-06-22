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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_type_id')->constrained('resource_types')->cascadeOnDelete();
            // Иерархия: parent_id = null означает корневой ресурс
            $table->foreignId('parent_id')->nullable()->constrained('resources')->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'branch_id']);
            $table->index(['club_id', 'is_active']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
