<?php

namespace Tests\Feature;

use App\Domain\ClubCore\Models\Club;
use App\Domain\Identity\Actions\CreateUser;
use App\Domain\Identity\DTO\CreateUserDTO;
use App\Domain\Identity\Services\AccessControlService;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 2 — мультиарендность и контроль доступа.
 *
 * Ключевой тест: admin клуба A не видит данные клуба B.
 */
class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // -------------------------------------------------------------------------
    // BelongsToTenant — изоляция между клубами
    // -------------------------------------------------------------------------

    /**
     * Главный тест Фазы 2: admin клуба A не видит пользователей клуба B.
     */
    public function test_admin_of_club_a_cannot_see_users_of_club_b(): void
    {
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        $adminA = User::factory()->create(['club_id' => $clubA->id]);
        $adminA->assignRole('admin');

        // Пользователи клуба B
        $usersB = User::factory()->count(3)->create(['club_id' => $clubB->id]);

        // Аутентифицируемся как adminA — глобальный scope должен скрыть клуб B
        $this->actingAs($adminA);

        $visibleUsers = User::all();

        // AdminA видит только пользователей своего клуба (себя)
        $this->assertTrue($visibleUsers->every(fn ($u) => $u->club_id === $clubA->id));

        // Пользователи клуба B не видны
        foreach ($usersB as $userB) {
            $this->assertFalse($visibleUsers->contains('id', $userB->id));
        }
    }

    /**
     * Superadmin видит пользователей всех клубов.
     */
    public function test_superadmin_can_see_all_clubs_users(): void
    {
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        User::factory()->count(2)->create(['club_id' => $clubA->id]);
        User::factory()->count(2)->create(['club_id' => $clubB->id]);

        // Superadmin из seeder
        $superadmin = User::where('email', 'superadmin@tt-service.local')->first();
        $this->actingAs($superadmin);

        // Superadmin видит всех (scope не применяется)
        $count = User::count();
        $this->assertGreaterThanOrEqual(5, $count); // 4 созданных + сам superadmin
    }

    // -------------------------------------------------------------------------
    // Роли и права
    // -------------------------------------------------------------------------

    public function test_roles_exist(): void
    {
        $this->assertDatabaseHas('roles', ['name' => 'superadmin']);
        $this->assertDatabaseHas('roles', ['name' => 'owner']);
        $this->assertDatabaseHas('roles', ['name' => 'admin']);
        $this->assertDatabaseHas('roles', ['name' => 'user']);
    }

    public function test_owner_has_all_permissions_except_clubs_create(): void
    {
        $owner = User::factory()->create(['club_id' => Club::factory()->create()->id]);
        $owner->assignRole('owner');

        $this->assertTrue($owner->can('bookings.create'));
        $this->assertTrue($owner->can('clients.view'));
        $this->assertTrue($owner->can('staff.manage'));
        $this->assertFalse($owner->can('clubs.create'));
    }

    public function test_admin_has_operational_permissions_only(): void
    {
        $admin = User::factory()->create(['club_id' => Club::factory()->create()->id]);
        $admin->assignRole('admin');

        $this->assertTrue($admin->can('bookings.view'));
        $this->assertTrue($admin->can('bookings.create'));
        $this->assertTrue($admin->can('clients.view'));
        $this->assertTrue($admin->can('reports.view'));
        // Не должен иметь
        $this->assertFalse($admin->can('payments.refund'));
        $this->assertFalse($admin->can('staff.manage'));
        $this->assertFalse($admin->can('clubs.delete'));
    }

    public function test_user_role_has_minimal_permissions(): void
    {
        $user = User::factory()->create(['club_id' => Club::factory()->create()->id]);
        $user->assignRole('user');

        $this->assertTrue($user->can('bookings.view'));
        $this->assertFalse($user->can('bookings.create'));
        $this->assertFalse($user->can('clients.view'));
    }

    public function test_superadmin_bypasses_all_gates(): void
    {
        $superadmin = User::where('email', 'superadmin@tt-service.local')->first();
        $this->actingAs($superadmin);

        // Суперадмин может всё через Gate::before
        $this->assertTrue($superadmin->can('clubs.create'));
        $this->assertTrue($superadmin->can('payments.refund'));
        $this->assertTrue($superadmin->can('staff.assign_roles'));
    }

    // -------------------------------------------------------------------------
    // CreateUser Action
    // -------------------------------------------------------------------------

    public function test_create_user_action_assigns_role(): void
    {
        $club = Club::factory()->create();

        $action = new CreateUser();
        $user = $action->handle(new CreateUserDTO(
            name: 'Иван Петров',
            email: 'ivan@test.com',
            password: 'secret123',
            role: 'admin',
            clubId: $club->id,
        ));

        $this->assertDatabaseHas('users', ['email' => 'ivan@test.com', 'club_id' => $club->id]);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertNotEmpty($user->public_id);
    }

    // -------------------------------------------------------------------------
    // AccessControlService
    // -------------------------------------------------------------------------

    public function test_user_cannot_access_another_clubs_data(): void
    {
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        $userA = User::factory()->create(['club_id' => $clubA->id]);

        $service = new AccessControlService();

        $this->assertTrue($service->canAccessClub($userA, $clubA->id));
        $this->assertFalse($service->canAccessClub($userA, $clubB->id));
    }

    public function test_superadmin_can_access_any_club(): void
    {
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        $superadmin = User::where('email', 'superadmin@tt-service.local')->first();

        $service = new AccessControlService();

        $this->assertTrue($service->canAccessClub($superadmin, $clubA->id));
        $this->assertTrue($service->canAccessClub($superadmin, $clubB->id));
    }
}
