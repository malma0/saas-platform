<?php

namespace Database\Seeders;

use App\Domain\Booking\Actions\CreateBooking;
use App\Domain\Booking\DTO\CreateBookingDTO;
use App\Domain\ClubCore\Models\Club;
use App\Domain\Facilities\Actions\CreateBranch;
use App\Domain\Facilities\Actions\CreateResource;
use App\Domain\Facilities\DTO\CreateBranchDTO;
use App\Domain\Facilities\DTO\CreateResourceDTO;
use App\Domain\Services\Actions\CreateService;
use App\Domain\Services\DTO\CreateServiceDTO;
use App\Domain\Facilities\Models\Hall;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Facilities\Models\ResourceType;
use App\Domain\Facilities\Models\Venue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Все права системы, сгруппированные по модулям.
     */
    private array $permissions = [
        // Клубы
        'clubs.view', 'clubs.create', 'clubs.edit', 'clubs.delete',
        // Филиалы
        'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
        // Ресурсы (столы, залы, тренеры)
        'resources.view', 'resources.create', 'resources.edit', 'resources.delete',
        // Услуги
        'services.view', 'services.create', 'services.edit', 'services.delete',
        // Бронирования
        'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.cancel',
        // Клиенты
        'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
        // Отчёты
        'reports.view', 'reports.export',
        // Платежи
        'payments.view', 'payments.refund',
        // Персонал
        'staff.view', 'staff.manage', 'staff.assign_roles',
        // Расписание
        'scheduling.view', 'scheduling.manage',
    ];

    public function run(): void
    {
        // Сброс кэша ролей/прав
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Создать все права
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // --- Роль: superadmin ---
        // Права выдаются через Gate::before — явный список не нужен
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        // --- Роль: owner ---
        // Всё в своём клубе, кроме clubs.create
        $ownerPermissions = collect($this->permissions)
            ->reject(fn ($p) => $p === 'clubs.create')
            ->values()
            ->toArray();

        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $ownerRole->syncPermissions($ownerPermissions);

        // --- Роль: admin ---
        // Основные операционные права
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.cancel',
            'clients.view', 'clients.create', 'clients.edit',
            'resources.view',
            'reports.view',
            'scheduling.view',
            'staff.view',
        ]);

        // --- Роль: user (клиент — legacy alias) ---
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $userRole->syncPermissions([
            'bookings.view',
            'scheduling.view',
        ]);

        // --- Роль: client (клиент через API) ---
        $clientRole = Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $clientRole->syncPermissions([
            'bookings.view',
            'bookings.create',
            'bookings.cancel',
            'scheduling.view',
        ]);

        // --- Тестовый superadmin ---
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@tt-service.local'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('superadmin123'),
                'club_id'  => null,
            ]
        );
        $superadmin->assignRole('superadmin');

        // --- Тестовый клуб и owner ---
        $demoClub = Club::firstOrCreate(
            ['name' => 'Demo TT Club'],
            [
                'email'                 => 'demo@tt-service.local',
                'default_currency_code' => 'RUB',
                'default_locale'        => 'ru',
                'timezone'              => 'Europe/Moscow',
            ]
        );

        $owner = User::firstOrCreate(
            ['email' => 'owner@tt-service.local'],
            [
                'name'     => 'Demo Owner',
                'password' => Hash::make('owner123'),
                'club_id'  => $demoClub->id,
            ]
        );
        $owner->assignRole('owner');

        $admin = User::firstOrCreate(
            ['email' => 'admin@tt-service.local'],
            [
                'name'     => 'Demo Admin',
                'password' => Hash::make('admin123'),
                'club_id'  => $demoClub->id,
            ]
        );
        $admin->assignRole('admin');

        // --- Демо-филиал, зал, столы ---
        $branch = (new CreateBranch())->handle(new CreateBranchDTO(
            clubId:   $demoClub->id,
            name:     'Центральный филиал',
            address:  'г. Москва, ул. Примерная, 1',
            timezone: 'Europe/Moscow',
        ));

        $venue = Venue::firstOrCreate(
            ['branch_id' => $branch->id, 'name' => 'Основной зал'],
            ['club_id' => $demoClub->id, 'description' => '12 столов для настольного тенниса']
        );

        foreach (range(1, 6) as $i) {
            Hall::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => "Стол $i"],
                ['club_id' => $demoClub->id, 'capacity' => 2]
            );
        }

        // --- Типы ресурсов и ресурсы (Фаза 4) ---
        $typeTable = ResourceType::firstOrCreate(
            ['club_id' => $demoClub->id, 'slug' => 'table'],
            ['name' => 'Стол']
        );
        $typeHall = ResourceType::firstOrCreate(
            ['club_id' => $demoClub->id, 'slug' => 'hall'],
            ['name' => 'Зал']
        );

        $createResource = new CreateResource();

        // Зал (родитель)
        $hallResource = $createResource->handle(new CreateResourceDTO(
            clubId:         $demoClub->id,
            branchId:       $branch->id,
            resourceTypeId: $typeHall->id,
            name:           'Основной зал',
            parentId:       null,
            capacity:       20,
        ));

        // 6 столов внутри зала
        foreach (range(1, 6) as $i) {
            $createResource->handle(new CreateResourceDTO(
                clubId:         $demoClub->id,
                branchId:       $branch->id,
                resourceTypeId: $typeTable->id,
                name:           "Стол $i",
                parentId:       $hallResource->id,
                capacity:       2,
            ));
        }

        // --- Демо-услуги (Фаза 5) ---
        $createSvc = new CreateService();

        // 1. Аренда стола: 500 руб будни, 700 руб выходные (сб=6, вс=0)
        $tableRental = $createSvc->handle(new CreateServiceDTO(
            clubId:           $demoClub->id,
            name:             'Аренда стола',
            durationMinutes:  60,
            capacity:         2,
            description:      'Аренда стола на 1 час',
            resourceRequirements: [
                ['resource_type_id' => $typeTable->id, 'quantity' => 1],
            ],
            pricingRules: [
                ['amount_minor' => 50000, 'currency_code' => 'RUB', 'priority' => 0],
                ['amount_minor' => 70000, 'currency_code' => 'RUB', 'day_of_week' => 6, 'priority' => 10],
                ['amount_minor' => 70000, 'currency_code' => 'RUB', 'day_of_week' => 0, 'priority' => 10],
                // Пиковые часы пт-вс 18:00-22:00 — 900 руб
                ['amount_minor' => 90000, 'currency_code' => 'RUB', 'time_from' => '18:00', 'time_to' => '22:00', 'priority' => 20],
            ],
        ));

        // 2. Индивидуальная тренировка: стол + тренер
        $createSvc->handle(new CreateServiceDTO(
            clubId:           $demoClub->id,
            name:             'Индивидуальная тренировка',
            durationMinutes:  60,
            capacity:         1,
            description:      'Тренировка с тренером 1-на-1',
            resourceRequirements: [
                ['resource_type_id' => $typeTable->id, 'quantity' => 1],
            ],
            pricingRules: [
                ['amount_minor' => 150000, 'currency_code' => 'RUB', 'priority' => 0],
            ],
        ));

        // 3. Аренда зала целиком
        $createSvc->handle(new CreateServiceDTO(
            clubId:           $demoClub->id,
            name:             'Аренда зала',
            durationMinutes:  60,
            capacity:         20,
            description:      'Весь зал в распоряжение (6 столов)',
            resourceRequirements: [
                ['resource_type_id' => $typeHall->id, 'quantity' => 1],
            ],
            pricingRules: [
                ['amount_minor' => 250000, 'currency_code' => 'RUB', 'priority' => 0],
            ],
        ));

        // -----------------------------------------------------------------------
        // Демо-бронирования (сегодня и завтра)
        // -----------------------------------------------------------------------
        /** @var \App\Domain\Services\Models\ServiceOffering $svcTable */
        $svcTable = \App\Domain\Services\Models\ServiceOffering::where('club_id', $demoClub->id)->where('name', 'Аренда стола')->first();
        $adminUser = User::where('email', 'admin@tt-service.local')->first();
        $createBooking = new CreateBooking();

        // Получаем первые 3 стола
        $tables = Resource::where('club_id', $demoClub->id)
            ->whereHas('resourceType', fn ($q) => $q->where('slug', 'table'))
            ->orderBy('id')->take(3)->get();

        $today = Carbon::today($branch->timezone);

        foreach ($tables as $i => $table) {
            $hour = 10 + $i * 2; // 10:00, 12:00, 14:00
            $startLocal = $today->copy()->setTime($hour, 0);
            $startUtc   = $startLocal->copy()->utc();
            try {
                $createBooking->handle(new CreateBookingDTO(
                    clubId:            $demoClub->id,
                    branchId:          $branch->id,
                    serviceOfferingId: $svcTable->id,
                    resourceIds:       [$table->id],
                    startAt:           $startUtc,
                    endAt:             $startUtc->copy()->addHour(),
                    amountMinor:       50000,
                    currencyCode:      'RUB',
                    adminId:           $adminUser?->id,
                    notes:             'Демо-бронирование',
                ));
            } catch (\Throwable) {
                // Слот уже занят — пропускаем
            }
        }

        $this->command->info('✅ Roles, permissions and test users created.');
        $this->command->info('   superadmin: superadmin@tt-service.local / superadmin123');
        $this->command->info('   owner:      owner@tt-service.local / owner123');
        $this->command->info('   admin:      admin@tt-service.local / admin123');
        $this->command->info("   Demo club:  '{$demoClub->name}' → '{$branch->name}' → '{$venue->name}' (6 столов)");
        $this->command->info('   Demo bookings: 3 бронирования на сегодня (10:00, 12:00, 14:00 МСК)');
    }
}
