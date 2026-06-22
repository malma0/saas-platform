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
        /**
         * Closure-таблица для иерархии ресурсов.
         *
         * Каждая строка = путь от ancestor до descendant.
         * Узел хранит сам себя: ancestor = descendant, depth = 0.
         *
         * Пример: зал → стол1, стол2
         *   (hall, hall, 0)
         *   (hall, table1, 1)
         *   (hall, table2, 1)
         *   (table1, table1, 0)
         *   (table2, table2, 0)
         *
         * Запрос "все связанные ресурсы для конфликт-чека":
         *   SELECT descendant_id FROM resource_closure WHERE ancestor_id IN (...)
         *   UNION
         *   SELECT ancestor_id FROM resource_closure WHERE descendant_id IN (...)
         */
        Schema::create('resource_closure', function (Blueprint $table) {
            $table->foreignId('ancestor_id')->constrained('resources')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('resources')->cascadeOnDelete();
            $table->unsignedTinyInteger('depth'); // 0 = сам себя, 1 = прямой потомок, ...

            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index('descendant_id');
            $table->index('ancestor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_closure');
    }
};
