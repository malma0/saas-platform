<?php

namespace Database\Factories;

use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resource>
 *
 * Важно: фабрика НЕ заполняет closure-таблицу.
 * Для создания с иерархией используй CreateResource Action.
 * Фабрика — только для тестов плоских данных.
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        $branch = Branch::factory()->create();

        return [
            'club_id'          => $branch->club_id,
            'branch_id'        => $branch->id,
            'resource_type_id' => ResourceType::factory()->create(['club_id' => $branch->club_id])->id,
            'parent_id'        => null,
            'name'             => 'Стол ' . fake()->numberBetween(1, 20),
            'capacity'         => 2,
            'is_active'        => true,
        ];
    }
}
