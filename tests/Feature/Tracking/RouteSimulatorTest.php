<?php

namespace Tests\Feature\Tracking;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\Tracking\RoutePath;
use App\Services\Tracking\RouteSimulator;
use App\Services\Tracking\SimulatedUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteSimulatorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trazado recto hacia el norte de ~1 km con paradas al inicio, a la mitad
     * y al final.
     *
     * @return array{0: RoutePath, 1: list<float>}
     */
    private function straightRoute(): array
    {
        $path = RoutePath::fromPoints([[25.4000, -101.0000], [25.4090, -101.0000]]);
        $this->assertNotNull($path);
        $length = $path->lengthMeters();

        return [$path, [0.0, $length / 2, $length]];
    }

    public function test_the_unit_stops_at_the_next_stop_and_waits_there(): void
    {
        [$path, $stops] = $this->straightRoute();
        $unit = new SimulatedUnit(Vehicle::factory()->create(), null, $path, 10.0, $stops[1] - 100, $stops);

        $arrival = $unit->advance(60);

        $this->assertSame(1, $arrival['arrived_at_stop']);
        $this->assertEqualsWithDelta($stops[1], $unit->progressMeters, 0.01);
        $this->assertGreaterThan(0, $arrival['speed_kmh']);

        $waiting = $unit->advance(5);

        $this->assertNull($waiting['arrived_at_stop']);
        $this->assertSame(0.0, $waiting['speed_kmh']);
        $this->assertEqualsWithDelta($stops[1], $unit->progressMeters, 0.01);
    }

    public function test_the_unit_brakes_before_a_stop_instead_of_keeping_full_speed(): void
    {
        [$path, $stops] = $this->straightRoute();
        $unit = new SimulatedUnit(Vehicle::factory()->create(), null, $path, 10.0, $stops[1] - 120, $stops);

        $step = $unit->advance(5);

        // A crucero haría ~36 km/h; cerca de la parada va más despacio.
        $this->assertLessThan(36, $step['speed_kmh']);
        $this->assertNull($step['arrived_at_stop']);
    }

    public function test_passengers_board_at_stops_and_everyone_gets_off_at_the_last_one(): void
    {
        [$path, $stops] = $this->straightRoute();
        $trip = Trip::factory()->inProgress()->create(['occupancy' => 10]);
        $unit = new SimulatedUnit($trip->vehicle, $trip, $path, 10.0, $stops[1] - 50, $stops);
        $simulator = app(RouteSimulator::class);

        $simulator->tick([$unit], 30);
        $this->assertGreaterThan(10, $trip->refresh()->occupancy);

        $unit->progressMeters = $stops[2] - 50;
        $simulator->tick([$unit], 60);
        $simulator->tick([$unit], 30);

        $this->assertSame(0, $trip->refresh()->occupancy);
    }

    public function test_after_waiting_at_the_last_stop_the_trip_ends_and_a_new_one_leaves_from_the_start(): void
    {
        [$path, $stops] = $this->straightRoute();
        $trip = Trip::factory()->inProgress()->create();
        $unit = new SimulatedUnit($trip->vehicle, $trip, $path, 10.0, $stops[2] - 50, $stops);
        $simulator = app(RouteSimulator::class);

        // Llega a la última parada, espera ahí y termina el recorrido.
        $simulator->tick([$unit], 60);
        $simulator->tick([$unit], 60);

        $this->assertSame(TripStatus::Completed, $trip->refresh()->status);
        $this->assertNotNull($unit->trip);
        $this->assertNotSame($trip->id, $unit->trip->id);
        $this->assertSame(TripStatus::InProgress, $unit->trip->status);
        $this->assertSame($trip->vehicle_id, $unit->trip->vehicle_id);
        $this->assertSame($trip->driver_id, $unit->trip->driver_id);
        $this->assertSame(0.0, $unit->progressMeters);
    }
}
