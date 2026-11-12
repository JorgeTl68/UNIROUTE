<?php

use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\TripAssignmentController;
use App\Http\Controllers\Admin\VehicleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

require __DIR__.'/settings.php';
require __DIR__.'/transit.php';
require __DIR__.'/admin.php';

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('/admin/fleet', [VehicleController::class, 'index'])
    ->name('admin.fleet.index');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/fleet/create', [VehicleController::class, 'create'])->name('admin.fleet.create');
    Route::post('/admin/fleet', [VehicleController::class, 'store'])->name('admin.fleet.store');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/fleet/drivers', [DriverController::class, 'index'])->name('admin.drivers.index');
    Route::get('/admin/fleet/drivers/create', [DriverController::class, 'create'])->name('admin.drivers.create');
    Route::post('/admin/fleet/drivers', [DriverController::class, 'store'])->name('admin.drivers.store');
    Route::get('/admin/fleet/assignments', [TripAssignmentController::class, 'index'])->name('admin.assignments.index');
    Route::get('/admin/fleet/assignments/{trip}/edit', [TripAssignmentController::class, 'edit'])->name('admin.assignments.edit');
    Route::patch('/admin/fleet/assignments/{trip}', [TripAssignmentController::class, 'update'])->name('admin.assignments.update');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/fleet/maintenance', [MaintenanceController::class, 'index'])->name('admin.maintenance.index');
    Route::get('/admin/fleet/maintenance/create', [MaintenanceController::class, 'create'])->name('admin.maintenance.create');
    Route::post('/admin/fleet/maintenance', [MaintenanceController::class, 'store'])->name('admin.maintenance.store');
    Route::get('/admin/fleet/maintenance/{record}', [MaintenanceController::class, 'show'])->whereNumber('record')->name('admin.maintenance.show');
    Route::patch('/admin/fleet/maintenance/{record}/complete', [MaintenanceController::class, 'complete'])->whereNumber('record')->name('admin.maintenance.complete');
});
