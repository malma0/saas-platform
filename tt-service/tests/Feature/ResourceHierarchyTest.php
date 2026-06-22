<?php

namespace Tests\Feature;

use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Тесты Фазы 4 — иерархия ресурсов через closure-таблицу.
 *
 * ✅ Главный тест: бронь зала видит занятость столов, и наоборот.
 */
class ResourceHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private CreateResource $action;
    private Club $club;
    private Branch $branch;
    private ResourceType $typeHall;
    private ResourceType $typeTable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->action = new CreateResource();
        $this->club   = Club::factory()->create();
        $this->branch = Branch::factory()->create(['club_id' => $this->club->id]);

        $this->typeHall  = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'hall',  'name' => 'Зал']);
        $this->typeTable = ResourceType::create(['club_id' => $this->club->id, 'slug' => 'table', 'name' => 'Стол']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeResource(string $name, ?int $parentId = null, int $capacity = 2): Resource
    {
        $typeId = $parentId === null ? $this->typeHall->id : $this->typeTable->id;

        return $this->action->handle(new CreateResourceDTO(
            clubId:         $this->club->id,
            branchId:       $this->branch->id,
            resourceTypeId: $typeId,
            name:           $name,
            parentId:       $parentId,
            capacity:       $capacity,
        ));
    }

    // -------------------------------------------------------------------------
    // Closure-таблица
    // -------------------------------------------------------------------------

    public function test_root_resource_has_self_link_in_closure(): void
    {
        $hall = $this->makeResource('Зал А');

        $rows = DB::table('resource_closure')
            ->where('ancestor_id', $hall->id)
            ->where('descendant_id', $hall->id)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEquals(0, $rows->first()->depth);
    }

    public function test_child_resource_has_correct_closure_entries(): void
    {
        $hall  = $this->makeResource('Зал А');
        $table = $this->makeResource('Стол 1', $hall->id);

        // Self-link для стола
        $selfLink = DB::table('resource_closure')
            ->where('ancestor_id', $table->id)
            ->where('descendant_id', $table->id)
            ->first();
        $this->assertNotNull($selfLink);
        $this->assertEquals(0, $selfLink->depth);

        // Связь зал → стол (depth=1)
        $hallToTable = DB::table('resource_closure')
            ->where('ancestor_id', $hall->id)
            ->where('descendant_id', $table->id)
            ->first();
        $this->assertNotNull($hallToTable);
        $this->assertEquals(1, $hallToTable->depth);
    }

    public function test_three_level_hierarchy_closure(): void
    {
        // venue → hall → table (3 уровня)
        $venue = $this->makeResource('Корпус 1');
        $hall  = $this->makeResource('Зал А', $venue->id);
        $table = $this->makeResource('Стол 1', $hall->id);

        // venue → table должен быть depth=2
        $link = DB::table('resource_closure')
            ->where('ancestor_id', $venue->id)
            ->where('descendant_id', $table->id)
            ->first();
        $this->assertNotNull($link);
        $this->assertEquals(2, $link->depth);
    }

    // -------------------------------------------------------------------------
    // relatedResourceIds() — ключевой метод конфликт-чека
    // -------------------------------------------------------------------------

    /**
     * Главный тест Фазы 4:
     * бронируем зал → система видит все столы внутри (и наоборот).
     */
    public function test_hall_related_ids_includes_all_tables(): void
    {
        $hall   = $this->makeResource('Зал А');
        $table1 = $this->makeResource('Стол 1', $hall->id);
        $table2 = $this->makeResource('Стол 2', $hall->id);
        $table3 = $this->makeResource('Стол 3', $hall->id);

        $relatedIds = $hall->relatedResourceIds();

        // Зал должен видеть себя и все 3 стола
        $this->assertContains($hall->id,   $relatedIds);
        $this->assertContains($table1->id, $relatedIds);
        $this->assertContains($table2->id, $relatedIds);
        $this->assertContains($table3->id, $relatedIds);
        $this->assertCount(4, $relatedIds);
    }

    /**
     * Стол видит своего родителя-зал (для блокировки при бронировании зала).
     */
    public function test_table_related_ids_includes_parent_hall(): void
    {
        $hall   = $this->makeResource('Зал А');
        $table1 = $this->makeResource('Стол 1', $hall->id);
        $table2 = $this->makeResource('Стол 2', $hall->id);

        $relatedFromTable = $table1->relatedResourceIds();

        // Стол 1 видит: себя + зал (предок)
        // НЕ видит стол 2 (это сосед, не предок и не потомок)
        $this->assertContains($table1->id, $relatedFromTable);
        $this->assertContains($hall->id,   $relatedFromTable);
        $this->assertNotContains($table2->id, $relatedFromTable);
    }

    public function test_three_level_related_ids(): void
    {
        $venue  = $this->makeResource('Корпус 1');
        $hall   = $this->makeResource('Зал А', $venue->id);
        $table1 = $this->makeResource('Стол 1', $hall->id);

        // Venue видит всех потомков
        $ids = $venue->relatedResourceIds();
        $this->assertContains($venue->id,  $ids);
        $this->assertContains($hall->id,   $ids);
        $this->assertContains($table1->id, $ids);

        // Стол видит всех предков
        $idsFromTable = $table1->relatedResourceIds();
        $this->assertContains($table1->id, $idsFromTable);
        $this->assertContains($hall->id,   $idsFromTable);
        $this->assertContains($venue->id,  $idsFromTable);
    }

    // -------------------------------------------------------------------------
    // descendantIds / ancestorIds
    // -------------------------------------------------------------------------

    public function test_descendant_ids_excludes_self(): void
    {
        $hall   = $this->makeResource('Зал А');
        $table1 = $this->makeResource('Стол 1', $hall->id);
        $table2 = $this->makeResource('Стол 2', $hall->id);

        $descendants = $hall->descendantIds();

        $this->assertContains($table1->id, $descendants);
        $this->assertContains($table2->id, $descendants);
        $this->assertNotContains($hall->id, $descendants);
    }

    public function test_ancestor_ids_excludes_self(): void
    {
        $hall  = $this->makeResource('Зал А');
        $table = $this->makeResource('Стол 1', $hall->id);

        $ancestors = $table->ancestorIds();

        $this->assertContains($hall->id, $ancestors);
        $this->assertNotContains($table->id, $ancestors);
    }

    public function test_root_has_no_ancestors(): void
    {
        $hall = $this->makeResource('Зал А');
        $this->assertEmpty($hall->ancestorIds());
    }

    public function test_leaf_has_no_descendants(): void
    {
        $hall  = $this->makeResource('Зал А');
        $table = $this->makeResource('Стол 1', $hall->id);
        $this->assertEmpty($table->descendantIds());
    }

    // -------------------------------------------------------------------------
    // Независимые иерархии не пересекаются
    // -------------------------------------------------------------------------

    public function test_sibling_halls_do_not_share_related_ids(): void
    {
        $hallA  = $this->makeResource('Зал А');
        $tableA = $this->makeResource('Стол A1', $hallA->id);

        $hallB  = $this->makeResource('Зал Б');
        $tableB = $this->makeResource('Стол B1', $hallB->id);

        $relatedA = $hallA->relatedResourceIds();
        $relatedB = $hallB->relatedResourceIds();

        $this->assertNotContains($hallB->id,  $relatedA);
        $this->assertNotContains($tableB->id, $relatedA);
        $this->assertNotContains($hallA->id,  $relatedB);
        $this->assertNotContains($tableA->id, $relatedB);
    }

    // -------------------------------------------------------------------------
    // Мультиарендность ресурсов
    // -------------------------------------------------------------------------

    public function test_resources_are_isolated_by_club(): void
    {
        $clubA   = Club::factory()->create();
        $clubB   = Club::factory()->create();
        $branchA = Branch::factory()->create(['club_id' => $clubA->id]);
        $branchB = Branch::factory()->create(['club_id' => $clubB->id]);
        $typeA   = ResourceType::create(['club_id' => $clubA->id, 'slug' => 'table_x', 'name' => 'Стол']);
        $typeB   = ResourceType::create(['club_id' => $clubB->id, 'slug' => 'table_y', 'name' => 'Стол']);

        $this->action->handle(new CreateResourceDTO($clubA->id, $branchA->id, $typeA->id, 'Стол A'));
        $this->action->handle(new CreateResourceDTO($clubB->id, $branchB->id, $typeB->id, 'Стол B'));

        $ownerA = \App\Models\User::factory()->create(['club_id' => $clubA->id]);
        $ownerA->assignRole('owner');
        $this->actingAs($ownerA);

        $visible = Resource::all();
        $this->assertCount(1, $visible);
        $this->assertTrue($visible->every(fn ($r) => $r->club_id === $clubA->id));
    }

    // -------------------------------------------------------------------------
    // SoftDelete
    // -------------------------------------------------------------------------

    public function test_soft_delete_resource(): void
    {
        $resource = $this->makeResource('Стол 1');
        $id = $resource->id;

        $resource->delete();

        $this->assertSoftDeleted('resources', ['id' => $id]);
        $this->assertNull(Resource::find($id));
        $this->assertNotNull(Resource::withTrashed()->find($id));
    }
}
