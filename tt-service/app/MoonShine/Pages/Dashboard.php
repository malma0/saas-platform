<?php

declare(strict_types=1);

namespace App\MoonShine\Pages;

use App\Domain\Booking\Models\Booking;
use App\Domain\Crm\Models\Client;
use App\Domain\Payments\Models\Payment;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Page;
use MoonShine\UI\Components\Metrics\Wrapped\ValueMetric;

#[\MoonShine\MenuManager\Attributes\SkipMenu]
class Dashboard extends Page
{
    public function getBreadcrumbs(): array
    {
        return ['#' => $this->getTitle()];
    }

    public function getTitle(): string
    {
        return $this->title ?: 'Дашборд';
    }

    /**
     * @return list<ComponentContract>
     */
    protected function components(): iterable
    {
        $today     = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();

        $todayTotal     = Booking::whereDate('start_at', $today)->count();
        $todayConfirmed = Booking::whereDate('start_at', $today)->where('status', 'confirmed')->count();
        $todayCancelled = Booking::whereDate('start_at', $today)->where('status', 'cancelled')->count();
        $pendingCount   = Booking::where('status', 'pending')->count();

        $monthRevenueMinor = Payment::where('status', 'paid')
            ->whereDate('created_at', '>=', $thisMonth)
            ->sum('amount_minor');
        $monthRevenue = number_format((int) $monthRevenueMinor / 100, 0, '.', ' ');

        // Через модель: tenant-scope ограничивает счётчик клубом пользователя
        $newClients = Client::whereDate('created_at', '>=', $thisMonth)->count();

        return [
            ValueMetric::make('Бронирований сегодня')->value((string) $todayTotal),
            ValueMetric::make('Подтверждено сегодня')->value((string) $todayConfirmed),
            ValueMetric::make('Отменено сегодня')->value((string) $todayCancelled),
            ValueMetric::make('Ожидают подтверждения')->value((string) $pendingCount),
            ValueMetric::make('Выручка за месяц (₽)')->value($monthRevenue),
            ValueMetric::make('Новых клиентов за месяц')->value((string) $newClients),
        ];
    }
}
