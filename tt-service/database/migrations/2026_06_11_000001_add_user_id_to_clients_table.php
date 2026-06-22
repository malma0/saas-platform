<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Связь CRM-карточки клиента с учётной записью (Client API).
     * Клиент, зарегистрировавшийся через приложение, получает карточку
     * в CRM — его брони попадают в историю (ТЗ Блок 8).
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->after('club_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
