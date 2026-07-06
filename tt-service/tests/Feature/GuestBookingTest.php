<?php

namespace Tests\Feature;

use App\Domain\Booking\Models\Booking;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Actions\CreateBranch;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Гостевое бронирование с сайта (POST /book) и личный кабинет (GET /account).
 *
 * Замыкает петлю «сайт → БД → MoonShine»: бронь, созданная без логина,
 * пишется тем же движком и видна в базе (значит — и в админке).
 */
class GuestBookingTest extends TestCase
{
    use RefreshDatabase;

    private Club            $club;
    private Branch          $branch;
    private Resource        $table1;
    private ServiceOffering $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Сидер с демо-данными НЕ нужен: гостевой поток не использует роли,
        // а демо-клуб/брони сидера ломали бы счётчики (Booking::first()/count()).
        $this->club   = Club::factory()->create();
        $this->branch = (new CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Центр', timezone: 'Europe/Moscow')
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
            'name'             => 'Аренда стола',
            'duration_minutes' => 60,
        ]);
        PricingRule::create([
            'service_offering_id' => $this->service->id,
            'amount_minor'        => 50000, // 500 ₽/час
            'currency_code'       => 'RUB',
            'priority'            => 0,
        ]);
    }

    /** Будний день в будущем, 14:00–15:00 (в рабочих часах 09:00–22:00). */
    private function futureWeekday(): Carbon
    {
        $d = Carbon::now('Europe/Moscow')->addDays(3)->startOfDay();
        while ($d->dayOfWeek === Carbon::SUNDAY) {
            $d->addDay();
        }
        return $d;
    }

    private function payload(Carbon $date, string $start = '14:00', string $end = '15:00'): array
    {
        return [
            'name'                => 'Иван Петров',
            'phone'               => '+7 999 123-45-67',
            'branch_id'           => $this->branch->id,
            'service_offering_id' => $this->service->id,
            'resource_id'         => $this->table1->id,
            'date'                => $date->toDateString(),
            'start'               => $start,
            'end'                 => $end,
        ];
    }

    public function test_guest_can_book_from_site_and_it_lands_in_db(): void
    {
        $date = $this->futureWeekday();

        $res = $this->postJson('/book', $this->payload($date));

        $res->assertCreated()->assertJson(['ok' => true]);

        // Клиент создан по телефону
        $client = Client::withoutGlobalScopes()->where('phone', '+7 999 123-45-67')->first();
        $this->assertNotNull($client);
        $this->assertSame('Иван', $client->first_name);
        $this->assertSame('Петров', $client->last_name);
        $this->assertSame('website', $client->source);

        // Бронь в БД — значит видна и в MoonShine (та же таблица)
        $booking = Booking::withoutGlobalScopes()->where('client_id', $client->id)->first();
        $this->assertNotNull($booking);
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(50000, $booking->amount_minor); // 1 час × 500 ₽
        $this->assertDatabaseHas('booking_resources', [
            'booking_id'  => $booking->id,
            'resource_id' => $this->table1->id,
        ]);
    }

    public function test_two_hour_booking_is_priced_by_duration(): void
    {
        $date = $this->futureWeekday();

        $this->postJson('/book', $this->payload($date, '14:00', '16:00'))
            ->assertCreated();

        $booking = Booking::withoutGlobalScopes()->first();
        $this->assertSame(100000, $booking->amount_minor); // 2 часа × 500 ₽
    }

    public function test_overlapping_guest_booking_is_rejected(): void
    {
        $date = $this->futureWeekday();

        $this->postJson('/book', $this->payload($date, '14:00', '15:00'))->assertCreated();

        // Пересечение 14:30–15:30 → 409
        $this->postJson('/book', $this->payload($date, '14:30', '15:30'))
            ->assertStatus(409)
            ->assertJson(['ok' => false]);
    }

    public function test_booking_outside_working_hours_is_rejected(): void
    {
        $date = $this->futureWeekday();

        // 07:00–08:00 — до открытия (09:00)
        $this->postJson('/book', $this->payload($date, '07:00', '08:00'))
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
    }

    public function test_repeat_phone_reuses_same_client(): void
    {
        $date = $this->futureWeekday();

        $this->postJson('/book', $this->payload($date, '14:00', '15:00'))->assertCreated();
        $this->postJson('/book', $this->payload($date, '16:00', '17:00'))->assertCreated();

        $this->assertSame(1, Client::withoutGlobalScopes()->where('phone', '+7 999 123-45-67')->count());
        $this->assertSame(2, Booking::withoutGlobalScopes()->count());
    }

    public function test_booking_more_than_week_ahead_is_rejected(): void
    {
        // 10 дней вперёд — за пределами окна онлайн-брони (неделя).
        $far = Carbon::now('Europe/Moscow')->addDays(10)->startOfDay();
        while ($far->dayOfWeek === Carbon::SUNDAY) {
            $far->addDay();
        }

        $this->postJson('/book', $this->payload($far))
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'contact_admin' => true]);

        // Бронь не создалась
        $this->assertSame(0, Booking::withoutGlobalScopes()->count());
    }

    public function test_account_page_shows_client_bookings_by_phone(): void
    {
        $date = $this->futureWeekday();
        $this->postJson('/book', $this->payload($date))->assertCreated();

        $this->get('/account?phone=' . urlencode('+7 999 123-45-67'))
            ->assertOk()
            ->assertSee('Личный кабинет')
            ->assertSee('Стол 1')
            ->assertSee('Иван');
    }

    public function test_account_page_without_match_shows_lookup(): void
    {
        $this->get('/account?phone=' . urlencode('+7 000 000-00-00'))
            ->assertOk()
            ->assertSee('броней не найдено');
    }
}
