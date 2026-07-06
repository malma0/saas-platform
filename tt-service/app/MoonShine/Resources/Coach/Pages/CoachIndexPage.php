<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Coach\Pages;

use App\MoonShine\Resources\Coach\CoachResource;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Number;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/** @extends IndexPage<CoachResource> */
final class CoachIndexPage extends IndexPage
{
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Тренер', 'name')->sortable(),
            Text::make('Разряд', 'rank'),
            Text::make('Специализация', 'specialization'),
            Number::make('Опыт, лет', 'experience_years')->sortable(),
            Number::make('Ставка, ₽/ч', 'hourly_rate_rub')->sortable(),
            Switcher::make('Активен', 'is_active'),
        ];
    }
}
