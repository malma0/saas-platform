<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Resource\Pages;

use App\MoonShine\Resources\Resource\ResourceResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<ResourceResource> */
final class ResourceIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Тип', 'resourceType.name'),
            Text::make('Филиал', 'branch.name'),
            Text::make('Вместимость', 'capacity'),
            Switcher::make('Активен', 'is_active'),
        ];
    }
}
