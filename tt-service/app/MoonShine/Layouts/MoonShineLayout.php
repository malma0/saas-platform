<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\Models\User;
use App\MoonShine\Pages\BookingCalendarPage;
use App\MoonShine\Pages\ReportsPage;
use App\MoonShine\Resources\Booking\BookingResource;
use App\MoonShine\Resources\Branch\BranchResource;
use App\MoonShine\Resources\Client\ClientResource;
use App\MoonShine\Resources\Club\ClubResource;
use App\MoonShine\Resources\Hall\HallResource;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\ServiceOffering\ServiceOfferingResource;
use App\MoonShine\Resources\Staff\StaffResource;
use App\MoonShine\Resources\Venue\VenueResource;
use MoonShine\ColorManager\ColorManager;
use MoonShine\ColorManager\Palettes\PurplePalette;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = PurplePalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    /**
     * Явное меню панели (Moonshine 4 не строит меню из атрибутов автоматически).
     * Разделы видимы по ролям; данные внутри фильтруются tenant-scope'ом.
     */
    protected function menu(): array
    {
        return [
            MenuItem::make(BookingCalendarPage::class, 'Календарь броней', 'calendar-days'),
            MenuItem::make(BookingResource::class, 'Бронирования', 'rectangle-stack'),
            MenuItem::make(ClientResource::class, 'Клиенты', 'users'),
            MenuItem::make(ReportsPage::class, 'Отчёты', 'document-chart-bar'),

            MenuGroup::make('Клуб', [
                MenuItem::make(BranchResource::class, 'Филиалы'),
                MenuItem::make(VenueResource::class, 'Объекты / Залы'),
                MenuItem::make(HallResource::class, 'Залы / Столы'),
                MenuItem::make(ResourceResource::class, 'Ресурсы'),
                MenuItem::make(ServiceOfferingResource::class, 'Услуги'),
            ], 'building-office-2'),

            MenuGroup::make('Администрирование', [
                MenuItem::make(ClubResource::class, 'Клубы')
                    ->canSee(static fn (): bool => ($u = auth()->user()) instanceof User && $u->isSuperAdmin()),
                MenuItem::make(StaffResource::class, 'Сотрудники')
                    ->canSee(static fn (): bool => ($u = auth()->user()) instanceof User && $u->hasRole(['superadmin', 'owner'])),
            ], 'cog-6-tooth'),
        ];
    }

    /**
     * @param ColorManager $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);
    }
}
