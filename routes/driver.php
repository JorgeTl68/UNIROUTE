<?php

use App\Http\Controllers\Driver\PositionController;
use App\Http\Controllers\Driver\TripController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('/', [TripController::class, 'show'])->name('trip');
    Route::post('trips/{trip}/start', [TripController::class, 'start'])->name('trips.start');
    Route::post('trips/{trip}/finish', [TripController::class, 'finish'])->name('trips.finish');
    Route::patch('trips/{trip}/occupancy', [TripController::class, 'occupancy'])->name('trips.occupancy');
    Route::post('trips/{trip}/positions', [PositionController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('trips.positions.store');
});
