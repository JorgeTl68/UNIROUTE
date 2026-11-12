<?php

namespace Tests\Feature\Tracking;

use Tests\TestCase;

class SchedulerTest extends TestCase
{
    public function test_daily_trip_generation_is_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('uniroute:generar-viajes');
    }

    public function test_position_cleanup_is_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->assertSuccessful()
            ->expectsOutputToContain('uniroute:limpiar-posiciones');
    }
}
