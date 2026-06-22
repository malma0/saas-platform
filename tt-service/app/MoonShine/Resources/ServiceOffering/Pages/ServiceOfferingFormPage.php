<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ServiceOffering\Pages;

use App\MoonShine\Resources\ServiceOffering\ServiceOfferingResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/** @extends FormPage<ServiceOfferingResource> */
final class ServiceOfferingFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'name')->required(),
                Textarea::make('Описание', 'description'),
                Flex::make([
                    Number::make('Длительность (мин)', 'duration_minutes')
                        ->required()->min(5)->default(60),
                    Number::make('Вместимость (человек)', 'capacity')
                        ->required()->min(1)->default(1),
                ]),
                Switcher::make('Активна', 'is_active')->default(true),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name'             => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:5',
            'capacity'         => 'required|integer|min:1',
        ];
    }
}
