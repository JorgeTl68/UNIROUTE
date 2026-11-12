<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmergencyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Transit\ArrivalAlarmController;
use App\Http\Controllers\Transit\FavoriteRouteController;
use App\Http\Controllers\Transit\RouteController;
use App\Http\Controllers\Transit\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('routes', [RouteController::class, 'index'])->name('routes.index');
    Route::get('routes/{transitRoute}', [RouteController::class, 'show'])->name('routes.show');
    Route::post('routes/{transitRoute}/favorite', [FavoriteRouteController::class, 'store'])->name('routes.favorite.store');
    Route::delete('routes/{transitRoute}/favorite', [FavoriteRouteController::class, 'destroy'])->name('routes.favorite.destroy');

    Route::get('schedules', ScheduleController::class)->name('schedules.index');

    Route::post('arrival-alarms', [ArrivalAlarmController::class, 'store'])->name('arrival-alarms.store');
    Route::delete('arrival-alarms/{arrivalAlarm}', [ArrivalAlarmController::class, 'destroy'])->name('arrival-alarms.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('emergency/create', [EmergencyController::class, 'create'])->name('emergency.create');
    Route::post('emergency', [EmergencyController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('emergency.store');
});
