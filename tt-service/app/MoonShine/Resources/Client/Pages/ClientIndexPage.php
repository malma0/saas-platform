<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Client\Pages;

use App\MoonShine\Resources\Client\ClientResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<ClientResource> */
final class ClientIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Имя', 'first_name')->sortable(),
            Text::make('Фамилия', 'last_name')->sortable(),
            Text::make('Телефон', 'phone'),
            Text::make('Email', 'email'),
            Date::make('Дата рождения', 'birth_date'),
            Switcher::make('Заблокирован', 'is_blocked'),
            Text::make('Источник', 'source'),
        ];
    }

    protected function filters(): iterable
    {
        return [
            Switcher::make('Заблокирован', 'is_blocked'),
            Text::make('Поиск по телефону', 'phone'),
        ];
    }
}
