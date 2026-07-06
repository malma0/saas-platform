<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateBranch;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * DB-level гарантия от двойного бронирования (EXCLUDE-констрейнт).
 *
 * В отличие от BookingEngineTest, здесь мы НАМЕРЕННО обходим приложение
 * (ConflictChecker и SELECT FOR UPDATE) и пишем прямо в таблицы. Это
 * эмулирует худший случай: баг, прямой SQL или второй сервис, который не
 * знает про доменную логику. PostgreSQL обязан отбить пересечение сам.
 */
class BookingOverlapConstraintTest extends TestCase
{
    use RefreshDatabase;

    private Club            $club;
    private Branch          $branch;
    private Resource        $table1;
    private ServiceOffering $service;
    private CreateBooking   $createBooking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club          = Club::factory()->create();
        $this->createBooking = new CreateBooking();

        $this->branch = (new CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Тест', timezone: 'Europe/Moscow')
        );

        $type = ResourceType::create([
            'club_id' => $this->club->id, 'slug' => 'table', 'name' => 'Стол',
        ]);

        $this->table1 = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $type->id, name: 'Стол 1',
        ));

        $this->service = ServiceOffering::factory()->create([
            'club_id'          => $this->club->id,
            'duration_minutes' => 60,
        ]);
        PricingRule::create([
            'service_offering_id' => $this->service->id,
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);
    }

    /**
     * Прямая вставка пересекающейся брони в обход приложения → СУБД отбивает.
     */
    public function test_database_rejects_overlap_inserted_bypassing_app(): void
    {
        $start = Carbon::tomorrow()->setTime(14, 0)->utc();

        // Легитимная бронь через приложение: 14:00–15:00 на table1
        $this->createBooking->handle(new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $this->service->id,
            resourceIds:       [$this->table1->id],
            startAt:           $start,
            endAt:             $start->copy()->addHour(),
            amountMinor:       50000,
        ));

        // Худший случай: кто-то пишет пересекающуюся бронь напрямую в БД,
        // минуя ConflictChecker. Ждём нарушение EXCLUDE-констрейнта (SQLSTATE 23P01).
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('br_no_overlap');

        $bookingId = DB::table('bookings')->insertGetId([
            'public_id'           => (string) Str::ulid(),
            'club_id'             => $this->club->id,
            'branch_id'           => $this->branch->id,
            'service_offering_id' => $this->service->id,
            'start_at'            => $start->copy()->addMinutes(30), // 14:30 — пересекается
            'end_at'              => $start->copy()->addMinutes(90), // 15:30
            'status'              => 'confirmed',
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        // Триггер подтянет время/статус из брони, констрейнт отбросит пересечение.
        DB::table('booking_resources')->insert([
            'booking_id'  => $bookingId,
            'resource_id' => $this->table1->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    /**
     * Отмена брони освобождает слот и на уровне БД: после отмены прямая
     * вставка на то же время проходит (партиальный индекс игнорирует cancelled).
     */
    public function test_cancelled_booking_frees_slot_at_db_level(): void
    {
        $start = Carbon::tomorrow()->setTime(16, 0)->utc();

        $booking = $this->createBooking->handle(new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $this->service->id,
            resourceIds:       [$this->table1->id],
            startAt:           $start,
            endAt:             $start->copy()->addHour(),
            amountMinor:       50000,
        ));

        // Отменяем — триггер протянет status='cancelled' в booking_resources
        (new \App\Domain\Booking\Actions\CancelBooking())->handle($booking);

        // Теперь то же время свободно даже для прямой вставки
        $bookingId = DB::table('bookings')->insertGetId([
            'public_id'           => (string) Str::ulid(),
            'club_id'             => $this->club->id,
            'branch_id'           => $this->branch->id,
            'service_offering_id' => $this->service->id,
            'start_at'            => $start,
            'end_at'              => $start->copy()->addHour(),
            'status'              => 'confirmed',
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        DB::table('booking_resources')->insert([
            'booking_id'  => $bookingId,
            'resource_id' => $this->table1->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->assertDatabaseHas('booking_resources', [
            'booking_id'  => $bookingId,
            'resource_id' => $this->table1->id,
            'status'      => 'confirmed',
        ]);
    }
}
