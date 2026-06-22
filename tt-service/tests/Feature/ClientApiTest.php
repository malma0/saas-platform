<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 13 — Client REST API (Sanctum).
 */
class ClientApiTest extends TestCase
{
    use RefreshDatabase;

    private Club   $club;
    private Branch $branch;
    private User   $user;
    private Client $clientCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club   = Club::factory()->create();
        $this->branch = Branch::factory()->create(['club_id' => $this->club->id]);

        $this->user = User::factory()->create([
            'club_id'  => $this->club->id,
            'email'    => 'client@example.com',
            'password' => bcrypt('secret123'),
        ]);
        $this->user->assignRole('client');

        // CRM-карточка (как создаёт register)
        $this->clientCard = Client::create([
            'club_id'    => $this->club->id,
            'user_id'    => $this->user->id,
            'first_name' => 'Тест',
            'email'      => 'client@example.com',
            'source'     => 'app',
        ]);
    }

    /** Услуга с базовой ценой + ресурс — общий каркас для booking-тестов */
    private function makeBookableService(int $amountMinor = 50000): array
    {
        $service  = ServiceOffering::factory()->create(['club_id' => $this->club->id]);
        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => $amountMinor,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);

        $resType  = ResourceType::factory()->create();
        $resource = Resource::factory()->create([
            'club_id'          => $this->club->id,
            'branch_id'        => $this->branch->id,
            'resource_type_id' => $resType->id,
        ]);

        return [$service, $resource];
    }

    // ── Auth ─────────────────────────────────────────────────────────────

    public function test_register_creates_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Иван Иванов',
            'email'                 => 'new@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'club_id'               => $this->club->id,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'message', 'data' => ['user' => ['id', 'name', 'email'], 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'new@example.com']);

        // Регистрация создаёт CRM-карточку, связанную с пользователем
        $newUser = User::where('email', 'new@example.com')->first();
        $this->assertDatabaseHas('clients', [
            'user_id'    => $newUser->id,
            'club_id'    => $this->club->id,
            'first_name' => 'Иван',
            'last_name'  => 'Иванов',
            'source'     => 'app',
        ]);
    }

    public function test_login_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'client@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email'    => 'client@example.com',
            'password' => 'wrongpass',
        ])
            ->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonStructure(['success', 'message', 'errors']);
    }

    public function test_logout_revokes_token(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Выход выполнен.']);

        // Токен должен быть удалён из БД
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    // ── Profile ──────────────────────────────────────────────────────────

    public function test_get_profile_requires_auth(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }

    public function test_get_profile_returns_user_data(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonFragment(['email' => 'client@example.com']);
    }

    public function test_update_profile_changes_name(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/profile', ['name' => 'Новое Имя'])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Новое Имя']);

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Новое Имя']);
    }

    // ── Schedule ─────────────────────────────────────────────────────────

    public function test_schedule_requires_branch_id(): void
    {
        $this->getJson('/api/v1/schedule')
            ->assertStatus(422);
    }

    public function test_schedule_returns_empty_for_new_branch(): void
    {
        $this->getJson("/api/v1/schedule?branch_id={$this->branch->id}")
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['branch_id', 'date_from', 'date_to', 'sessions', 'busy_resource_ids']]);
    }

    // ── Services ─────────────────────────────────────────────────────────

    public function test_services_returns_active_only(): void
    {
        ServiceOffering::factory()->create(['club_id' => $this->club->id, 'is_active' => true,  'name' => 'Активная']);
        ServiceOffering::factory()->create(['club_id' => $this->club->id, 'is_active' => false, 'name' => 'Неактивная']);

        $response = $this->getJson('/api/v1/services');
        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name');
        $this->assertContains('Активная', $names->all());
        $this->assertNotContains('Неактивная', $names->all());
    }

    // ── Bookings ─────────────────────────────────────────────────────────

    public function test_bookings_list_requires_auth(): void
    {
        $this->getJson('/api/v1/bookings')->assertUnauthorized();
    }

    public function test_bookings_list_returns_own_bookings(): void
    {
        [$service, $resource] = $this->makeBookableService();

        $dto = new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       [$resource->id],
            startAt:           Carbon::now()->addDay()->setHour(10)->utc(),
            endAt:             Carbon::now()->addDay()->setHour(11)->utc(),
            amountMinor:       50000,
            adminId:           $this->user->id,
        );

        app(CreateBooking::class)->handle($dto);

        $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['items', 'meta']]);
    }

    public function test_create_booking_via_api(): void
    {
        [$service, $resource] = $this->makeBookableService(amountMinor: 70000);

        $start = Carbon::now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0);
        $end   = $start->copy()->addHour();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'branch_id'           => $this->branch->id,
                'service_offering_id' => $service->id,
                'resource_ids'        => [$resource->id],
                'start_at'            => $start->toIso8601String(),
                'end_at'              => $end->toIso8601String(),
            ])
            ->assertStatus(201)
            ->assertJson(['success' => true])
            // Цена посчитана по PricingRule, а не 0
            ->assertJsonPath('data.amount_minor', 70000)
            ->assertJsonStructure(['data' => ['id', 'status', 'start_at', 'end_at']]);

        // Бронь связана с CRM-карточкой клиента
        $this->assertDatabaseHas('bookings', [
            'club_id'   => $this->club->id,
            'client_id' => $this->clientCard->id,
        ]);
    }

    public function test_create_booking_fails_without_pricing_rule(): void
    {
        $service  = ServiceOffering::factory()->create(['club_id' => $this->club->id]);
        $resType  = ResourceType::factory()->create();
        $resource = Resource::factory()->create([
            'club_id'          => $this->club->id,
            'branch_id'        => $this->branch->id,
            'resource_type_id' => $resType->id,
        ]);

        $start = Carbon::now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0);

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'branch_id'           => $this->branch->id,
                'service_offering_id' => $service->id,
                'resource_ids'        => [$resource->id],
                'start_at'            => $start->toIso8601String(),
                'end_at'              => $start->copy()->addHour()->toIso8601String(),
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_client_cannot_see_or_cancel_foreign_booking(): void
    {
        [$service, $resource] = $this->makeBookableService();

        // Бронь другого клиента того же клуба
        $otherClient = Client::create([
            'club_id'    => $this->club->id,
            'first_name' => 'Другой',
        ]);

        $booking = app(CreateBooking::class)->handle(new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       [$resource->id],
            startAt:           Carbon::now()->addDays(4)->setHour(10)->utc(),
            endAt:             Carbon::now()->addDays(4)->setHour(11)->utc(),
            amountMinor:       50000,
            clientId:          $otherClient->id,
        ));

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/bookings/{$booking->public_id}")
            ->assertStatus(404);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/bookings/{$booking->public_id}")
            ->assertStatus(404);

        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    // ── Holds ────────────────────────────────────────────────────────────

    public function test_hold_blocks_slot_and_token_allows_booking(): void
    {
        [$service, $resource] = $this->makeBookableService();

        $start = Carbon::now()->addDays(2)->setHour(16)->setMinute(0)->setSecond(0);
        $end   = $start->copy()->addHour();

        // 1. Создаём hold
        $holdResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/holds', [
                'branch_id'    => $this->branch->id,
                'resource_ids' => [$resource->id],
                'start_at'     => $start->toIso8601String(),
                'end_at'       => $end->toIso8601String(),
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['hold_token', 'expires_at']]);

        $token = $holdResponse->json('data.hold_token');

        // 2. Чужая бронь на этот слот не проходит — hold блокирует
        $other = User::factory()->create(['club_id' => $this->club->id]);
        $other->assignRole('client');

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'branch_id'           => $this->branch->id,
                'service_offering_id' => $service->id,
                'resource_ids'        => [$resource->id],
                'start_at'            => $start->toIso8601String(),
                'end_at'              => $end->toIso8601String(),
            ])
            ->assertStatus(422);

        // 3. Владелец hold'а бронирует по токену — успех, hold снят
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/bookings', [
                'branch_id'           => $this->branch->id,
                'service_offering_id' => $service->id,
                'resource_ids'        => [$resource->id],
                'start_at'            => $start->toIso8601String(),
                'end_at'              => $end->toIso8601String(),
                'hold_token'          => $token,
            ])
            ->assertStatus(201);

        $this->assertDatabaseMissing('reservation_holds', ['session_token' => $token]);
    }

    public function test_cancel_booking_via_api(): void
    {
        [$service, $resource] = $this->makeBookableService();

        $dto = new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $this->branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       [$resource->id],
            startAt:           Carbon::now()->addDays(3)->setHour(10)->utc(),
            endAt:             Carbon::now()->addDays(3)->setHour(11)->utc(),
            amountMinor:       0,
            adminId:           $this->user->id,
        );

        $booking = app(CreateBooking::class)->handle($dto);

        $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/bookings/{$booking->public_id}")
            ->assertOk()
            ->assertJsonFragment(['message' => 'Бронирование отменено.']);

        $this->assertDatabaseHas('bookings', [
            'id'     => $booking->id,
            'status' => 'cancelled',
        ]);
    }
}
