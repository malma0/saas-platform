<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Branch\Pages;

use App\MoonShine\Resources\Branch\BranchResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Email;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/** @extends FormPage<BranchResource> */
final class BranchFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'name')->required(),
                Flex::make([
                    Text::make('Часовой пояс', 'timezone')
                        ->default('Europe/Moscow')
                        ->required()
                        ->hint('IANA, напр. Europe/Moscow'),
                    Text::make('Телефон', 'phone'),
                ]),
                Flex::make([
                    Email::make('Email', 'email'),
                    Text::make('Адрес', 'address'),
                ]),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name'     => 'required|string|max:255',
            'timezone' => 'required|string',
        ];
    }
}
