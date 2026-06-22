<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Staff\Pages;

use App\MoonShine\Resources\Staff\StaffResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<StaffResource> */
final class StaffIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Имя', 'name')->sortable(),
            Text::make('Email', 'email')->sortable(),
            Text::make('Роль', 'roles.0.name'),
            Date::make('Создан', 'created_at')->sortable(),
        ];
    }
}
