<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DB-level «ремень безопасности» против двойного бронирования.
     *
     * Конфликт-чек в приложении (ConflictChecker + SELECT FOR UPDATE) остаётся
     * первичной защитой. Эта миграция добавляет НЕЗАВИСИМУЮ гарантию на уровне
     * PostgreSQL: даже если бронь вставят в обход CreateBooking (прямой SQL,
     * баг, второй сервис) — СУБД физически не даст создать пересечение по
     * одному и тому же ресурсу.
     *
     * Реализация:
     *  1. На booking_resources денормализуем время/статус брони (start_at,
     *     end_at, status, deleted_at) — это нужно, т.к. EXCLUDE-констрейнт
     *     работает в пределах одной таблицы, а время хранится в bookings.
     *  2. Триггеры держат эти поля в синхроне с родительской bookings —
     *     код доменных Action'ов трогать не нужно.
     *  3. EXCLUDE USING gist гарантирует: для активной брони
     *     (pending|confirmed, не удалённой) интервалы по одному resource_id
     *     не пересекаются.
     *
     * ВАЖНО: констрейнт ловит пересечения по ТОМУ ЖЕ ресурсу. Иерархические
     * конфликты (бронь зала vs бронь стола внутри) по-прежнему закрывает
     * только приложение через closure-таблицу — это осознанное разделение.
     */
    public function up(): void
    {
        // EXCLUDE/gist/plpgsql — фичи PostgreSQL. На других драйверах (SQLite в
        // локальной разработке) пропускаем: защиту от двойной брони держит
        // приложение (ConflictChecker + SELECT FOR UPDATE).
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        Schema::table('booking_resources', function (Blueprint $table) {
            $table->timestampTz('start_at')->nullable();
            $table->timestampTz('end_at')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestampTz('deleted_at')->nullable();
        });

        // Бэкфилл существующих строк из родительских броней
        DB::statement(<<<'SQL'
            UPDATE booking_resources br
               SET start_at   = b.start_at,
                   end_at     = b.end_at,
                   status     = b.status,
                   deleted_at = b.deleted_at
              FROM bookings b
             WHERE b.id = br.booking_id
        SQL);

        // Триггер 1: при вставке строки ресурса подтянуть данные из брони
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION br_sync_from_booking() RETURNS trigger AS $$
            BEGIN
                SELECT b.start_at, b.end_at, b.status, b.deleted_at
                  INTO NEW.start_at, NEW.end_at, NEW.status, NEW.deleted_at
                  FROM bookings b
                 WHERE b.id = NEW.booking_id;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_br_sync_insert
                BEFORE INSERT ON booking_resources
                FOR EACH ROW EXECUTE FUNCTION br_sync_from_booking();
        SQL);

        // Триггер 2: при изменении брони (время/статус/soft-delete) протянуть в ресурсы
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION booking_propagate_to_resources() RETURNS trigger AS $$
            BEGIN
                IF NEW.start_at   IS DISTINCT FROM OLD.start_at
                OR NEW.end_at     IS DISTINCT FROM OLD.end_at
                OR NEW.status     IS DISTINCT FROM OLD.status
                OR NEW.deleted_at IS DISTINCT FROM OLD.deleted_at THEN
                    UPDATE booking_resources
                       SET start_at   = NEW.start_at,
                           end_at     = NEW.end_at,
                           status     = NEW.status,
                           deleted_at = NEW.deleted_at
                     WHERE booking_id = NEW.id;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_booking_propagate
                AFTER UPDATE ON bookings
                FOR EACH ROW EXECUTE FUNCTION booking_propagate_to_resources();
        SQL);

        // EXCLUDE: никаких пересечений активных броней по одному ресурсу.
        // tstzrange [) — полуоткрытый: соседние брони (11:00 конец / 11:00 старт) НЕ конфликтуют.
        DB::statement(<<<'SQL'
            ALTER TABLE booking_resources
              ADD CONSTRAINT br_no_overlap
              EXCLUDE USING gist (
                  resource_id WITH =,
                  tstzrange(start_at, end_at) WITH &&
              )
              WHERE (status IN ('pending', 'confirmed') AND deleted_at IS NULL)
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE booking_resources DROP CONSTRAINT IF EXISTS br_no_overlap');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_booking_propagate ON bookings');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_br_sync_insert ON booking_resources');
        DB::unprepared('DROP FUNCTION IF EXISTS booking_propagate_to_resources()');
        DB::unprepared('DROP FUNCTION IF EXISTS br_sync_from_booking()');

        Schema::table('booking_resources', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at', 'status', 'deleted_at']);
        });
    }
};
