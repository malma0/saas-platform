<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Hall;

use App\Domain\Facilities\Models\Hall;
use App\MoonShine\Resources\Hall\Pages\HallFormPage;
use App\MoonShine\Resources\Hall\Pages\HallIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<Hall, HallIndexPage, HallFormPage, null>
 */
#[Icon('table-cells')]
#[Group('Клуб', 'building-office-2')]
#[Order(5)]
class HallResource extends ModelResource
{
    protected string $model  = Hall::class;
    protected string $column = 'name';
    protected array  $with   = ['venue'];

    public function getTitle(): string { return 'Залы / Столы'; }

    protected function pages(): array
    {
        return [HallIndexPage::class, HallFormPage::class];
    }

    protected function search(): array { return ['id', 'name']; }
}
