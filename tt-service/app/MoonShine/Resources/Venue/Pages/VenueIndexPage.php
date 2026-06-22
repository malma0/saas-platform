<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Venue\Pages;

use App\MoonShine\Resources\Venue\VenueResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<VenueResource> */
final class VenueIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Филиал', 'branch.name'),
            Text::make('Описание', 'description'),
            Switcher::make('Активен', 'is_active'),
        ];
    }
}
