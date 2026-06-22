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
        Schema::create('user_branch_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // branch_id — FK добавим после создания таблицы branches (Фаза 3)
            $table->unsignedBigInteger('branch_id');
            $table->timestamps();

            $table->unique(['user_id', 'branch_id']);
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_branch_access');
    }
};
