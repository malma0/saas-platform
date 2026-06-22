<?php

namespace Database\Factories;

use App\Domain\ClubCore\Models\Club;
use App\Domain\Services\Models\ServiceOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceOffering>
 */
class ServiceOfferingFactory extends Factory
{
    protected $model = ServiceOffering::class;

    public function definition(): array
    {
        return [
            'club_id'          => Club::factory(),
            'name'             => fake()->randomElement([
                'Аренда стола',
                'Индивидуальная тренировка',
                'Групповая тренировка',
                'Аренда зала',
            ]),
            'description'      => fake()->sentence(),
            'duration_minutes' => fake()->randomElement([30, 45, 60, 90]),
            'capacity'         => fake()->randomElement([1, 2, 6, 12]),
            'is_active'        => true,
        ];
    }
}
