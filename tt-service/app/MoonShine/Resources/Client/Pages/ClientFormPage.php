<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Client\Pages;

use App\MoonShine\Resources\Client\ClientResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/** @extends FormPage<ClientResource> */
final class ClientFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make('Личные данные', [
                ID::make(),
                Flex::make([
                    Text::make('Имя', 'first_name')->required(),
                    Text::make('Фамилия', 'last_name'),
                ]),
                Flex::make([
                    Text::make('Телефон', 'phone'),
                    Text::make('Email', 'email'),
                ]),
                Flex::make([
                    Date::make('Дата рождения', 'birth_date'),
                    Select::make('Пол', 'gender')
                        ->options(['male' => 'Мужской', 'female' => 'Женский', 'other' => 'Другой'])
                        ->nullable(),
                ]),
            ]),
            Box::make('Дополнительно', [
                Select::make('Источник', 'source')
                    ->options([
                        'walk_in'    => 'Пришёл сам',
                        'referral'   => 'Рекомендация',
                        'social'     => 'Соцсети',
                        'website'    => 'Сайт',
                        'other'      => 'Другое',
                    ])
                    ->nullable(),
                Textarea::make('Быстрая заметка', 'quick_note'),
            ]),
            Box::make('Блокировка', [
                Switcher::make('Заблокирован', 'is_blocked'),
                Textarea::make('Причина блокировки', 'block_reason'),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'nullable|string|max:100',
            'phone'        => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:255',
            'birth_date'   => 'nullable|date',
            'gender'       => 'nullable|in:male,female,other',
            'source'       => 'nullable|string|max:50',
            'quick_note'   => 'nullable|string|max:1000',
            'is_blocked'   => 'boolean',
            'block_reason' => 'nullable|string|max:500',
        ];
    }
}
