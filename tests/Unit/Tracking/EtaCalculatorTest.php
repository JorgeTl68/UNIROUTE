<?php

namespace Tests\Unit\Tracking;

use App\Enums\TripStatus;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use App\Services\Tracking\EtaCalculator;
use App\Services\Tracking\Polyline;
use App\Services\Tracking\RoutePath;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class EtaCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_eta_for_each_stop_along_the_polyline(): void
    {
        $route = $this->routeWithTwoStops();
        $path = RoutePath::fromRoute($route);

        $this->assertNotNull($path);

        $now = CarbonImmutable::parse('2026-11-11 08:00:00');
        $speed = 36.0;
        $metersPerMinute = $speed * 1000 / 60;

        $etas = app(EtaCalculator::class)->forRoute($route, 25.40, -101.00, $speed, $now);

        $expectedFirst = (int) ceil(($path->lengthMeters() / 2) / $metersPerMinute);
        $expectedSecond = (int) ceil($path->lengthMeters() / $metersPerMinute);

        $this->assertCount(2, $etas);
        $this->assertSame('Media', $etas[0]['stop_name']);
        $this->assertSame($expectedFirst, $etas[0]['eta_minutes']);
        $this->assertSame($expectedSecond, $etas[1]['eta_minutes']);
        $this->assertSame(0, $etas[0]['delay_minutes']);
        $this->assertNull($etas[0]['trip_id']);
        $this->assertTrue($etas[0]['arrives_at']->equalTo($now->addMinutes($expectedFirst)));
    }

    public function test_skips_stops_the_unit_already_passed(): void
    {
        $route = $this->routeWithTwoStops();

        // La unidad va a unos 700 m del inicio: ya pasó "Media" (a ~500 m).
        $etas = app(EtaCalculator::class)->forRoute($route, 25.40, -100.993, 36.0);

        $this->assertCount(1, $etas);
        $this->assertSame('Final', $etas[0]['stop_name']);
        $this->assertGreaterThan(0, $etas[0]['eta_minutes']);
    }

    public function test_keeps_a_stop_the_unit_is_standing_at(): void
    {
        $route = $this->routeWithTwoStops();

        // Unos 10 m después de "Media": sigue contando como llegada inmediata.
        $etas = app(EtaCalculator::class)->forRoute($route, 25.40, -100.9949, 36.0);

        $this->assertCount(2, $etas);
        $this->assertSame('Media', $etas[0]['stop_name']);
        $this->assertSame(0, $etas[0]['eta_minutes']);
    }

    public function test_uses_the_trip_schedule_to_compute_the_delay(): void
    {
        $route = $this->routeWithTwoStops();
        $now = CarbonImmutable::parse('2026-11-11 08:00:00');
        $trip = Trip::factory()->create([
            'route_id' => $route->id,
            'scheduled_at' => $now->subMinutes(1),
            'status' => TripStatus::InProgress,
        ]);

        $etas = app(EtaCalculator::class)->forRoute($route, 25.40, -101.00, 36.0, $now, $trip);

        $scheduledArrival = $now->subMinutes(1)->addMinutes(5);
        $expectedDelay = (int) round(
            ($etas[0]['arrives_at']->getTimestamp() - $scheduledArrival->getTimestamp()) / 60,
        );

        $this->assertSame($trip->id, $etas[0]['trip_id']);
        $this->assertSame($expectedDelay, $etas[0]['delay_minutes']);
    }

    public function test_rejects_a_non_positive_speed(): void
    {
        $route = $this->routeWithTwoStops();

        $this->expectException(InvalidArgumentException::class);

        app(EtaCalculator::class)->forRoute($route, 25.40, -101.00, 0.0);
    }

    public function test_returns_no_etas_when_the_route_has_no_path(): void
    {
        $route = TransitRoute::factory()->create(['encoded_polyline' => null]);

        $this->assertSame([], app(EtaCalculator::class)->forRoute($route, 25.40, -101.00, 30.0));
    }

    public function test_average_speed_uses_recent_positions(): void
    {
        $vehicle = Vehicle::factory()->create();
        $now = CarbonImmutable::parse('2026-11-11 08:00:00');

        VehiclePosition::factory()->create([
            'vehicle_id' => $vehicle->id,
            'speed_kmh' => 30,
            'recorded_at' => $now->subMinute(),
        ]);
        VehiclePosition::factory()->create([
            'vehicle_id' => $vehicle->id,
            'speed_kmh' => 40,
            'recorded_at' => $now->subMinutes(2),
        ]);
        VehiclePosition::factory()->create([
            'vehicle_id' => $vehicle->id,
            'speed_kmh' => 99,
            'recorded_at' => $now->subHour(),
        ]);

        $this->assertSame(35.0, app(EtaCalculator::class)->averageSpeedKmh($vehicle, now: $now));
    }

    public function test_for_trip_uses_the_latest_position_and_recent_speed(): void
    {
        $route = $this->routeWithTwoStops();
        $trip = Trip::factory()->inProgress()->create(['route_id' => $route->id]);
        $now = CarbonImmutable::parse('2026-11-11 08:00:00');

        VehiclePosition::factory()->forTrip($trip)->create([
            'latitude' => 25.40,
            'longitude' => -101.00,
            'speed_kmh' => 30,
            'recorded_at' => $now->subMinutes(2),
        ]);
        VehiclePosition::factory()->forTrip($trip)->create([
            'latitude' => 25.40,
            'longitude' => -101.00,
            'speed_kmh' => 40,
            'recorded_at' => $now->subMinute(),
        ]);

        $etas = app(EtaCalculator::class)->forTrip($trip, $now);

        $this->assertCount(2, $etas);
        $this->assertSame($trip->id, $etas[0]['trip_id']);
        $this->assertGreaterThanOrEqual(0, $etas[0]['eta_minutes']);
    }

    public function test_for_trip_returns_no_etas_without_positions(): void
    {
        $route = $this->routeWithTwoStops();
        $trip = Trip::factory()->inProgress()->create(['route_id' => $route->id]);

        $this->assertSame([], app(EtaCalculator::class)->forTrip($trip));
    }

    private function routeWithTwoStops(): TransitRoute
    {
        $route = TransitRoute::factory()->create([
            'encoded_polyline' => Polyline::encode([
                [25.40, -101.00],
                [25.40, -100.99],
            ]),
        ]);

        $middle = Stop::factory()->create([
            'name' => 'Media',
            'latitude' => 25.40,
            'longitude' => -100.995,
        ]);
        $end = Stop::factory()->create([
            'name' => 'Final',
            'latitude' => 25.40,
            'longitude' => -100.99,
        ]);

        $route->stops()->attach([
            $middle->id => ['position' => 1, 'minutes_from_start' => 5],
            $end->id => ['position' => 2, 'minutes_from_start' => 10],
        ]);

        return $route->load('stops');
    }
}
