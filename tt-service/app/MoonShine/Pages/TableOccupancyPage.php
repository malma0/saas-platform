<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;

/**
 * Сетка занятости столов для админа.
 *
 * Тот же вид «столы × время», что и на сайте: видно занятость на выбранную
 * дату, бронь оформляется кликом по свободной ячейке. Данные и создание брони —
 * через route('admin.occupancy.grid') и route('admin.occupancy.book').
 */
class TableOccupancyPage extends Page
{
    public function getTitle(): string
    {
        return 'Сетка столов';
    }

    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
    }

    protected function components(): iterable
    {
        return [
            FlexibleRender::make(
                view('admin.table-occupancy', [
                    'gridUrl'    => route('admin.occupancy.grid'),
                    'bookUrl'    => route('admin.occupancy.book'),
                    'actionBase' => url('/panel/occupancy/booking'),
                    'csrf'       => csrf_token(),
                ])
            ),
        ];
    }
}
