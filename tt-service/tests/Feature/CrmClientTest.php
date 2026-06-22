<?php

namespace Tests\Feature;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Crm\Actions\AddClientNote;
use App\Domain\Crm\Actions\BlockClient;
use App\Domain\Crm\Actions\CreateClient;
use App\Domain\Crm\DTO\CreateClientDTO;
use App\Domain\Crm\Models\Client;
use App\Domain\Crm\Models\ClientNote;
use App\Domain\Crm\Models\ClientTag;
use App\Domain\Crm\Services\ClientCardService;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Enums\BookingStatus;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 8 — CRM / Clients.
 *
 * ✅ Готово, если: карточка клиента, история броней, заметки.
 */
class CrmClientTest extends TestCase
{
    use RefreshDatabase;

    private Club $club;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->club = Club::factory()->create();
    }

    // -------------------------------------------------------------------------
    // Создание клиента
    // -------------------------------------------------------------------------

    public function test_create_client(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId:    $this->club->id,
            firstName: 'Иван',
            lastName:  'Петров',
            phone:     '+7 (900) 123-45-67',
            email:     'ivan@example.com',
            source:    'walk-in',
        ));

        $this->assertInstanceOf(Client::class, $client);
        $this->assertNotEmpty($client->public_id);
        $this->assertEquals('Иван Петров', $client->fullName());
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'phone' => '+7 (900) 123-45-67']);
    }

    public function test_client_has_ulid_public_id(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Тест'
        ));

        $this->assertMatchesRegularExpression('/^[0-9a-z]{26}$/', $client->public_id);
    }

    public function test_duplicate_phone_in_same_club_rejected(): void
    {
        (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id,
            firstName: 'Первый',
            phone: '+7 (900) 111-22-33',
        ));

        $this->expectException(\RuntimeException::class);
        (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id,
            firstName: 'Второй',
            phone: '+7 (900) 111-22-33',
        ));
    }

    public function test_same_phone_allowed_in_different_clubs(): void
    {
        $club2 = Club::factory()->create();

        (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'А', phone: '+7 (900) 555-00-00'
        ));

        // В другом клубе тот же телефон — ок
        $client2 = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $club2->id, firstName: 'Б', phone: '+7 (900) 555-00-00'
        ));

        $this->assertNotNull($client2->id);
    }

    // -------------------------------------------------------------------------
    // Заметки
    // -------------------------------------------------------------------------

    public function test_add_note_to_client(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Клиент'
        ));

        $note = (new AddClientNote())->handle($client, 'Любит утренние тренировки', isPinned: true);

        $this->assertInstanceOf(ClientNote::class, $note);
        $this->assertTrue($note->is_pinned);
        $this->assertDatabaseHas('client_notes', ['client_id' => $client->id, 'text' => 'Любит утренние тренировки']);
    }

    public function test_pinned_notes_appear_first(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Клиент'
        ));

        (new AddClientNote())->handle($client, 'Обычная заметка', isPinned: false);
        (new AddClientNote())->handle($client, 'Закреплённая заметка', isPinned: true);

        $notes = $client->fresh()->notes;

        $this->assertEquals('Закреплённая заметка', $notes->first()->text);
    }

    // -------------------------------------------------------------------------
    // Теги
    // -------------------------------------------------------------------------

    public function test_attach_tags_to_client(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Клиент'
        ));

        $tagVip = ClientTag::create(['club_id' => $this->club->id, 'name' => 'VIP', 'color' => '#fbbf24']);
        $tagPro = ClientTag::create(['club_id' => $this->club->id, 'name' => 'Про-игрок', 'color' => '#10b981']);

        $client->tags()->attach([$tagVip->id, $tagPro->id]);

        $this->assertCount(2, $client->fresh()->tags);
    }

    // -------------------------------------------------------------------------
    // Предпочтения
    // -------------------------------------------------------------------------

    public function test_client_preferences(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Клиент'
        ));

        $client->setPreference('preferred_time', ['morning', 'evening']);
        $client->setPreference('preferred_resource_id', 5);

        $time = $client->getPreference('preferred_time');
        $this->assertEquals(['morning', 'evening'], $time);

        // Обновление
        $client->setPreference('preferred_time', ['evening']);
        $this->assertEquals(['evening'], $client->getPreference('preferred_time'));
    }

    // -------------------------------------------------------------------------
    // Блокировка
    // -------------------------------------------------------------------------

    public function test_block_and_unblock_client(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Нарушитель'
        ));

        $blocked = (new BlockClient())->block($client, 'Систематические неявки');

        $this->assertTrue($blocked->is_blocked);
        $this->assertEquals('Систематические неявки', $blocked->block_reason);

        $unblocked = (new BlockClient())->unblock($blocked);
        $this->assertFalse($unblocked->is_blocked);
        $this->assertNull($unblocked->block_reason);
    }

    public function test_cannot_block_already_blocked_client(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Клиент'
        ));

        (new BlockClient())->block($client, 'Причина 1');

        $this->expectException(\RuntimeException::class);
        (new BlockClient())->block($client->fresh(), 'Причина 2');
    }

    // -------------------------------------------------------------------------
    // История броней и карточка
    // -------------------------------------------------------------------------

    public function test_client_booking_history(): void
    {
        // Настройка ресурсов
        $branch = (new \App\Domain\Facilities\Actions\CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Филиал', timezone: 'Europe/Moscow')
        );
        $type = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'table', 'name' => 'Стол']);
        $table = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $branch->id,
            resourceTypeId: $type->id, name: 'Стол 1'
        ));
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id, 'duration_minutes' => 60]);
        PricingRule::create(['service_offering_id' => $service->id, 'amount_minor' => 50000, 'currency_code' => 'RUB', 'priority' => 0]);

        // Создаём клиента
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Игорь'
        ));

        // Создаём бронь для клиента
        $start = Carbon::tomorrow()->setTime(10, 0)->utc();
        $booking = (new CreateBooking())->handle(new CreateBookingDTO(
            clubId:            $this->club->id,
            branchId:          $branch->id,
            serviceOfferingId: $service->id,
            resourceIds:       [$table->id],
            startAt:           $start,
            endAt:             $start->copy()->addHour(),
            amountMinor:       50000,
            clientId:          $client->id,
        ));

        // История броней клиента
        $history = $client->bookings;
        $this->assertCount(1, $history);
        $this->assertEquals($booking->id, $history->first()->id);
    }

    public function test_client_card_stats(): void
    {
        // Настройка
        $branch = (new \App\Domain\Facilities\Actions\CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Ф', timezone: 'Europe/Moscow')
        );
        $type  = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'tbl', 'name' => 'Стол']);
        $table = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $branch->id,
            resourceTypeId: $type->id, name: 'T'
        ));
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id, 'duration_minutes' => 60]);
        PricingRule::create(['service_offering_id' => $service->id, 'amount_minor' => 50000, 'currency_code' => 'RUB', 'priority' => 0]);

        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Стат'
        ));

        // Две брони
        $start1 = Carbon::tomorrow()->setTime(11, 0)->utc();
        $start2 = Carbon::tomorrow()->setTime(13, 0)->utc();
        (new CreateBooking())->handle(new CreateBookingDTO(
            clubId: $this->club->id, branchId: $branch->id,
            serviceOfferingId: $service->id, resourceIds: [$table->id],
            startAt: $start1, endAt: $start1->copy()->addHour(),
            amountMinor: 50000, clientId: $client->id,
        ));

        $table2 = (new CreateResource())->handle(new CreateResourceDTO(
            clubId: $this->club->id, branchId: $branch->id,
            resourceTypeId: $type->id, name: 'T2'
        ));
        (new CreateBooking())->handle(new CreateBookingDTO(
            clubId: $this->club->id, branchId: $branch->id,
            serviceOfferingId: $service->id, resourceIds: [$table2->id],
            startAt: $start2, endAt: $start2->copy()->addHour(),
            amountMinor: 50000, clientId: $client->id,
        ));

        $card = (new ClientCardService())->card($client);

        $this->assertEquals(2, $card['stats']['total_bookings']);
        $this->assertEquals($client->id, $card['client']->id);
    }

    // -------------------------------------------------------------------------
    // Мягкое удаление
    // -------------------------------------------------------------------------

    public function test_soft_delete_client(): void
    {
        $client = (new CreateClient())->handle(new CreateClientDTO(
            clubId: $this->club->id, firstName: 'Удалить'
        ));

        $client->delete();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);

        // После удаления — не виден в обычных запросах
        $found = Client::withoutGlobalScopes()->where('id', $client->id)->first();
        $this->assertNotNull($found);
        $this->assertNotNull($found->deleted_at);
    }
}
