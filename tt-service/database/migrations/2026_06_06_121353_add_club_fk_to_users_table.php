<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляем FK с users.club_id → clubs.id.
 * Миграция отдельная, чтобы clubs уже существовала.
 * Суперадмин имеет club_id = null, поэтому FK nullable + nullOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('club_id')
                ->references('id')
                ->on('clubs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
        });
    }
};
