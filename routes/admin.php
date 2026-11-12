<?php

use App\Http\Controllers\Admin\TransitRouteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/routes', [TransitRouteController::class, 'index'])->name('admin.routes.index');
    Route::get('/admin/routes/create', [TransitRouteController::class, 'create'])->name('admin.routes.create');
    // El servidor público de OSRM acepta una consulta por segundo; se limita
    // a 10 por minuto para no saturarlo con clics repetidos.
    Route::post('/admin/routes/trace', [TransitRouteController::class, 'trace'])
        ->middleware('throttle:10,1')
        ->name('admin.routes.trace');
    Route::post('/admin/routes', [TransitRouteController::class, 'store'])->name('admin.routes.store');
});
