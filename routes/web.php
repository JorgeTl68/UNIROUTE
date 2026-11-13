
<?php

use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\TripAssignmentController;
use App\Http\Controllers\Admin\VehicleController;

use App\Http\Controllers\Student\ScheduleController;
use App\Http\Controllers\Student\FavoriteRouteController;
use App\Http\Controllers\Student\ArrivalAlarmController;

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

// ==========================================
// ADMINISTRACIÓN DE FLOTA
// ==========================================

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('/admin/fleet', [VehicleController::class, 'index'])
    ->name('admin.fleet.index');

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/fleet/create', [VehicleController::class, 'create'])
        ->name('admin.fleet.create');

    Route::post('/admin/fleet', [VehicleController::class, 'store'])
        ->name('admin.fleet.store');
});

// ==========================================
// CONDUCTORES Y ASIGNACIONES
// ==========================================

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/fleet/drivers', [DriverController::class, 'index'])
        ->name('admin.drivers.index');

    Route::get('/admin/fleet/drivers/create', [DriverController::class, 'create'])
        ->name('admin.drivers.create');

    Route::post('/admin/fleet/drivers', [DriverController::class, 'store'])
        ->name('admin.drivers.store');

    Route::get('/admin/fleet/assignments', [TripAssignmentController::class, 'index'])
        ->name('admin.assignments.index');

    Route::get('/admin/fleet/assignments/{trip}/edit', [TripAssignmentController::class, 'edit'])
        ->name('admin.assignments.edit');

    Route::patch('/admin/fleet/assignments/{trip}', [TripAssignmentController::class, 'update'])
        ->name('admin.assignments.update');
});

// ==========================================
// MANTENIMIENTO
// ==========================================

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/fleet/maintenance', [MaintenanceController::class, 'index'])
        ->name('admin.maintenance.index');

    Route::get('/admin/fleet/maintenance/create', [MaintenanceController::class, 'create'])
        ->name('admin.maintenance.create');

    Route::post('/admin/fleet/maintenance', [MaintenanceController::class, 'store'])
        ->name('admin.maintenance.store');

    Route::get('/admin/fleet/maintenance/{record}', [MaintenanceController::class, 'show'])
        ->whereNumber('record')
        ->name('admin.maintenance.show');

    Route::patch('/admin/fleet/maintenance/{record}/complete', [MaintenanceController::class, 'complete'])
        ->whereNumber('record')
        ->name('admin.maintenance.complete');
});

// ==========================================
// MÓDULO DEL ESTUDIANTE
// ==========================================

Route::middleware(['auth', 'verified', 'role:student'])->group(function () {

    // Horarios e itinerarios
    Route::get('/schedules', [ScheduleController::class, 'index'])
        ->name('schedules.index');

    // Detalle individual de rutas
    Route::get('/routes/{route}', [ScheduleController::class, 'show'])
        ->whereNumber('route')
        ->name('routes.show');

    // Rutas favoritas
    Route::get('/favorites', [FavoriteRouteController::class, 'index'])
        ->name('favorites.index');

    Route::post('/favorites/{route}', [FavoriteRouteController::class, 'store'])
        ->whereNumber('route')
        ->name('favorites.store');

    Route::delete('/favorites/{route}', [FavoriteRouteController::class, 'destroy'])
        ->whereNumber('route')
        ->name('favorites.destroy');

    // Alarmas de llegada
    Route::get('/arrival-alarms', [ArrivalAlarmController::class, 'index'])
        ->name('arrival-alarms.index');

    Route::post('/arrival-alarms', [ArrivalAlarmController::class, 'store'])
        ->name('arrival-alarms.store');

    Route::patch('/arrival-alarms/{alarm}', [ArrivalAlarmController::class, 'update'])
        ->whereNumber('alarm')
        ->name('arrival-alarms.update');

    Route::patch('/arrival-alarms/{alarm}/toggle', [ArrivalAlarmController::class, 'toggle'])
        ->whereNumber('alarm')
        ->name('arrival-alarms.toggle');

    Route::delete('/arrival-alarms/{alarm}', [ArrivalAlarmController::class, 'destroy'])
        ->whereNumber('alarm')
        ->name('arrival-alarms.destroy');
});
