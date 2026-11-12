<?php

use App\Http\Controllers\Admin\TransitRouteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin'])
    ->get('/admin/routes', [TransitRouteController::class, 'index'])
    ->name('admin.routes.index');
