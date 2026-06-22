<?php

namespace Tests\Feature;

use App\Domain\Attendance\Actions\MarkAttendance;
use App\Domain\Attendance\Models\Attendance;
use App\Domain\Attendance\Services\AttendanceReportService;
use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\Booking\Models\Booking;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Crm\Actions\CreateClient;
use App\Domain\Crm\DTO\CreateClientDTO;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 9 — Attendance.
 *
 * ✅ Готово, если: по брони можно отметить факт посещения / неявку.
 */
class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Club            $club;
    private Branch          $branch;
    private Resource        $table;
    private ServiceOffering $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club   = Club::factory()->create();
        $this->branch = (new \App\Domain\Facilities\Actions\CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Тест', timezone: 'Europe/Moscow')
        );

        $type = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'table', 'name' => 'Стол']);
        $this->table = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            resourceTypeId: $type->id, name: 'Стол 1'
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
    // Helper
    // -------------------------------------------------------------------------

    private function makeBooking(?int $clientId = null, ?Carbon $startAt = null): Booking
    {
        $start = $startAt ?? Carbon::tomorrow()->setTime(10, 0)->utc();
        return (new CreateBooking())->handle(new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $this->service->id,
            resourceIds:       [$this->table->id],
            startAt:           $start,
            endAt:             $start->copy()->addHour(),
            amountMinor:       50000,
            clientId:          $clientId,
        ));
    }

    // -------------------------------------------------------------------------
    // Базовые тесты
    // -------------------------------------------------------------------------

    public function test_mark_present(): void
    {
        $booking    = $this->makeBooking();
        $attendance = (new MarkAttendance())->handle($booking, AttendanceStatus::Present);

        $this->assertInstanceOf(Attendance::class, $attendance);
        $this->assertEquals(AttendanceStatus::Present, $attendance->status);
        $this->assertDatabaseHas('attendance', [
            'booking_id' => $booking->id,
            'status'     => 'present',
        ]);
    }

    public function test_mark_absent(): void
    {
        $booking    = $this->makeBooking();
        $attendance = (new MarkAttendance())->handle($booking, AttendanceStatus::Absent, comment: 'Предупредил заранее');

        $this->assertEquals(AttendanceStatus::Absent, $attendance->status);
        $this->assertEquals('Предупредил заранее', $attendance->comment);

        // Статус брони не меняется при absent
        $this->assertEquals(BookingStatus::Confirmed, $booking->fresh()->status);
    }

    public function test_mark_no_show_changes_booking_status(): void
    {
        $booking = $this->makeBooking();

        (new MarkAttendance())->handle($booking, AttendanceStatus::NoShow);

        // Статус брони должен поменяться на no_show
        $this->assertEquals(BookingStatus::NoShow, $booking->fresh()->status);
    }

    public function test_mark_present_completes_booking(): void
    {
        $booking = $this->makeBooking();

        (new MarkAttendance())->handle($booking, AttendanceStatus::Present);

        // Confirmed → Completed при явке
        $this->assertEquals(BookingStatus::Completed, $booking->fresh()->status);
    }

    public function test_attendance_linked_to_client(): void
    {
        $client  = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Клиент'
        ));
        $booking = $this->makeBooking(clientId: $client->id);

        $attendance = (new MarkAttendance())->handle($booking, AttendanceStatus::Present);

        $this->assertEquals($client->id, $attendance->client_id);
    }

    public function test_booking_has_attendance_relation(): void
    {
        $booking = $this->makeBooking();
        (new MarkAttendance())->handle($booking, AttendanceStatus::Present);

        $this->assertNotNull($booking->fresh()->attendance);
        $this->assertEquals(AttendanceStatus::Present, $booking->fresh()->attendance->status);
    }

    // -------------------------------------------------------------------------
    // Переотметка
    // -------------------------------------------------------------------------

    public function test_remark_attendance_updates_existing(): void
    {
        $booking = $this->makeBooking(startAt: Carbon::tomorrow()->setTime(14, 0)->utc());

        (new MarkAttendance())->handle($booking, AttendanceStatus::Absent);
        // Ошиблись — переотмечаем
        (new MarkAttendance())->handle($booking->fresh(), AttendanceStatus::Present);

        // Должна быть только одна запись
        $count = Attendance::withoutGlobalScopes()->where('booking_id', $booking->id)->count();
        $this->assertEquals(1, $count);

        $this->assertEquals(AttendanceStatus::Present, $booking->fresh()->attendance->status);
    }

    // -------------------------------------------------------------------------
    // Ошибки
    // -------------------------------------------------------------------------

    public function test_cannot_mark_cancelled_booking(): void
    {
        $booking = $this->makeBooking(startAt: Carbon::tomorrow()->setTime(15, 0)->utc());
        (new \App\Domain\Booking\Actions\CancelBooking())->handle($booking);

        $this->expectException(\RuntimeException::class);
        (new MarkAttendance())->handle($booking->fresh(), AttendanceStatus::Present);
    }

    // -------------------------------------------------------------------------
    // Статистика
    // -------------------------------------------------------------------------

    public function test_attendance_report_client_summary(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Статист'
        ));

        // Создаём разные ресурсы для разных слотов
        $type = ResourceType::where('slug', 'table')->first();

        $times = [
            Carbon::tomorrow()->setTime(9, 0)->utc(),
            Carbon::tomorrow()->setTime(11, 0)->utc(),
            Carbon::tomorrow()->setTime(13, 0)->utc(),
        ];

        $tables = [];
        foreach ($times as $i => $t) {
            $tables[$i] = (new CreateResource())->handle(new CreateResourceDTO(
                clubId: $this->club->id, branchId: $this->branch->id,
                resourceTypeId: $type->id, name: "Стол доп {$i}"
            ));
        }

        // Бронь 1 → present
        $b1 = (new CreateBooking())->handle(new CreateBookingDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            serviceOfferingId: $this->service->id, resourceIds: [$tables[0]->id],
            startAt: $times[0], endAt: $times[0]->copy()->addHour(),
            amountMinor: 50000, clientId: $client->id,
        ));
        (new MarkAttendance())->handle($b1, AttendanceStatus::Present);

        // Бронь 2 → no_show
        $b2 = (new CreateBooking())->handle(new CreateBookingDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            serviceOfferingId: $this->service->id, resourceIds: [$tables[1]->id],
            startAt: $times[1], endAt: $times[1]->copy()->addHour(),
            amountMinor: 50000, clientId: $client->id,
        ));
        (new MarkAttendance())->handle($b2, AttendanceStatus::NoShow);

        // Бронь 3 → absent
        $b3 = (new CreateBooking())->handle(new CreateBookingDTO(
            clubId: $this->club->id, branchId: $this->branch->id,
            serviceOfferingId: $this->service->id, resourceIds: [$tables[2]->id],
            startAt: $times[2], endAt: $times[2]->copy()->addHour(),
            amountMinor: 50000, clientId: $client->id,
        ));
        (new MarkAttendance())->handle($b3, AttendanceStatus::Absent);

        $summary = (new AttendanceReportService())->clientSummary($client->id);

        $this->assertEquals(1, $summary['present']);
        $this->assertEquals(1, $summary['absent']);
        $this->assertEquals(1, $summary['no_show']);
        $this->assertEquals(3, $summary['total']);
    }

    public function test_attendance_rate_calculation(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Ставка'
        ));

        $type = ResourceType::where('slug', 'table')->first();
        $slots = [
            Carbon::tomorrow()->setTime(8, 0)->utc(),
            Carbon::tomorrow()->setTime(16, 0)->utc(),
        ];

        foreach ($slots as $i => $t) {
            $tbl = (new CreateResource())->handle(new CreateResourceDTO(
                clubId: $this->club->id, branchId: $this->branch->id,
                resourceTypeId: $type->id, name: "Rate table {$i}"
            ));
            $b = (new CreateBooking())->handle(new CreateBookingDTO(
                clubId: $this->club->id, branchId: $this->branch->id,
                serviceOfferingId: $this->service->id, resourceIds: [$tbl->id],
                startAt: $t, endAt: $t->copy()->addHour(),
                amountMinor: 50000, clientId: $client->id,
            ));
            // Первая — present, вторая — no_show → ставка 50%
            $s = $i === 0 ? AttendanceStatus::Present : AttendanceStatus::NoShow;
            (new MarkAttendance())->handle($b, $s);
        }

        $rate = (new AttendanceReportService())->attendanceRate($client->id);
        $this->assertEquals(50.0, $rate);
    }
}
