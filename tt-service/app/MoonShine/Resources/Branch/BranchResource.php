<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Branch;

use App\Domain\Facilities\Models\Branch;
use App\MoonShine\Resources\Branch\Pages\BranchFormPage;
use App\MoonShine\Resources\Branch\Pages\BranchIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<Branch, BranchIndexPage, BranchFormPage, null>
 */
#[Icon('map-pin')]
#[Group('Клуб', 'building-office-2')]
#[Order(1)]
class BranchResource extends ModelResource
{
    protected string $model  = Branch::class;
    protected string $column = 'name';

    public function getTitle(): string { return 'Филиалы'; }

    protected function pages(): array
    {
        return [BranchIndexPage::class, BranchFormPage::class];
    }

    protected function search(): array
    {
        return ['id', 'name'];
    }
}
