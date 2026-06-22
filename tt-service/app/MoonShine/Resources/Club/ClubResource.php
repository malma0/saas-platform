<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Club;

use App\Domain\ClubCore\Models\Club;
use App\MoonShine\Resources\Club\Pages\ClubFormPage;
use App\MoonShine\Resources\Club\Pages\ClubIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;

/**
 * Клубы — только для superadmin.
 * Видимость скрывается через canSee() — owner/admin не видят этот раздел.
 *
 * @extends ModelResource<Club, ClubIndexPage, ClubFormPage, null>
 */
#[Icon('building-office')]
#[Group('Система', 'cog-6-tooth')]
#[Order(0)]
class ClubResource extends ModelResource
{
    protected string $model  = Club::class;
    protected string $column = 'name';
    protected array  $with   = [];

    public function getTitle(): string
    {
        return 'Клубы';
    }

    /** Видят только superadmin (club_id === null + роль superadmin) */
    public function canSee(): bool
    {
        $user = auth()->user();

        return $user instanceof \App\Models\User && $user->isSuperAdmin();
    }

    protected function pages(): array
    {
        return [
            ClubIndexPage::class,
            ClubFormPage::class,
        ];
    }

    protected function search(): array
    {
        return ['id', 'name', 'email'];
    }
}
