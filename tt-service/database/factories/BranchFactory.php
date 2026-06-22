<?php

namespace Database\Factories;

use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'club_id'   => Club::factory(),
            'name'      => fake()->randomElement(['Центральный', 'Северный', 'Южный', 'Западный']) . ' филиал',
            'address'   => fake()->address(),
            'phone'     => fake()->phoneNumber(),
            'email'     => fake()->companyEmail(),
            'timezone'  => fake()->randomElement([
                'Europe/Moscow',
                'Asia/Novosibirsk',
                'Asia/Yekaterinburg',
                'Asia/Krasnoyarsk',
            ]),
            'is_active' => true,
        ];
    }
}
