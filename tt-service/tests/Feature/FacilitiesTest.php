<?php

namespace Tests\Feature;

use App\Domain\ClubCore\Models\Club;
use App\Domain\ClubCore\Models\ClubSetting;
use App\Domain\Facilities\Actions\CreateBranch;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Hall;
use App\Domain\Facilities\Models\Venue;
use App\Domain\Facilities\Models\WorkingHour;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 3 — Club Core + Branches + Venues + Halls + WorkingHours.
 */
class FacilitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Иерархия Club → Branch → Venue → Hall
    // -------------------------------------------------------------------------

    public function test_full_hierarchy_can_be_created(): void
    {
        $club = Club::factory()->create();

        $branch = Branch::factory()->create(['club_id' => $club->id]);
        $venue  = Venue::create(['club_id' => $club->id, 'branch_id' => $branch->id, 'name' => 'Зал А']);
        $hall   = Hall::create(['club_id' => $club->id, 'venue_id' => $venue->id, 'name' => 'Стол 1', 'capacity' => 2]);

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'club_id' => $club->id]);
        $this->assertDatabaseHas('venues',   ['id' => $venue->id,  'branch_id' => $branch->id]);
        $this->assertDatabaseHas('halls',    ['id' => $hall->id,   'venue_id' => $venue->id]);

        $this->assertCount(1, $branch->venues);
        $this->assertCount(1, $venue->halls);
    }

    public function test_branch_has_public_id_ulid(): void
    {
        $branch = Branch::factory()->create();

        $this->assertNotEmpty($branch->public_id);
        $this->assertMatchesRegularExpression('/^[0-9a-z]{26}$/', $branch->public_id);
    }

    public function test_club_has_branches_relation(): void
    {
        $club = Club::factory()->create();
        Branch::factory()->count(3)->create(['club_id' => $club->id]);

        $this->assertCount(3, $club->fresh()->branches);
    }

    // -------------------------------------------------------------------------
    // Рабочее время
    // -------------------------------------------------------------------------

    public function test_create_branch_action_creates_default_working_hours(): void
    {
        $club   = Club::factory()->create();
        $action = new CreateBranch();

        $branch = $action->handle(new CreateBranchDTO(
            clubId:   $club->id,
            name:     'Тестовый филиал',
            timezone: 'Europe/Moscow',
        ));

        $this->assertCount(7, $branch->workingHours);

        $sunday = $branch->getWorkingHourForDay(0);
        $this->assertTrue($sunday->is_closed);

        $monday = $branch->getWorkingHourForDay(1);
        $this->assertFalse($monday->is_closed);
        $this->assertEquals('09:00:00', $monday->open_time);
        $this->assertEquals('22:00:00', $monday->close_time);
    }

    public function test_create_branch_with_custom_working_hours(): void
    {
        $club   = Club::factory()->create();
        $action = new CreateBranch();

        $customHours = [
            0 => ['open_time' => null,    'close_time' => null,    'is_closed' => true],
            1 => ['open_time' => '10:00', 'close_time' => '20:00', 'is_closed' => false],
            2 => ['open_time' => '10:00', 'close_time' => '20:00', 'is_closed' => false],
            3 => ['open_time' => '10:00', 'close_time' => '20:00', 'is_closed' => false],
            4 => ['open_time' => '10:00', 'close_time' => '20:00', 'is_closed' => false],
            5 => ['open_time' => '10:00', 'close_time' => '20:00', 'is_closed' => false],
            6 => ['open_time' => '11:00', 'close_time' => '18:00', 'is_closed' => false],
        ];

        $branch = $action->handle(new CreateBranchDTO(clubId: $club->id, name: 'Филиал 2'), $customHours);

        $saturday = $branch->getWorkingHourForDay(6);
        $this->assertEquals('11:00:00', $saturday->open_time);
        $this->assertEquals('18:00:00', $saturday->close_time);
        $this->assertFalse($saturday->is_closed);

        $this->assertFalse($branch->isOpenOn(0));
        $this->assertTrue($branch->isOpenOn(5));
    }

    public function test_working_hour_day_name(): void
    {
        $branch = Branch::factory()->create();
        $wh = WorkingHour::create([
            'branch_id'   => $branch->id,
            'day_of_week' => 1,
            'open_time'   => '09:00',
            'close_time'  => '22:00',
            'is_closed'   => false,
        ]);

        $this->assertEquals('Понедельник', $wh->dayName());
    }

    // -------------------------------------------------------------------------
    // ClubSetting
    // -------------------------------------------------------------------------

    public function test_club_settings_can_be_set_and_retrieved(): void
    {
        $club = Club::factory()->create();

        $club->setSetting('booking.min_duration_minutes', 30);
        $club->setSetting('booking.max_advance_days', 60);

        $this->assertEquals(30, $club->getSetting('booking.min_duration_minutes'));
        $this->assertEquals(60, $club->getSetting('booking.max_advance_days'));
        $this->assertNull($club->getSetting('nonexistent.key'));
        $this->assertEquals('default', $club->getSetting('nonexistent.key', 'default'));
    }

    public function test_club_settings_update_existing(): void
    {
        $club = Club::factory()->create();

        $club->setSetting('currency', 'RUB');
        $club->setSetting('currency', 'USD');

        $this->assertDatabaseCount('club_settings', 1);
        $this->assertEquals('USD', $club->getSetting('currency'));
    }

    // -------------------------------------------------------------------------
    // Мультиарендность Facilities
    // -------------------------------------------------------------------------

    public function test_branches_are_isolated_by_club(): void
    {
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        Branch::factory()->count(2)->create(['club_id' => $clubA->id]);
        Branch::factory()->count(3)->create(['club_id' => $clubB->id]);

        $ownerA = \App\Models\User::factory()->create(['club_id' => $clubA->id]);
        $ownerA->assignRole('owner');
        $this->actingAs($ownerA);

        $this->assertCount(2, Branch::all());
        $this->assertTrue(Branch::all()->every(fn ($b) => $b->club_id === $clubA->id));
    }

    public function test_soft_delete_branch(): void
    {
        $branch = Branch::factory()->create();
        $id     = $branch->id;

        $branch->delete();

        $this->assertSoftDeleted('branches', ['id' => $id]);
        $this->assertNull(Branch::find($id));
        $this->assertNotNull(Branch::withTrashed()->find($id));
    }
}
