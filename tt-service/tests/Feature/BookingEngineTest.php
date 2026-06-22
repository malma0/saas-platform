<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CancelBooking;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\Actions\RescheduleBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Exceptions\SlotNotAvailableException;
use App\Domain\Booking\Models\Booking;
use App\Domain\Booking\Models\ReservationHold;
use App\Domain\Booking\Services\AvailabilityService;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Models\ServiceOffering;
use App\Domain\Services\Models\PricingRule;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 6 — Booking Engine.
 *
 * ✅ Ключевой тест: двойная бронь невозможна (доказано тестом).
 */
class BookingEngineTest extends TestCase
{
    use RefreshDatabase;

    private Club            $club;
    private Branch          $branch;
    private ResourceType    $typeTable;
    private Resource        $table1;
    private Resource        $table2;
    private ServiceOffering $service;
    private CreateBooking   $createBooking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club        = Club::factory()->create();
        $this->createBooking = new CreateBooking();

        // Создаём филиал с рабочим временем
        $this->branch = (new \App\Domain\Facilities\Actions\CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Тест', timezone: 'Europe/Moscow')
        );

        $this->typeTable = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'table', 'name' => 'Стол']);

        $createResource = new CreateResource();
        $this->table1 = $createResource->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $this->typeTable->id, name: 'Стол 1'
        ));
        $this->table2 = $createResource->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $this->typeTable->id, name: 'Стол 2'
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeDTO(Resource $resource, Carbon $start, ?Carbon $end = null): CreateBookingDTO
    {
        return new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $this->service->id,
            resourceIds:       [$resource->id],
            startAt:           $start,
            endAt:             $end ?? $start->copy()->addHour(),
            amountMinor:       50000,
        );
    }

    // -------------------------------------------------------------------------
    // Базовое бронирование
    // -------------------------------------------------------------------------

    public function test_create_booking_successfully(): void
    {
        $start = Carbon::tomorrow()->setTime(10, 0)->utc();
        $booking = $this->createBooking->handle($this->makeDTO($this->table1, $start));

        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertEquals(BookingStatus::Confirmed, $booking->status);
        $this->assertNotEmpty($booking->public_id);
        $this->assertCount(1, $booking->bookingResources);
        $this->assertCount(1, $booking->statusHistory);
    }

    public function test_booking_has_ulid_public_id(): void
    {
        $start   = Carbon::tomorrow()->setTime(11, 0)->utc();
        $booking = $this->createBooking->handle($this->makeDTO($this->table1, $start));
        $this->assertMatchesRegularExpression('/^[0-9a-z]{26}$/', $booking->public_id);
    }

    // -------------------------------------------------------------------------
    // ✅ ГЛАВНЫЙ ТЕСТ: двойное бронирование невозможно
    // -------------------------------------------------------------------------

    /**
     * Два запроса на один слот — только один проходит.
     * Второй получает SlotNotAvailableException.
     *
     * Это эмулирует race condition: первая бронь создана,
     * второй CreateBooking видит её внутри транзакции.
     */
    public function test_double_booking_is_impossible(): void
    {
        $start = Carbon::tomorrow()->setTime(14, 0)->utc();
        $dto   = $this->makeDTO($this->table1, $start);

        // Первая бронь — должна пройти
        $booking1 = $this->createBooking->handle($dto);
        $this->assertEquals(BookingStatus::Confirmed, $booking1->status);

        // Вторая бронь — тот же ресурс, то же время → ДОЛЖНА упасть
        $this->expectException(SlotNotAvailableException::class);
        $this->createBooking->handle($dto);
    }

    /**
     * Пересечение с существующей бронью (не точное совпадение, а частичное).
     */
    public function test_overlapping_booking_is_rejected(): void
    {
        $start = Carbon::tomorrow()->setTime(10, 0)->utc();

        // Бронь 10:00–11:00
        $this->createBooking->handle($this->makeDTO($this->table1, $start, $start->copy()->addHour()));

        // Попытка 10:30–11:30 — пересекается
        $this->expectException(SlotNotAvailableException::class);
        $this->createBooking->handle(
            $this->makeDTO($this->table1, $start->copy()->addMinutes(30), $start->copy()->addMinutes(90))
        );
    }

    /**
     * Разные ресурсы — не конфликтуют.
     */
    public function test_different_resources_do_not_conflict(): void
    {
        $start = Carbon::tomorrow()->setTime(10, 0)->utc();

        $b1 = $this->createBooking->handle($this->makeDTO($this->table1, $start));
        $b2 = $this->createBooking->handle($this->makeDTO($this->table2, $start));

        $this->assertEquals(BookingStatus::Confirmed, $b1->status);
        $this->assertEquals(BookingStatus::Confirmed, $b2->status);
    }

    /**
     * Соседние (не пересекающиеся) брони — не конфликтуют.
     */
    public function test_adjacent_bookings_do_not_conflict(): void
    {
        $start = Carbon::tomorrow()->setTime(10, 0)->utc();

        // 10:00–11:00 и 11:00–12:00 — должны оба пройти
        $b1 = $this->createBooking->handle($this->makeDTO($this->table1, $start, $start->copy()->addHour()));
        $b2 = $this->createBooking->handle($this->makeDTO($this->table1, $start->copy()->addHour(), $start->copy()->addHours(2)));

        $this->assertEquals(BookingStatus::Confirmed, $b1->status);
        $this->assertEquals(BookingStatus::Confirmed, $b2->status);
    }

    // -------------------------------------------------------------------------
    // Иерархия ресурсов — конфликт через closure
    // -------------------------------------------------------------------------

    /**
     * Если стол занят — бронь на весь зал (родителя) должна быть отклонена.
     */
    public function test_booking_parent_hall_blocked_when_child_table_booked(): void
    {
        $typeHall = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'hall', 'name' => 'Зал']);
        $hall = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $typeHall->id, name: 'Зал А'
        ));
        $table = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $this->typeTable->id, name: 'Стол в зале', parentId: $hall->id
        ));

        $start = Carbon::tomorrow()->setTime(15, 0)->utc();

        // Бронируем стол
        $this->createBooking->handle($this->makeDTO($table, $start));

        // Пытаемся забронировать зал (родитель) — должно быть отклонено
        $this->expectException(SlotNotAvailableException::class);
        $this->createBooking->handle($this->makeDTO($hall, $start));
    }

    /**
     * Если зал занят — бронь стола внутри должна быть отклонена.
     */
    public function test_booking_child_table_blocked_when_parent_hall_booked(): void
    {
        $typeHall = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'hall2', 'name' => 'Зал']);
        $hall = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $typeHall->id, name: 'Зал Б'
        ));
        $table = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $this->typeTable->id, name: 'Стол в зале Б', parentId: $hall->id
        ));

        $start = Carbon::tomorrow()->setTime(16, 0)->utc();

        // Бронируем зал
        $this->createBooking->handle($this->makeDTO($hall, $start));

        // Пытаемся забронировать стол внутри — должно быть отклонено
        $this->expectException(SlotNotAvailableException::class);
        $this->createBooking->handle($this->makeDTO($table, $start));
    }

    // -------------------------------------------------------------------------
    // ReservationHold
    // -------------------------------------------------------------------------

    public function test_active_hold_blocks_booking(): void
    {
        $start = Carbon::tomorrow()->setTime(12, 0)->utc();

        // Кто-то держит hold на ресурс
        ReservationHold::create([
            'club_id'       => $this->club->id,
            'resource_id'   => $this->table1->id,
            'start_at'      => $start,
            'end_at'        => $start->copy()->addHour(),
            'expires_at'    => now()->addMinutes(10),
            'session_token' => 'other-user-token',
        ]);

        $this->expectException(SlotNotAvailableException::class);
        $this->createBooking->handle($this->makeDTO($this->table1, $start));
    }

    public function test_expired_hold_does_not_block_booking(): void
    {
        $start = Carbon::tomorrow()->setTime(13, 0)->utc();

        // Истёкший hold — не должен блокировать
        ReservationHold::create([
            'club_id'       => $this->club->id,
            'resource_id'   => $this->table1->id,
            'start_at'      => $start,
            'end_at'        => $start->copy()->addHour(),
            'expires_at'    => now()->subMinute(), // уже истёк
            'session_token' => 'old-token',
        ]);

        $booking = $this->createBooking->handle($this->makeDTO($this->table1, $start));
        $this->assertEquals(BookingStatus::Confirmed, $booking->status);
    }

    // -------------------------------------------------------------------------
    // CancelBooking
    // -------------------------------------------------------------------------

    public function test_cancel_booking(): void
    {
        $start   = Carbon::tomorrow()->setTime(9, 0)->utc();
        $booking = $this->createBooking->handle($this->makeDTO($this->table1, $start));

        $cancelled = (new CancelBooking())->handle($booking, comment: 'Клиент отказался');

        $this->assertEquals(BookingStatus::Cancelled, $cancelled->status);
        $this->assertDatabaseHas('booking_status_history', [
            'booking_id' => $booking->id,
            'status'     => 'cancelled',
        ]);
    }

    public function test_cannot_cancel_already_cancelled_booking(): void
    {
        $start   = Carbon::tomorrow()->setTime(9, 30)->utc();
        $booking = $this->createBooking->handle($this->makeDTO($this->table1, $start));
        (new CancelBooking())->handle($booking);

        $this->expectException(\RuntimeException::class);
        (new CancelBooking())->handle($booking->fresh());
    }

    public function test_cancelled_slot_becomes_available_again(): void
    {
        $start = Carbon::tomorrow()->setTime(8, 0)->utc();

        $booking = $this->createBooking->handle($this->makeDTO($this->table1, $start));
        (new CancelBooking())->handle($booking);

        // После отмены тот же слот должен бронироваться снова
        $newBooking = $this->createBooking->handle($this->makeDTO($this->table1, $start));
        $this->assertEquals(BookingStatus::Confirmed, $newBooking->status);
    }

    // -------------------------------------------------------------------------
    // RescheduleBooking
    // -------------------------------------------------------------------------

    public function test_reschedule_booking(): void
    {
        $start   = Carbon::tomorrow()->setTime(10, 0)->utc();
        $booking = $this->createBooking->handle($this->makeDTO($this->table1, $start));

        $newStart = Carbon::tomorrow()->setTime(14, 0)->utc();
        $reschedule = new RescheduleBooking(new CancelBooking(), new CreateBooking());
        $newBooking = $reschedule->handle(
            booking:        $booking,
            newStartAt:     $newStart,
            newEndAt:       $newStart->copy()->addHour(),
            newResourceIds: [$this->table1->id],
        );

        // Старая бронь отменена
        $this->assertEquals(BookingStatus::Cancelled, $booking->fresh()->status);
        // Новая создана
        $this->assertEquals(BookingStatus::Confirmed, $newBooking->status);
        $this->assertEquals($newStart->toDateTimeString(), $newBooking->start_at->toDateTimeString());
    }

    // -------------------------------------------------------------------------
    // AvailabilityService
    // -------------------------------------------------------------------------

    public function test_availability_excludes_booked_slots(): void
    {
        $availability = new AvailabilityService();

        $tomorrow = Carbon::tomorrow()->setTimezone('Europe/Moscow');

        // Создаём бронь на 10:00–11:00 UTC для обоих столов (все ресурсы данного типа заняты)
        $start = Carbon::tomorrow()->setTime(7, 0)->utc(); // 10:00 Moscow
        $this->createBooking->handle($this->makeDTO($this->table1, $start));
        $this->createBooking->handle($this->makeDTO($this->table2, $start));

        $this->service->resourceRequirements()->create([
            'resource_type_id' => $this->typeTable->id,
            'quantity'         => 1,
        ]);

        $slots = $availability->availableSlots($this->service->fresh(), $this->branch->fresh(), $tomorrow);

        // Слот 10:00 Moscow (7:00 UTC) не должен быть в списке
        $bookedSlot = $slots->first(fn ($s) => $s['start_at']->hour === 7 && $s['start_at']->minute === 0);
        $this->assertNull($bookedSlot);
    }
}
