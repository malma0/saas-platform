<?php

namespace Database\Factories;

use App\Domain\ClubCore\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    protected $model = Club::class;

    public function definition(): array
    {
        return [
            'name'                  => fake()->company() . ' TT Club',
            'email'                 => fake()->companyEmail(),
            'phone'                 => fake()->phoneNumber(),
            'address'               => fake()->address(),
            'default_currency_code' => 'RUB',
            'default_locale'        => 'ru',
            'timezone'              => fake()->randomElement([
                'Europe/Moscow',
                'Asia/Novosibirsk',
                'Asia/Yekaterinburg',
                'Europe/Kaliningrad',
            ]),
            'is_active' => true,
        ];
    }
}
