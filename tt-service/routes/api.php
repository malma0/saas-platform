<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\HoldController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| TT-Service Client API
|--------------------------------------------------------------------------
|
| Версия: v1
| Аутентификация: Laravel Sanctum (Bearer token)
|
*/

Route::prefix('v1')->group(function () {

    // ── Публичные эндпоинты ───────────────────────────────────────────────

    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login',    'login');
    });

    // Расписание и услуги доступны без авторизации (публичное расписание клуба)
    Route::get('schedule', [ScheduleController::class, 'index']);
    Route::get('services', [ServiceController::class, 'index']);

    // ── Защищённые эндпоинты (Sanctum) ───────────────────────────────────

    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);

        // Profile
        Route::get('profile',  [ProfileController::class, 'show']);
        Route::put('profile',  [ProfileController::class, 'update']);

        // Holds (временная блокировка слота на время оформления)
        Route::post('holds', [HoldController::class, 'store']);

        // Bookings
        Route::get('bookings',          [BookingController::class, 'index']);
        Route::post('bookings',         [BookingController::class, 'store']);
        Route::get('bookings/{id}',     [BookingController::class, 'show']);
        Route::delete('bookings/{id}',  [BookingController::class, 'destroy']);
    });
});
