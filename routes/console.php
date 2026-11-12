<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('uniroute:generar-viajes')
    ->dailyAt('00:10')
    ->withoutOverlapping();

Schedule::command('uniroute:limpiar-posiciones')
    ->dailyAt('03:00')
    ->withoutOverlapping();
