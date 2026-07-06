<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Coach;

use App\Domain\Facilities\Models\Coach;
use App\MoonShine\Resources\Coach\Pages\CoachFormPage;
use App\MoonShine\Resources\Coach\Pages\CoachIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<Coach, CoachIndexPage, CoachFormPage, null>
 */
#[Icon('academic-cap')]
#[Order(4)]
class CoachResource extends ModelResource
{
    protected string $model  = Coach::class;
    protected string $column = 'name';
    protected array  $with   = ['branch'];

    public function getTitle(): string { return 'Тренеры'; }

    protected function pages(): array
    {
        return [CoachIndexPage::class, CoachFormPage::class];
    }

    protected function search(): array { return ['id', 'name', 'specialization']; }
}
