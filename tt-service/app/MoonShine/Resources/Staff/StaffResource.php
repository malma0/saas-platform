<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Staff;

use App\Models\User;
use App\MoonShine\Resources\Staff\Pages\StaffFormPage;
use App\MoonShine\Resources\Staff\Pages\StaffIndexPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MenuManager\Attributes\Group;
use MoonShine\MenuManager\Attributes\Order;
use MoonShine\Support\Attributes\Icon;

/**
 * @extends ModelResource<User, StaffIndexPage, StaffFormPage, null>
 */
#[Icon('user-group')]
#[Group('Администрирование', 'cog-6-tooth')]
#[Order(0)]
class StaffResource extends ModelResource
{
    protected string $model  = User::class;
    protected string $column = 'name';

    public function getTitle(): string { return 'Сотрудники'; }

    /** Управление сотрудниками — owner и superadmin */
    public function canSee(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasRole(['superadmin', 'owner']);
    }

    protected function pages(): array
    {
        return [StaffIndexPage::class, StaffFormPage::class];
    }

    protected function search(): array
    {
        return ['id', 'name', 'email'];
    }
}
