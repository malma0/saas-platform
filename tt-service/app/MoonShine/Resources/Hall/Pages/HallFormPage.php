<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Hall\Pages;

use App\MoonShine\Resources\Hall\HallResource;
use App\MoonShine\Resources\Venue\VenueResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/** @extends FormPage<HallResource> */
final class HallFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'name')->required(),
                BelongsTo::make('Объект', 'venue', formatted: fn($m) => $m->name, resource: VenueResource::class)
                    ->valuesQuery(fn($q) => $q->select(['id', 'name'])),
                Flex::make([
                    Number::make('Вместимость', 'capacity')->default(2)->min(1),
                    Switcher::make('Активен', 'is_active')->default(true),
                ]),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name'     => 'required|string|max:255',
            'venue_id' => 'required|integer|exists:venues,id',
        ];
    }
}
