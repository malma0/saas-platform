<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Facilities\Models\Branch;
use App\Domain\Facilities\Models\Coach;
use App\Domain\Services\Models\ServiceOffering;
use App\Http\Controllers\Controller;
use App\Support\Device;
use Illuminate\Contracts\View\View;

/**
 * Публичная главная страница (лендинг).
 *
 * Server-side рендер: контроллер достаёт реальные данные клуба из БД
 * и передаёт их в Blade. Страница публичная — tenant-scope не применяется
 * (гость видит все живые записи), поэтому авторизация не требуется.
 */
class HomeController extends Controller
{
    public function index(): View
    {
        // Активный филиал + его часы работы (для формы брони и контактов).
        $branch = Branch::query()
            ->where('is_active', true)
            ->with('workingHours')
            ->first();

        // Активные услуги клуба (наполняют выбор в форме брони).
        $services = ServiceOffering::query()
            ->where('is_active', true)
            ->orderBy('duration_minutes')
            ->get(['id', 'public_id', 'name', 'duration_minutes', 'capacity']);

        // Границы рабочего дня — берём типичный будний день (Пн), чтобы
        // ограничить выпадашку времени реальными часами работы филиала.
        $weekdayHours = $branch?->getWorkingHourForDay(1); // 1 = понедельник
        $openHour  = $weekdayHours && $weekdayHours->open_time
            ? (int) substr((string) $weekdayHours->open_time, 0, 2)
            : 9;
        $closeHour = $weekdayHours && $weekdayHours->close_time
            ? (int) substr((string) $weekdayHours->close_time, 0, 2)
            : 22;

        $coaches = Coach::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(2)
            ->get();

        return view(Device::isMobile() ? 'mobile.home' : 'welcome', [
            'branch'    => $branch,
            'services'  => $services,
            'openHour'  => $openHour,
            'closeHour' => $closeHour,
            'coaches'   => $coaches,
        ]);
    }
}
