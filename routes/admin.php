<?php

use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\TransitRouteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('/admin/routes', [TransitRouteController::class, 'index'])
    ->name('admin.routes.index');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/routes/{transitRoute}/schedules', [ScheduleController::class, 'index'])->name('admin.routes.schedules.index');
    Route::post('/admin/routes/{transitRoute}/schedules', [ScheduleController::class, 'store'])->name('admin.routes.schedules.store');
    Route::patch('/admin/routes/{transitRoute}/schedules/{schedule}', [ScheduleController::class, 'update'])->name('admin.routes.schedules.update');
    Route::post('/admin/routes/{transitRoute}/schedules/generate', [ScheduleController::class, 'generate'])->name('admin.routes.schedules.generate');
});
