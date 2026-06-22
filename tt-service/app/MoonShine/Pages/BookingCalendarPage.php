<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Resource;
use App\Domain\Identity\Services\AccessControlService;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;

/**
 * Календарный вид броней (Фаза 12).
 *
 * Рендерит FullCalendar с фильтрами по филиалу и ресурсу (ТЗ Блок 5).
 * События тянутся из route('admin.booking-calendar.events') — с tenant-фильтрацией
 * и ограничением user_branch_access для админов.
 */
class BookingCalendarPage extends Page
{
    public function getTitle(): string
    {
        return 'Календарь броней';
    }

    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
    }

    protected function components(): iterable
    {
        $allowedBranchIds = app(AccessControlService::class)
            ->accessibleBranchIds(auth()->user());

        $branches = Branch::query()
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('id', $allowedBranchIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        $resources = Resource::query()
            ->where('is_active', true)
            ->when($allowedBranchIds !== null, fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name']);

        return [
            FlexibleRender::make(
                view('admin.booking-calendar', [
                    'branches'  => $branches,
                    'resources' => $resources,
                ])
            ),
        ];
    }
}
