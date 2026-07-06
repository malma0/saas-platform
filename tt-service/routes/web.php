<?php

use App\Http\Controllers\Admin\AdminOccupancyController;
use App\Http\Controllers\Admin\BookingActionController;
use App\Http\Controllers\Admin\BookingCalendarController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BookingController as WebBookingController;
use App\Http\Controllers\Web\CoachController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\KtokudaController;
use App\Http\Controllers\Web\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/schedule', [ScheduleController::class, 'index']);
Route::get('/partners', fn() => view(\App\Support\Device::isMobile() ? 'mobile.partners' : 'partners'));

// Гостевое бронирование с сайта (сайт → БД → MoonShine).
Route::post('/book', [WebBookingController::class, 'store'])->name('web.book');

// Тренеры: список + запись на индивидуальную тренировку.
Route::get('/coaches', [CoachController::class, 'index'])->name('web.coaches');
Route::post('/coaches/book', [CoachController::class, 'book'])->name('web.coaches.book');

// Вход клиента (сейчас по телефону, задел под e-mail+пароль).
Route::post('/auth/login', [AuthController::class, 'login'])->name('web.auth.login');

// Регистрация нового клиента + автоматическая регистрация в КтоКуда.
Route::post('/auth/register', [AuthController::class, 'register'])->name('web.auth.register');

// Личный кабинет клиента (вход по номеру телефона).
Route::get('/account', [AccountController::class, 'index'])->name('web.account');

// КтоКуда: события и привязка аккаунта.
Route::prefix('ktokyda')->group(function () {
    Route::get('/events',                        [KtokudaController::class, 'events'])->name('ktokyda.events');
    Route::post('/register',                     [KtokudaController::class, 'register'])->name('ktokyda.register');
    Route::post('/link',                         [KtokudaController::class, 'link'])->name('ktokyda.link');
    Route::post('/unlink',                       [KtokudaController::class, 'unlink'])->name('ktokyda.unlink');
    Route::post('/event/{eventId}/signup',       [KtokudaController::class, 'signup'])->name('ktokyda.signup');
    Route::post('/event/{eventId}/signout',      [KtokudaController::class, 'signout'])->name('ktokyda.signout');
});

// Источник событий для календарного вида броней в админ-панели.
// Под web+auth: tenant-scope ограничивает данные клубом текущего пользователя.
Route::middleware(['web', 'auth'])
    ->get('/panel/booking-calendar/events', [BookingCalendarController::class, 'events'])
    ->name('admin.booking-calendar.events');

// Сетка занятости столов в админке: данные сетки + бронирование кликом
// + действия над бронью прямо из сетки (подтвердить/отменить/посещение/оплата/перенос).
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/panel/occupancy/grid', [AdminOccupancyController::class, 'grid'])
        ->name('admin.occupancy.grid');
    Route::post('/panel/occupancy/book', [AdminOccupancyController::class, 'book'])
        ->name('admin.occupancy.book');

    Route::post('/panel/occupancy/booking/{publicId}/confirm',    [AdminOccupancyController::class, 'confirm'])->name('admin.occupancy.confirm');
    Route::post('/panel/occupancy/booking/{publicId}/cancel',     [AdminOccupancyController::class, 'cancel'])->name('admin.occupancy.cancel');
    Route::post('/panel/occupancy/booking/{publicId}/present',    [AdminOccupancyController::class, 'present'])->name('admin.occupancy.present');
    Route::post('/panel/occupancy/booking/{publicId}/no-show',    [AdminOccupancyController::class, 'noShow'])->name('admin.occupancy.no-show');
    Route::post('/panel/occupancy/booking/{publicId}/paid',       [AdminOccupancyController::class, 'paid'])->name('admin.occupancy.paid');
    Route::post('/panel/occupancy/booking/{publicId}/reschedule', [AdminOccupancyController::class, 'reschedule'])->name('admin.occupancy.reschedule');
});

// Перенос брони из админ-панели (через RescheduleBooking с конфликт-чеком).
Route::middleware(['web', 'auth'])
    ->post('/panel/booking/{publicId}/reschedule', [BookingActionController::class, 'reschedule'])
    ->name('admin.booking.reschedule');

// Синхронная выгрузка отчётов CSV/XLSX.
Route::middleware(['web', 'auth'])
    ->get('/panel/reports/export', [ReportsController::class, 'export'])
    ->name('admin.reports.export');
