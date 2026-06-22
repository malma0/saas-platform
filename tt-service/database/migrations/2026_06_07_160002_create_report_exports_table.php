<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 26)->unique();

            $table->foreignId('club_id')->constrained('clubs')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();

            // Тип отчёта: bookings | revenue | occupancy | attendance
            $table->string('report_type', 30);

            // Параметры запроса (JSON): date_from, date_to, branch_id, …
            $table->jsonb('params');

            // Статус: pending | processing | ready | failed
            $table->string('status', 20)->default('pending');

            // Путь к готовому файлу (локальный или S3 key)
            $table->string('file_path')->nullable();
            $table->string('file_format', 10)->default('csv'); // csv | xlsx

            $table->text('error')->nullable();

            $table->timestampTz('ready_at')->nullable();
            $table->timestamps();

            $table->index(['club_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
