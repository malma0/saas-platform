<?php

namespace App\Domain\Facilities\Actions;

use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Resource;
use Illuminate\Support\Facades\DB;

/**
 * Создание ресурса с поддержкой closure-таблицы.
 *
 * После создания ресурса необходимо заполнить resource_closure:
 *  1. Запись о самом себе: (id, id, 0)
 *  2. Для каждого предка (из closure родителя): (ancestor, new_id, depth+1)
 *
 * Это обеспечивает работу relatedResourceIds() без рекурсии.
 */
class CreateResource
{
    public function handle(CreateResourceDTO $dto): Resource
    {
        return DB::transaction(function () use ($dto) {
            $resource = Resource::create([
                'club_id'          => $dto->clubId,
                'branch_id'        => $dto->branchId,
                'resource_type_id' => $dto->resourceTypeId,
                'parent_id'        => $dto->parentId,
                'name'             => $dto->name,
                'capacity'         => $dto->capacity,
                'is_active'        => true,
            ]);

            $this->rebuildClosure($resource);

            return $resource;
        });
    }

    /**
     * Перестроить closure-записи для нового ресурса.
     *
     * Алгоритм:
     *  1. Вставить запись (self → self, depth=0)
     *  2. Взять все строки предков родителя из closure: (ancestor, parent, depth)
     *     и вставить (ancestor, self, depth+1)
     *  3. Вставить (parent, self, 1) — прямая связь с родителем
     */
    public function rebuildClosure(Resource $resource): void
    {
        // 1. Сам себя
        DB::table('resource_closure')->insert([
            'ancestor_id'   => $resource->id,
            'descendant_id' => $resource->id,
            'depth'         => 0,
        ]);

        // 2 + 3. Все предки через родителя
        if ($resource->parent_id !== null) {
            DB::insert(
                'INSERT INTO resource_closure (ancestor_id, descendant_id, depth)
                 SELECT ancestor_id, ?, depth + 1
                 FROM resource_closure
                 WHERE descendant_id = ?',
                [$resource->id, $resource->parent_id]
            );
        }
    }

    /**
     * Удалить все closure-записи для ресурса.
     * Вызывается перед физическим удалением ресурса.
     */
    public function removeClosure(Resource $resource): void
    {
        DB::table('resource_closure')
            ->where('ancestor_id', $resource->id)
            ->orWhere('descendant_id', $resource->id)
            ->delete();
    }

    /**
     * Переместить ресурс к новому родителю — перестроить closure.
     */
    public function moveTo(Resource $resource, ?int $newParentId): void
    {
        DB::transaction(function () use ($resource, $newParentId) {
            // Удаляем старые связи (кроме self-link)
            DB::table('resource_closure')
                ->where('descendant_id', $resource->id)
                ->where('ancestor_id', '!=', $resource->id)
                ->delete();

            $resource->parent_id = $newParentId;
            $resource->save();

            if ($newParentId !== null) {
                DB::insert(
                    'INSERT INTO resource_closure (ancestor_id, descendant_id, depth)
                     SELECT ancestor_id, ?, depth + 1
                     FROM resource_closure
                     WHERE descendant_id = ?',
                    [$resource->id, $newParentId]
                );
            }
        });
    }
}
