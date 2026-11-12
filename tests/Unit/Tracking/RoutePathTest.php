<?php

namespace Tests\Unit\Tracking;

use App\Models\Stop;
use App\Models\TransitRoute;
use App\Services\Tracking\Polyline;
use App\Services\Tracking\RoutePath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutePathTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefers_the_encoded_polyline(): void
    {
        $polyline = Polyline::encode([
            [25.40, -101.00],
            [25.41, -101.01],
            [25.42, -101.02],
        ]);

        $route = new TransitRoute(['encoded_polyline' => $polyline]);
        $path = RoutePath::fromRoute($route);

        $this->assertNotNull($path);
        $this->assertGreaterThan(0, $path->lengthMeters());

        $start = $path->positionAt(0);
        $end = $path->positionAt($path->lengthMeters());

        $this->assertEqualsWithDelta(25.40, $start['latitude'], 0.000001);
        $this->assertEqualsWithDelta(-101.00, $start['longitude'], 0.000001);
        $this->assertEqualsWithDelta(25.42, $end['latitude'], 0.000001);
        $this->assertEqualsWithDelta(-101.02, $end['longitude'], 0.000001);
    }

    public function test_falls_back_to_stops_when_the_polyline_is_missing(): void
    {
        $route = TransitRoute::factory()->create(['encoded_polyline' => null]);
        $first = Stop::factory()->create(['latitude' => 25.40, 'longitude' => -101.00]);
        $second = Stop::factory()->create(['latitude' => 25.42, 'longitude' => -101.02]);

        $route->stops()->attach([
            $first->id => ['position' => 1, 'minutes_from_start' => 0],
            $second->id => ['position' => 2, 'minutes_from_start' => 10],
        ]);
        $route->load('stops');

        $path = RoutePath::fromRoute($route);

        $this->assertNotNull($path);
        $start = $path->positionAt(0);
        $this->assertEqualsWithDelta(25.40, $start['latitude'], 0.000001);
        $this->assertEqualsWithDelta(-101.00, $start['longitude'], 0.000001);
    }

    public function test_returns_null_with_less_than_two_valid_points(): void
    {
        $this->assertNull(RoutePath::fromPoints([]));
        $this->assertNull(RoutePath::fromPoints([[25.40, -101.00]]));
        $this->assertNull(RoutePath::fromPoints([[25.40, -101.00], [25.40, -101.00]]));
    }

    public function test_interpolates_the_position_and_heading(): void
    {
        $path = RoutePath::fromPoints([
            [25.40, -101.00],
            [25.40, -100.99],
        ]);

        $this->assertNotNull($path);
        $middle = $path->positionAt($path->lengthMeters() / 2);

        $this->assertEqualsWithDelta(25.40, $middle['latitude'], 0.000001);
        $this->assertEqualsWithDelta(-100.995, $middle['longitude'], 0.00001);
        $this->assertSame(90, $middle['heading']);
    }

    public function test_finds_the_distance_along_the_path_to_the_nearest_point(): void
    {
        $path = RoutePath::fromPoints([
            [25.40, -101.00],
            [25.40, -100.99],
        ]);

        $this->assertNotNull($path);
        $length = $path->lengthMeters();

        $this->assertEqualsWithDelta(0.0, $path->distanceAlongFor(25.40, -101.00), 1.0);
        $this->assertEqualsWithDelta($length / 2, $path->distanceAlongFor(25.40, -100.995), $length * 0.01);
        $this->assertEqualsWithDelta($length, $path->distanceAlongFor(25.40, -100.99), 1.0);
        $this->assertEqualsWithDelta($length, $path->distanceAlongFor(25.40, -100.98), 1.0);
    }
}
