<?php

use App\Http\Controllers\Admin\BookingActionController;
use App\Http\Controllers\Admin\BookingCalendarController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/schedule', [ScheduleController::class, 'index']);
Route::get('/partners', fn() => view('partners'));

// Источник событий для календарного вида броней в админ-панели.
// Под web+auth: tenant-scope ограничивает данные клубом текущего пользователя.
Route::middleware(['web', 'auth'])
    ->get('/panel/booking-calendar/events', [BookingCalendarController::class, 'events'])
    ->name('admin.booking-calendar.events');

// Перенос брони из админ-панели (через RescheduleBooking с конфликт-чеком).
Route::middleware(['web', 'auth'])
    ->post('/panel/booking/{publicId}/reschedule', [BookingActionController::class, 'reschedule'])
    ->name('admin.booking.reschedule');

// Синхронная выгрузка отчётов CSV/XLSX.
Route::middleware(['web', 'auth'])
    ->get('/panel/reports/export', [ReportsController::class, 'export'])
    ->name('admin.reports.export');
