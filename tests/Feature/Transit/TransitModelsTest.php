<?php

namespace Tests\Feature\Transit;

use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Http\Resources\TransitRouteResource;
use App\Http\Resources\TripResource;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransitModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_stops_come_ordered_by_position(): void
    {
        $route = TransitRoute::factory()->create();
        [$first, $second, $third] = Stop::factory()->count(3)->create();

        $route->stops()->attach([
            $third->id => ['position' => 3, 'minutes_from_start' => 20],
            $first->id => ['position' => 1, 'minutes_from_start' => 0],
            $second->id => ['position' => 2, 'minutes_from_start' => 8],
        ]);

        $this->assertSame(
            [$first->id, $second->id, $third->id],
            $route->stops()->pluck('stops.id')->all(),
        );
    }

    public function test_coordinates_are_numbers_and_statuses_are_enums(): void
    {
        $stop = Stop::factory()->create(['latitude' => 25.4262, 'longitude' => -100.9925]);
        $vehicle = Vehicle::factory()->maintenance()->create();
        $trip = Trip::factory()->inProgress()->create();

        $stop->refresh();

        $this->assertIsFloat($stop->latitude);
        $this->assertEqualsWithDelta(-100.9925, $stop->longitude, 0.0000001);
        $this->assertSame(VehicleStatus::Maintenance, $vehicle->fresh()?->status);
        $this->assertSame(TripStatus::InProgress, $trip->fresh()?->status);
    }

    public function test_scopes_filter_active_routes_and_upcoming_trips(): void
    {
        $active = TransitRoute::factory()->create();
        TransitRoute::factory()->inactive()->create();

        $later = Trip::factory()->create(['route_id' => $active->id, 'scheduled_at' => now()->addHour()]);
        $sooner = Trip::factory()->create(['route_id' => $active->id, 'scheduled_at' => now()->addMinutes(10)]);
        Trip::factory()->completed()->create(['route_id' => $active->id]);

        $this->assertSame([$active->id], TransitRoute::active()->pluck('id')->all());
        $this->assertSame([$sooner->id, $later->id], Trip::upcoming()->pluck('id')->all());
    }

    public function test_route_resource_matches_the_shared_typescript_shape(): void
    {
        $route = TransitRoute::factory()->create();
        $stop = Stop::factory()->create();
        $route->stops()->attach($stop->id, ['position' => 1, 'minutes_from_start' => 0]);

        $data = json_decode(TransitRouteResource::make($route->load(['campus', 'stops']))->toJson(), true);

        $this->assertSame($route->campus_id, $data['campus']['id']);
        $this->assertSame(
            ['route_id' => $route->id, 'stop_id' => $stop->id, 'position' => 1, 'minutes_from_start' => 0],
            $data['stops'][0]['pivot'],
        );
        $this->assertArrayNotHasKey('stops_count', $data);
    }

    public function test_driver_resource_never_exposes_the_email(): void
    {
        $driver = User::factory()->withDriverProfile()->create();
        $trip = Trip::factory()->create(['driver_id' => $driver->id]);

        $data = json_decode(TripResource::make($trip->load('driver.driverProfile'))->toJson(), true);

        $this->assertSame($driver->name, $data['driver']['name']);
        $this->assertArrayNotHasKey('email', $data['driver']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $data['driver']['driver_profile']['hired_at']);
        $this->assertSame('scheduled', $data['status']);
    }
}
