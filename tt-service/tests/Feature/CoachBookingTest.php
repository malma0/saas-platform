<?php

namespace Tests\Feature;

use App\Domain\Booking\Models\Booking;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Crm\Models\Client;
use App\Domain\Facilities\Actions\CreateBranch;
use App\Domain\Facilities\Actions\CreateCoach;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateCoachDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Coach;
use App\Domain\Services\Models\ServiceOffering;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Запись на индивидуальную тренировку (POST /coaches/book).
 *
 * Тренер — ресурс типа «тренер», запись = бронь этого ресурса тем же движком.
 * Цена — по ставке тренера; защита от двойной записи — через конфликт-чек.
 */
class CoachBookingTest extends TestCase
{
    use RefreshDatabase;

    private Club   $club;
    private Branch $branch;
    private Coach  $coach;

    protected function setUp(): void
    {
        parent::setUp();

        $this->club   = Club::factory()->create();
        $this->branch = (new CreateBranch())->handle(
            new CreateBranchDTO(clubId: $this->club->id, name: 'Центр', timezone: 'Europe/Moscow')
        );

        // Услуга, под которой проходят тренировки (контроллер ищет по названию).
        ServiceOffering::factory()->create([
            'club_id'          => $this->club->id,
            'name'             => 'Индивидуальная тренировка',
            'duration_minutes' => 60,
        ]);

        $this->coach = (new CreateCoach())->handle(new CreateCoachDTO(
            clubId:          $this->club->id,
            branchId:        $this->branch->id,
            name:            'Иван Петров',
            hourlyRateMinor: 180000, // 1800 ₽/час
            specialization:  'Техника и тактика',
            experienceYears: 12,
            rank:            'МС',
        ));
    }

    private function futureWeekday(): Carbon
    {
        $d = Carbon::now('Europe/Moscow')->addDays(2)->startOfDay();
        while ($d->dayOfWeek === Carbon::SUNDAY) {
            $d->addDay();
        }
        return $d;
    }

    private function payload(Carbon $date, string $start = '14:00', string $end = '15:00'): array
    {
        return [
            'coach_id' => $this->coach->id,
            'name'     => 'Пётр Клиент',
            'phone'    => '+7 999 555-44-33',
            'date'     => $date->toDateString(),
            'start'    => $start,
            'end'      => $end,
        ];
    }

    public function test_guest_can_book_a_coach_and_it_lands_in_db(): void
    {
        $date = $this->futureWeekday();

        $this->postJson('/coaches/book', $this->payload($date))
            ->assertCreated()
            ->assertJson(['ok' => true, 'coach' => 'Иван Петров']);

        $client  = Client::withoutGlobalScopes()->where('phone', '+7 999 555-44-33')->first();
        $booking = Booking::withoutGlobalScopes()->where('client_id', $client->id)->first();

        $this->assertNotNull($booking);
        $this->assertSame(180000, $booking->amount_minor); // 1 час × 1800 ₽
        // Бронь занимает именно ресурс тренера
        $this->assertDatabaseHas('booking_resources', [
            'booking_id'  => $booking->id,
            'resource_id' => $this->coach->resource_id,
        ]);
    }

    public function test_two_hour_training_priced_by_coach_rate(): void
    {
        $date = $this->futureWeekday();

        $this->postJson('/coaches/book', $this->payload($date, '14:00', '16:00'))
            ->assertCreated();

        $booking = Booking::withoutGlobalScopes()->first();
        $this->assertSame(360000, $booking->amount_minor); // 2 часа × 1800 ₽
    }

    public function test_coach_double_booking_is_rejected(): void
    {
        $date = $this->futureWeekday();

        $this->postJson('/coaches/book', $this->payload($date, '14:00', '15:00'))->assertCreated();

        // Тот же тренер, пересекающееся время → 409
        $this->postJson('/coaches/book', $this->payload($date, '14:30', '15:30'))
            ->assertStatus(409)
            ->assertJson(['ok' => false]);
    }
}
