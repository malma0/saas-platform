<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Venue;

use App\Domain\Facilities\Models\Venue;
use App\MoonShine\Resources\Venue\Pages\VenueFormPage;
use App\MoonShine\Resources\Venue\Pages\VenueIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<Venue, VenueIndexPage, VenueFormPage, null>
 */
#[Icon('building-office-2')]
#[Group('Клуб', 'building-office-2')]
#[Order(4)]
class VenueResource extends ModelResource
{
    protected string $model  = Venue::class;
    protected string $column = 'name';
    protected array  $with   = ['branch'];

    public function getTitle(): string { return 'Объекты / Залы'; }

    protected function pages(): array
    {
        return [VenueIndexPage::class, VenueFormPage::class];
    }

    protected function search(): array { return ['id', 'name']; }
}
