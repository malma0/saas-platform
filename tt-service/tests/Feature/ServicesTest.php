<?php

namespace Tests\Feature;

use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Services\Actions\CreateService;
use App\Domain\Services\DTO\CreateServiceDTO;
use App\Domain\Services\Models\PricingRule;
use App\Domain\Services\Models\ServiceOffering;
use App\Domain\Services\Services\PricingService;
use App\Support\Money\Money;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты Фазы 5 — услуги, требования к ресурсам, ценообразование.
 *
 * ✅ Готово, если: можно создать услугу с ресурсами и ценой,
 * цена корректно выбирается по дню/времени.
 */
class ServicesTest extends TestCase
{
    use RefreshDatabase;

    private Club           $club;
    private CreateService  $createService;
    private PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->club          = Club::factory()->create();
        $this->createService = new CreateService();
        $this->pricing       = new PricingService();
    }

    // -------------------------------------------------------------------------
    // CreateService Action
    // -------------------------------------------------------------------------

    public function test_create_simple_service_with_base_price(): void
    {
        $typeTable = ResourceType::create([
            'club_id' => $this->club->id,
            'slug'    => 'table',
            'name'    => 'Стол',
        ]);

        $service = $this->createService->handle(new CreateServiceDTO(
            clubId:           $this->club->id,
            name:             'Аренда стола',
            durationMinutes:  60,
            capacity:         2,
            resourceRequirements: [
                ['resource_type_id' => $typeTable->id, 'quantity' => 1],
            ],
            pricingRules: [
                ['amount_minor' => 50000, 'currency_code' => 'RUB', 'priority' => 0],
            ],
        ));

        $this->assertDatabaseHas('service_offerings', [
            'club_id'          => $this->club->id,
            'name'             => 'Аренда стола',
            'duration_minutes' => 60,
        ]);

        $this->assertCount(1, $service->resourceRequirements);
        $this->assertCount(1, $service->pricingRules);
        $this->assertEquals($typeTable->id, $service->resourceRequirements->first()->resource_type_id);
        $this->assertEquals(50000, $service->pricingRules->first()->amount_minor);
        $this->assertNotEmpty($service->public_id);
    }

    public function test_service_with_multiple_resource_requirements(): void
    {
        $typeTable = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'table', 'name' => 'Стол']);
        $typeCoach = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'coach', 'name' => 'Тренер']);

        $service = $this->createService->handle(new CreateServiceDTO(
            clubId:           $this->club->id,
            name:             'Индивидуальная тренировка',
            durationMinutes:  60,
            resourceRequirements: [
                ['resource_type_id' => $typeTable->id, 'quantity' => 1],
                ['resource_type_id' => $typeCoach->id, 'quantity' => 1],
            ],
            pricingRules: [
                ['amount_minor' => 150000, 'currency_code' => 'RUB', 'priority' => 0],
            ],
        ));

        $this->assertCount(2, $service->resourceRequirements);

        $slugs = $service->resourceRequirements
            ->map(fn ($r) => $r->resourceType->slug)
            ->toArray();
        $this->assertContains('table', $slugs);
        $this->assertContains('coach', $slugs);
    }

    // -------------------------------------------------------------------------
    // PricingService — выбор цены
    // -------------------------------------------------------------------------

    public function test_base_price_is_returned_when_no_specific_rule(): void
    {
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);
        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);

        $price = $this->pricing->priceFor($service, Carbon::now());

        $this->assertInstanceOf(Money::class, $price);
        $this->assertEquals(50000, $price->amountMinor);
        $this->assertEquals('RUB', $price->currencyCode);
    }

    public function test_weekend_price_overrides_base_price(): void
    {
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);

        // Базовая цена — 500 руб
        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);

        // Суббота (6) — 700 руб, приоритет выше
        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 70000,
            'currency_code'       => 'RUB',
            'day_of_week'         => 6, // Суббота
            'priority'            => 10,
        ]);

        // Проверяем в субботу
        $saturday = Carbon::parse('next saturday')->setTime(14, 0);
        $price = $this->pricing->priceFor($service, $saturday);
        $this->assertEquals(70000, $price->amountMinor);

        // В будний день — базовая
        $monday = Carbon::parse('next monday')->setTime(14, 0);
        $price = $this->pricing->priceFor($service, $monday);
        $this->assertEquals(50000, $price->amountMinor);
    }

    public function test_peak_hours_price_overrides_day_price(): void
    {
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);

        // Базовая цена
        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);

        // Пиковые часы 18:00-22:00 — 800 руб
        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 80000,
            'currency_code'       => 'RUB',
            'time_from'           => '18:00',
            'time_to'             => '22:00',
            'priority'            => 20,
        ]);

        // В пиковые часы
        $peak = Carbon::today()->setTime(19, 0);
        $this->assertEquals(80000, $this->pricing->priceFor($service, $peak)->amountMinor);

        // Вне пиковых
        $offPeak = Carbon::today()->setTime(11, 0);
        $this->assertEquals(50000, $this->pricing->priceFor($service, $offPeak)->amountMinor);
    }

    public function test_valid_from_to_restricts_rule_by_date(): void
    {
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);

        // Базовая цена (бессрочная)
        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);

        // Акция: только до вчерашнего дня
        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 30000,
            'currency_code'       => 'RUB',
            'valid_from'          => now()->subDays(7)->toDateString(),
            'valid_to'            => now()->subDay()->toDateString(), // истекла вчера
            'priority'            => 10,
        ]);

        // Сегодня акция не применяется — должна вернуть базовую
        $price = $this->pricing->priceFor($service, Carbon::now());
        $this->assertEquals(50000, $price->amountMinor);
    }

    public function test_throws_when_no_pricing_rule(): void
    {
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);
        // Правил нет

        $this->expectException(\RuntimeException::class);
        $this->pricing->priceFor($service, Carbon::now());
    }

    public function test_base_price_helper(): void
    {
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);

        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 50000,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);

        PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 80000,
            'currency_code'       => 'RUB',
            'time_from'           => '18:00',
            'time_to'             => '22:00',
            'priority'            => 20,
        ]);

        $base = $this->pricing->basePrice($service);
        $this->assertNotNull($base);
        $this->assertEquals(50000, $base->amountMinor);
    }

    // -------------------------------------------------------------------------
    // Money value-object
    // -------------------------------------------------------------------------

    public function test_pricing_rule_returns_money_vo(): void
    {
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);
        $rule = PricingRule::create([
            'service_offering_id' => $service->id,
            'amount_minor'        => 75000,
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);

        $money = $rule->money();
        $this->assertInstanceOf(Money::class, $money);
        $this->assertEquals(75000, $money->amountMinor);
        $this->assertStringContainsString('750', $money->format()); // 750,00 ₽
    }

    // -------------------------------------------------------------------------
    // Мультиарендность услуг
    // -------------------------------------------------------------------------

    public function test_services_are_isolated_by_club(): void
    {
        $clubA = Club::factory()->create();
        $clubB = Club::factory()->create();

        ServiceOffering::factory()->count(2)->create(['club_id' => $clubA->id]);
        ServiceOffering::factory()->count(3)->create(['club_id' => $clubB->id]);

        $ownerA = \App\Models\User::factory()->create(['club_id' => $clubA->id]);
        $ownerA->assignRole('owner');
        $this->actingAs($ownerA);

        $this->assertCount(2, ServiceOffering::all());
        $this->assertTrue(ServiceOffering::all()->every(fn ($s) => $s->club_id === $clubA->id));
    }

    // -------------------------------------------------------------------------
    // SoftDeletes
    // -------------------------------------------------------------------------

    public function test_soft_delete_service(): void
    {
        $service = ServiceOffering::factory()->create(['club_id' => $this->club->id]);
        $id = $service->id;

        $service->delete();

        $this->assertSoftDeleted('service_offerings', ['id' => $id]);
        $this->assertNull(ServiceOffering::find($id));
        $this->assertNotNull(ServiceOffering::withTrashed()->find($id));
    }
}
