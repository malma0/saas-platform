<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\FlexibleRender;

/**
 * Страница выгрузки отчётов (Фаза 11 / ТЗ Блок 9).
 * Форма GET-запросом скачивает CSV/XLSX через admin.reports.export.
 */
class ReportsPage extends Page
{
    public function getTitle(): string
    {
        return 'Отчёты';
    }

    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
    }

    protected function components(): iterable
    {
        return [
            FlexibleRender::make(
                view('admin.reports')
            ),
        ];
    }
}
