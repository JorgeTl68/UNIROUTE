<?php

namespace Tests\Feature\Tracking;

use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use App\Services\Tracking\Polyline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulateFleetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulates_units_along_the_encoded_polyline(): void
    {
        $route = TransitRoute::factory()->create([
            'encoded_polyline' => Polyline::encode([
                [25.40, -101.00],
                [25.42, -101.02],
                [25.44, -101.04],
            ]),
        ]);
        Vehicle::factory()->count(2)->create();

        $this->artisan('uniroute:simular', [
            '--rutas' => (string) $route->id,
            '--intervalo' => 0,
            '--unidades' => 2,
            '--iteraciones' => 1,
        ])->assertSuccessful();

        $positions = VehiclePosition::all();

        $this->assertCount(2, $positions);

        foreach ($positions as $position) {
            $this->assertGreaterThanOrEqual(25.39, $position->latitude);
            $this->assertLessThanOrEqual(25.45, $position->latitude);
            $this->assertGreaterThanOrEqual(-101.05, $position->longitude);
            $this->assertLessThanOrEqual(-100.99, $position->longitude);
            $this->assertNotNull($position->speed_kmh);
            $this->assertGreaterThan(0, $position->speed_kmh);
            $this->assertGreaterThanOrEqual(0, $position->heading);
            $this->assertLessThanOrEqual(359, $position->heading);
        }
    }

    public function test_falls_back_to_stops_when_the_route_has_no_polyline(): void
    {
        $route = TransitRoute::factory()->create(['encoded_polyline' => null]);
        $first = Stop::factory()->create(['latitude' => 25.40, 'longitude' => -101.00]);
        $second = Stop::factory()->create(['latitude' => 25.42, 'longitude' => -101.02]);

        $route->stops()->attach([
            $first->id => ['position' => 1, 'minutes_from_start' => 0],
            $second->id => ['position' => 2, 'minutes_from_start' => 10],
        ]);
        Vehicle::factory()->create();

        $this->artisan('uniroute:simular', [
            '--rutas' => (string) $route->id,
            '--intervalo' => 0,
            '--unidades' => 1,
            '--iteraciones' => 1,
        ])->assertSuccessful();

        $this->assertDatabaseCount('vehicle_positions', 1);
    }

    public function test_fails_when_there_are_no_available_vehicles(): void
    {
        $route = TransitRoute::factory()->create([
            'encoded_polyline' => Polyline::encode([[25.40, -101.00], [25.42, -101.02]]),
        ]);

        $this->artisan('uniroute:simular', [
            '--rutas' => (string) $route->id,
            '--intervalo' => 0,
            '--iteraciones' => 1,
        ])
            ->assertFailed()
            ->expectsOutputToContain('No hay unidades disponibles');
    }

    public function test_rejects_zero_interval_without_iterations(): void
    {
        $route = TransitRoute::factory()->create([
            'encoded_polyline' => Polyline::encode([[25.40, -101.00], [25.42, -101.02]]),
        ]);
        Vehicle::factory()->create();

        $this->artisan('uniroute:simular', [
            '--rutas' => (string) $route->id,
            '--intervalo' => 0,
        ])
            ->assertFailed()
            ->expectsOutputToContain('bucle infinito');
    }

    public function test_fails_when_the_route_filter_matches_nothing(): void
    {
        Vehicle::factory()->create();

        $this->artisan('uniroute:simular', [
            '--rutas' => '99999',
            '--intervalo' => 0,
            '--iteraciones' => 1,
        ])
            ->assertFailed()
            ->expectsOutputToContain('No hay rutas activas');
    }
}
