<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Hall\Pages;

use App\MoonShine\Resources\Hall\HallResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<HallResource> */
final class HallIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Объект', 'venue.name'),
            Number::make('Вместимость', 'capacity'),
            Switcher::make('Активен', 'is_active'),
        ];
    }
}
