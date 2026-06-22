<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Models\ServiceOffering;
use App\MoonShine\Resources\Booking\BookingResource;
use App\MoonShine\Resources\Club\ClubResource;
use App\MoonShine\Resources\Staff\StaffResource;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Приёмка Фазы 12 — единая панель Moonshine на App\Models\User.
 *
 * Критерий гайда: «owner/admin работают в своём клубе, superadmin — во всех».
 */
class MoonshinePanelTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private User $owner;
    private Club $clubA;
    private Club $clubB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->clubA = Club::factory()->create(['name' => 'Клуб А']);
        $this->clubB = Club::factory()->create(['name' => 'Клуб Б']);

        $this->superadmin = User::factory()->create(['club_id' => null]);
        $this->superadmin->assignRole('superadmin');

        $this->owner = User::factory()->create(['club_id' => $this->clubA->id]);
        $this->owner->assignRole('owner');
    }

    // ── Вход в панель ────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_superadmin_can_open_admin_panel(): void
    {
        $this->actingAs($this->superadmin)
            ->get('/admin')
            ->assertSuccessful();
    }

    /**
     * Регресс: сессионный резолв пользователя НЕ должен уходить в бесконечную
     * рекурсию из-за tenant-scope на модели User (исчерпание памяти, 500).
     * actingAs() это не ловит — нужен реальный резолв из провайдера.
     */
    public function test_session_user_resolution_does_not_recurse(): void
    {
        auth()->loginUsingId($this->owner->id);
        auth()->forgetUser(); // сброс кэша → следующий user() резолвит из провайдера (с scope)

        $resolved = auth()->user();

        $this->assertNotNull($resolved);
        $this->assertSame($this->owner->id, $resolved->id);
    }

    public function test_owner_can_open_admin_panel(): void
    {
        $this->actingAs($this->owner)
            ->get('/admin')
            ->assertSuccessful();
    }

    // ── Видимость разделов по ролям ──────────────────────────────────────

    public function test_club_resource_visible_only_to_superadmin(): void
    {
        $this->actingAs($this->superadmin);
        $this->assertTrue(app(ClubResource::class)->canSee());

        $this->actingAs($this->owner);
        $this->assertFalse(app(ClubResource::class)->canSee());
    }

    public function test_staff_resource_visible_to_owner_and_superadmin(): void
    {
        $this->actingAs($this->superadmin);
        $this->assertTrue(app(StaffResource::class)->canSee());

        $this->actingAs($this->owner);
        $this->assertTrue(app(StaffResource::class)->canSee());

        // admin (не owner) — не видит
        $admin = User::factory()->create(['club_id' => $this->clubA->id]);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $this->assertFalse(app(StaffResource::class)->canSee());
    }

    public function test_booking_resource_visible_to_all_staff(): void
    {
        $this->actingAs($this->owner);
        $this->assertTrue(app(BookingResource::class)->canSee());
    }

    // ── Tenant-фильтрация в панели ───────────────────────────────────────

    public function test_owner_sees_only_own_club_branches(): void
    {
        $branchA = Branch::factory()->create(['club_id' => $this->clubA->id]);
        $branchB = Branch::factory()->create(['club_id' => $this->clubB->id]);

        // Owner клуба А, авторизованный через основной guard,
        // через tenant-scope видит только филиалы своего клуба.
        $this->actingAs($this->owner);

        $branches = Branch::pluck('id')->all();

        $this->assertContains($branchA->id, $branches);
        $this->assertNotContains($branchB->id, $branches);
    }

    public function test_superadmin_sees_all_branches(): void
    {
        $branchA = Branch::factory()->create(['club_id' => $this->clubA->id]);
        $branchB = Branch::factory()->create(['club_id' => $this->clubB->id]);

        // superadmin (club_id = null) — scope не применяется, видит оба клуба
        $this->actingAs($this->superadmin);

        $branches = Branch::pluck('id')->all();

        $this->assertContains($branchA->id, $branches);
        $this->assertContains($branchB->id, $branches);
    }

    // ── Календарь броней ─────────────────────────────────────────────────

    public function test_calendar_events_requires_auth(): void
    {
        $this->getJson('/panel/booking-calendar/events')->assertStatus(401);
    }

    public function test_calendar_events_returns_bookings_as_json(): void
    {
        [$branch, $booking] = $this->makeBookingInClubA();

        $this->actingAs($this->owner)
            ->getJson('/panel/booking-calendar/events?start='
                . now()->subDay()->format('Y-m-d')
                . '&end=' . now()->addDays(5)->format('Y-m-d'))
            ->assertOk()
            ->assertJsonFragment(['id' => $booking->public_id]);
    }

    // ── Перенос брони ────────────────────────────────────────────────────

    public function test_reschedule_endpoint_moves_booking(): void
    {
        [$branch, $booking] = $this->makeBookingInClubA();

        $newStart = Carbon::now()->addDays(4)->setHour(15)->setMinute(0);
        $newEnd   = $newStart->copy()->addHour();

        $this->actingAs($this->owner)
            ->post(route('admin.booking.reschedule', $booking->public_id), [
                'new_start' => $newStart->format('Y-m-d\TH:i'),
                'new_end'   => $newEnd->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        // Старая бронь отменена (RescheduleBooking = cancel + create)
        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => 'cancelled',
        ]);

        // Появилась новая подтверждённая бронь в клубе А
        $this->assertDatabaseHas('bookings', [
            'club_id' => $this->clubA->id,
            'status'  => 'confirmed',
        ]);
    }

    /**
     * @return array{0: Branch, 1: \App\Domain\Booking\Models\Booking}
     */
    private function makeBookingInClubA(): array
    {
        $branch  = Branch::factory()->create(['club_id' => $this->clubA->id, 'timezone' => 'Europe/Moscow']);
        $resType = ResourceType::factory()->create(['club_id' => $this->clubA->id]);
        $resource = app(CreateResource::class)->handle(new CreateResourceDTO(
            clubId:         $this->clubA->id,
            branchId:       $branch->id,
            resourceTypeId: $resType->id,
            name:           'Стол 1',
        ));
        $service = ServiceOffering::factory()->create(['club_id' => $this->clubA->id]);

        $booking = app(CreateBooking::class)->handle(new CreateBookingDTO(
            clubId:            $this->clubA->id,
            branchId:          $branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       [$resource->id],
            startAt:           Carbon::now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0)->utc(),
            endAt:             Carbon::now()->addDays(2)->setHour(11)->setMinute(0)->setSecond(0)->utc(),
            amountMinor:       50000,
        ));

        return [$branch, $booking];
    }
}
