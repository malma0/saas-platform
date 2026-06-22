<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Venue\Pages;

use App\MoonShine\Resources\Branch\BranchResource;
use App\MoonShine\Resources\Venue\VenueResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/** @extends FormPage<VenueResource> */
final class VenueFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'name')->required(),
                BelongsTo::make('Филиал', 'branch', formatted: fn($m) => $m->name, resource: BranchResource::class)
                    ->valuesQuery(fn($q) => $q->select(['id', 'name'])),
                Textarea::make('Описание', 'description'),
                Switcher::make('Активен', 'is_active')->default(true),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name'      => 'required|string|max:255',
            'branch_id' => 'required|integer|exists:branches,id',
        ];
    }
}
