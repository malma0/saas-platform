<?php

namespace Database\Factories;

use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceType>
 */
class ResourceTypeFactory extends Factory
{
    protected $model = ResourceType::class;

    public function definition(): array
    {
        static $idx = 0;
        $slugs = ['table', 'hall', 'coach', 'room', 'venue_whole'];

        return [
            'club_id' => Club::factory(),
            'slug'    => $slugs[$idx++ % count($slugs)] . '_' . uniqid(),
            'name'    => fake()->word(),
        ];
    }
}
