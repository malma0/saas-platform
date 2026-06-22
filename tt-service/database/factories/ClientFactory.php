<?php

namespace Database\Factories;

use App\Domain\ClubCore\Models\Club;
use App\Domain\Crm\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'club_id'    => Club::factory(),
            'first_name' => fake()->firstName(),
            'last_name'  => fake()->lastName(),
            'phone'      => fake()->numerify('+7 (9##) ###-##-##'),
            'email'      => fake()->unique()->safeEmail(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-16 years')->format('Y-m-d'),
            'gender'     => fake()->randomElement(['male', 'female']),
            'source'     => fake()->randomElement(['walk-in', 'referral', 'social', 'website', null]),
        ];
    }
}
