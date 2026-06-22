<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Club\Pages;

use App\MoonShine\Resources\Club\ClubResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<ClubResource> */
final class ClubIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Название', 'name')->sortable(),
            Email::make('Email', 'email'),
            Text::make('Телефон', 'phone'),
            Text::make('Валюта', 'default_currency_code'),
            Text::make('Часовой пояс', 'timezone'),
            Date::make('Создан', 'created_at')->format('d.m.Y')->sortable(),
        ];
    }
}
