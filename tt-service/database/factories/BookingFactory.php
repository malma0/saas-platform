<?php

namespace Database\Factories;

use App\Domain\Booking\Models\Booking;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Services\Models\ServiceOffering;
use App\Support\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $club    = Club::factory()->create();
        $branch  = Branch::factory()->create(['club_id' => $club->id]);
        $service = ServiceOffering::factory()->create(['club_id' => $club->id]);
        $start   = now()->addHours(fake()->numberBetween(1, 48))->startOfHour();

        return [
            'club_id'             => $club->id,
            'branch_id'           => $branch->id,
            'service_offering_id' => $service->id,
            'client_id'           => null,
            'admin_id'            => null,
            'start_at'            => $start,
            'end_at'              => $start->copy()->addHour(),
            'status'              => BookingStatus::Confirmed,
            'amount_minor'        => fake()->numberBetween(30000, 200000),
            'currency_code'       => 'RUB',
            'notes'               => null,
        ];
    }
}
