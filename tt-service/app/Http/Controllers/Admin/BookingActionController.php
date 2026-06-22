<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Booking\Actions\RescheduleBooking;
use App\Domain\Booking\Models\Booking;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Перенос брони из админ-панели через безопасный RescheduleBooking
 * (с проверкой конфликтов и записью в историю).
 */
class BookingActionController extends Controller
{
    public function reschedule(Request $request, string $publicId, RescheduleBooking $action): RedirectResponse
    {
        $data = $request->validate([
            'new_start' => 'required|date',
            'new_end'   => 'required|date|after:new_start',
        ]);

        // Tenant-scope ограничивает выборку клубом текущего пользователя
        $booking = Booking::where('public_id', $publicId)->firstOrFail();

        if ($booking->isFinal()) {
            return back()->with('alert', 'Бронь в финальном статусе — перенос невозможен.');
        }

        $resourceIds = $booking->bookingResources()->pluck('resource_id')->all();

        // Время из формы — в таймзоне филиала; переводим в UTC для хранения.
        $tz = $booking->branch?->timezone ?? config('app.timezone', 'UTC');

        try {
            $action->handle(
                booking:        $booking,
                newStartAt:     Carbon::parse($data['new_start'], $tz)->utc(),
                newEndAt:       Carbon::parse($data['new_end'], $tz)->utc(),
                newResourceIds: $resourceIds,
                rescheduledBy:  auth()->id(),
            );
        } catch (\Throwable $e) {
            return back()->with('alert', 'Перенос не выполнен: ' . $e->getMessage());
        }

        return back()->with('alert', 'Бронь перенесена.');
    }
}
