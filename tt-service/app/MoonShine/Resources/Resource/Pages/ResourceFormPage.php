<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Resource\Pages;

use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\ResourceType;
use App\MoonShine\Resources\Resource\ResourceResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Flex;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Text;

/** @extends FormPage<ResourceResource> */
final class ResourceFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Название', 'name')->required(),
                Flex::make([
                    BelongsTo::make('Тип ресурса', 'resourceType', formatted: fn($m) => $m->name, resource: \App\MoonShine\Resources\Resource\ResourceResource::class)
                        ->valuesQuery(fn($q) => $q->select(['id', 'name', 'slug'])),
                    BelongsTo::make('Филиал', 'branch', formatted: fn($m) => $m->name, resource: \App\MoonShine\Resources\Branch\BranchResource::class)
                        ->valuesQuery(fn($q) => $q->select(['id', 'name'])),
                ]),
                Flex::make([
                    Number::make('Вместимость', 'capacity')->default(1)->min(1),
                    Switcher::make('Активен', 'is_active')->default(true),
                ]),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name'             => 'required|string|max:255',
            'resource_type_id' => 'required|integer|exists:resource_types,id',
            'branch_id'        => 'required|integer|exists:branches,id',
        ];
    }
}
