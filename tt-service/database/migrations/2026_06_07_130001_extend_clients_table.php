<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Дата рождения
            $table->date('birth_date')->nullable()->after('email');
            // Пол: male | female | other
            $table->string('gender', 10)->nullable()->after('birth_date');
            // Заметка (быстрая) прямо в карточке
            $table->text('quick_note')->nullable()->after('gender');
            // Источник привлечения
            $table->string('source', 50)->nullable()->after('quick_note');
            // Заблокирован ли клиент
            $table->boolean('is_blocked')->default(false)->after('source');
            $table->text('block_reason')->nullable()->after('is_blocked');
            // Кто создал карточку
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('block_reason');

            $table->index(['club_id', 'phone']);
            $table->index(['club_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'birth_date', 'gender', 'quick_note', 'source',
                'is_blocked', 'block_reason', 'created_by',
            ]);
        });
    }
};
