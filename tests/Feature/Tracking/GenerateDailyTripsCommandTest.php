<?php

namespace Tests\Feature\Tracking;

use App\Enums\TripStatus;
use App\Models\Schedule;
use App\Models\TransitRoute;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateDailyTripsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_trips_for_the_requested_date(): void
    {
        $route = TransitRoute::factory()->create(['is_active' => true]);
        $schedule = Schedule::factory()->create([
            'route_id' => $route->id,
            'weekday' => 3,
            'departs_at' => '08:15:00',
        ]);

        $this->artisan('uniroute:generar-viajes', ['--fecha' => '2026-11-11'])
            ->assertSuccessful()
            ->expectsOutputToContain('1 nuevos, 0 ya existían');

        $trip = Trip::sole();

        $this->assertSame($route->id, $trip->route_id);
        $this->assertSame($schedule->id, $trip->schedule_id);
        $this->assertSame('2026-11-11 08:15:00', $trip->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame(TripStatus::Scheduled, $trip->status);
        $this->assertSame(0, $trip->occupancy);
    }

    public function test_is_idempotent_when_run_twice(): void
    {
        $route = TransitRoute::factory()->create(['is_active' => true]);
        Schedule::factory()->create([
            'route_id' => $route->id,
            'weekday' => 3,
            'departs_at' => '09:00:00',
        ]);

        $this->artisan('uniroute:generar-viajes', ['--fecha' => '2026-11-11'])
            ->assertSuccessful();

        $this->artisan('uniroute:generar-viajes', ['--fecha' => '2026-11-11'])
            ->assertSuccessful()
            ->expectsOutputToContain('0 nuevos, 1 ya existían');

        $this->assertDatabaseCount('trips', 1);
    }

    public function test_ignores_other_weekdays_and_inactive_routes(): void
    {
        $active = TransitRoute::factory()->create(['is_active' => true]);
        Schedule::factory()->create([
            'route_id' => $active->id,
            'weekday' => 3,
            'departs_at' => '07:00:00',
        ]);
        Schedule::factory()->create([
            'route_id' => $active->id,
            'weekday' => 4,
            'departs_at' => '07:00:00',
        ]);

        $inactive = TransitRoute::factory()->inactive()->create();
        Schedule::factory()->create([
            'route_id' => $inactive->id,
            'weekday' => 3,
            'departs_at' => '07:00:00',
        ]);

        $this->artisan('uniroute:generar-viajes', ['--fecha' => '2026-11-11'])
            ->assertSuccessful();

        $this->assertDatabaseCount('trips', 1);
        $this->assertSame($active->id, Trip::sole()->route_id);
    }

    public function test_rejects_an_invalid_date(): void
    {
        $this->artisan('uniroute:generar-viajes', ['--fecha' => '2026-13-99'])
            ->assertFailed()
            ->expectsOutputToContain('formato AAAA-MM-DD');
    }
}
