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
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('club_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            // Часовой пояс филиала (IANA): Asia/Novosibirsk, Europe/Moscow и т.д.
            // Хранится отдельно — филиал может быть в другом городе.
            $table->string('timezone')->default('Europe/Moscow');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('club_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
