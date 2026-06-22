<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Club\Pages;

use App\MoonShine\Resources\Club\ClubResource;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;

/** @extends FormPage<ClubResource> */
final class ClubFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Flex::make([
                    Text::make('Название', 'name')->required(),
                    Text::make('Часовой пояс', 'timezone')
                        ->required()
                        ->hint('IANA, напр. Europe/Moscow'),
                ]),
                Flex::make([
                    Email::make('Email', 'email'),
                    Text::make('Телефон', 'phone'),
                ]),
                Flex::make([
                    Select::make('Валюта', 'default_currency_code')
                        ->options(['RUB' => 'RUB', 'USD' => 'USD', 'EUR' => 'EUR'])
                        ->default('RUB')
                        ->required(),
                ]),
            ]),
        ];
    }

    protected function rules(\MoonShine\Contracts\Core\TypeCasts\DataWrapperContract $item): array
    {
        return [
            'name'                 => 'required|string|max:255',
            'timezone'             => 'required|string',
            'default_currency_code'=> 'required|string|size:3',
            'email'                => 'nullable|email',
        ];
    }
}
