<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Coach\Pages;

use App\MoonShine\Resources\Branch\BranchResource;
use App\MoonShine\Resources\Coach\CoachResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/** @extends FormPage<CoachResource> */
final class CoachFormPage extends FormPage
{
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make(),
                Text::make('Имя тренера', 'name')->required(),
                BelongsTo::make('Филиал', 'branch', formatted: fn ($m) => $m->name, resource: BranchResource::class)
                    ->valuesQuery(fn ($q) => $q->select(['id', 'name'])),
                Text::make('Разряд', 'rank')->hint('МС, КМС, 1 разряд…'),
                Text::make('Специализация', 'specialization'),
                Number::make('Опыт, лет', 'experience_years')->default(0),
                Number::make('Ставка, ₽/час', 'hourly_rate_rub')->required()->hint('Цена за 60 минут'),
                Textarea::make('О тренере', 'bio'),
                Number::make('Порядок', 'sort_order')->default(0),
                Switcher::make('Активен', 'is_active')->default(true),
            ]),
        ];
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [
            'name'           => 'required|string|max:255',
            'branch_id'      => 'required|integer|exists:branches,id',
            'hourly_rate_rub' => 'required|numeric|min:0',
        ];
    }
}
