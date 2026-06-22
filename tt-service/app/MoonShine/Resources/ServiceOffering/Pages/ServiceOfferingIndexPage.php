<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ServiceOffering\Pages;

use App\MoonShine\Resources\ServiceOffering\ServiceOfferingResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<ServiceOfferingResource> */
final class ServiceOfferingIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Описание', 'description'),
            Number::make('Длительность (мин)', 'duration_minutes')->sortable(),
            Number::make('Вместимость', 'capacity'),
            Switcher::make('Активна', 'is_active'),
        ];
    }
}
