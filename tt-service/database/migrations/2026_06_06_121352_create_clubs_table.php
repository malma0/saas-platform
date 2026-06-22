<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Фаза 2/3: минимальная таблица clubs.
 * Полная схема (settings, branding) будет в Фазе 3.
 * Создаётся здесь, чтобы users.club_id мог ссылаться через FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clubs', function (Blueprint $table) {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->char('default_currency_code', 3)->default('RUB');
            $table->string('default_locale', 10)->default('ru');
            $table->string('timezone')->default('Europe/Moscow'); // IANA
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubs');
    }
};
