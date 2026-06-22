<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Resource;

use App\Domain\Facilities\Models\Resource;
use App\MoonShine\Resources\Resource\Pages\ResourceFormPage;
use App\MoonShine\Resources\Resource\Pages\ResourceIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<Resource, ResourceIndexPage, ResourceFormPage, null>
 */
#[Icon('squares-2x2')]
#[Group('Клуб', 'building-office-2')]
#[Order(2)]
class ResourceResource extends ModelResource
{
    protected string $model  = Resource::class;
    protected string $column = 'name';
    protected array  $with   = ['resourceType', 'branch'];

    public function getTitle(): string { return 'Ресурсы (столы/залы)'; }

    protected function pages(): array
    {
        return [ResourceIndexPage::class, ResourceFormPage::class];
    }

    protected function search(): array
    {
        return ['id', 'name'];
    }
}
