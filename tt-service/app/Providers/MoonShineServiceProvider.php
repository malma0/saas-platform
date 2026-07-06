<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Pages\BookingCalendarPage;
use App\MoonShine\Pages\ReportsPage;
use App\MoonShine\Pages\TableOccupancyPage;
use App\MoonShine\Resources\Booking\BookingResource;
use App\MoonShine\Resources\Branch\BranchResource;
use App\MoonShine\Resources\Client\ClientResource;
use App\MoonShine\Resources\Club\ClubResource;
use App\MoonShine\Resources\Coach\CoachResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\Hall\HallResource;
use App\MoonShine\Resources\Resource\ResourceResource;
use App\MoonShine\Resources\ServiceOffering\ServiceOfferingResource;
use App\MoonShine\Resources\Staff\StaffResource;
use App\MoonShine\Resources\Venue\VenueResource;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Laravel\DependencyInjection\MoonShine;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;
use MoonShine\Support\Enums\Ability;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                // Moonshine built-in
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,

                // Club structure (superadmin only)
                ClubResource::class,

                // Club management
                BranchResource::class,
                VenueResource::class,
                HallResource::class,
                ResourceResource::class,
                ServiceOfferingResource::class,
                CoachResource::class,

                // Operations
                BookingResource::class,

                // CRM
                ClientResource::class,

                // Administration
                StaffResource::class,
            ])
            ->pages([
                ...$core->getConfig()->getPages(),
                TableOccupancyPage::class,
                BookingCalendarPage::class,
                ReportsPage::class,
            ])
        ;

        // ──────────────────────────────────────────────────────────────────
        // RBAC: доступ к разделам по ролям (Фаза 2 + Фаза 12).
        // Правило срабатывает на ЛЮБОЕ действие ресурса (просмотр/создание/
        // правка/удаление) — закрывает доступ и по прямой ссылке, не только в меню.
        // ──────────────────────────────────────────────────────────────────
        moonshineConfig()->authorizationRules(
            static function (ResourceContract $resource, $user, Ability $ability, $item): bool {
                if (! $user instanceof User) {
                    return false;
                }

                // Суперадмин — полный доступ ко всему
                if ($user->hasRole('superadmin')) {
                    return true;
                }

                $class  = $resource::class;
                $isView = in_array($ability, [Ability::VIEW, Ability::VIEW_ANY], true);

                // Клубы — только суперадмин (Фаза 12)
                if ($class === ClubResource::class) {
                    return false;
                }

                // Сотрудники — владелец + суперадмин (Фаза 12)
                if ($class === StaffResource::class) {
                    return $user->hasRole('owner');
                }

                // Структура клуба и услуги: владелец — полный доступ,
                // админ — только просмотр (Фаза 2: resources.view)
                $clubStructure = [
                    BranchResource::class, VenueResource::class, HallResource::class,
                    ResourceResource::class, ServiceOfferingResource::class, CoachResource::class,
                ];
                if (in_array($class, $clubStructure, true)) {
                    if ($user->hasRole('owner')) {
                        return true;
                    }
                    if ($user->hasRole('admin')) {
                        return $isView;
                    }
                    return false;
                }

                // Бронирования, Клиенты и пр. — администратор и владелец
                return $user->hasAnyRole(['owner', 'admin']);
            }
        );
    }
}
