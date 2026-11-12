<?php

use App\Http\Controllers\Admin\TransitRouteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/routes', [TransitRouteController::class, 'index'])->name('admin.routes.index');
    Route::get('/admin/routes/create', [TransitRouteController::class, 'create'])->name('admin.routes.create');
    Route::post('/admin/routes/trace', [TransitRouteController::class, 'trace'])->name('admin.routes.trace');
    Route::post('/admin/routes', [TransitRouteController::class, 'store'])->name('admin.routes.store');
});
