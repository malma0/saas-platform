<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Branch\Pages;

use App\MoonShine\Resources\Branch\BranchResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<BranchResource> */
final class BranchIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Text::make('Часовой пояс', 'timezone'),
            Text::make('Телефон', 'phone'),
            Date::make('Создан', 'created_at')->format('d.m.Y')->sortable(),
        ];
    }
}
