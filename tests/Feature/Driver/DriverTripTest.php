<?php

namespace Tests\Feature\Driver;

use App\Enums\TripStatus;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DriverTripTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_drivers_can_open_the_driver_screen(): void
    {
        $this->actingAs(User::factory()->student()->create())->get(route('driver.trip'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('driver.trip'))->assertForbidden();
        $this->actingAs(User::factory()->driver()->create())->get(route('driver.trip'))->assertOk();
    }

    public function test_the_driver_sees_the_next_trip_of_today(): void
    {
        $driver = User::factory()->driver()->create();
        Trip::factory()->create(['driver_id' => $driver->id, 'scheduled_at' => now()->addHours(2)]);
        $next = Trip::factory()->create(['driver_id' => $driver->id, 'scheduled_at' => now()->addMinutes(5)]);
        Trip::factory()->create(['driver_id' => $driver->id, 'scheduled_at' => now()->addDay()]);

        $this->actingAs($driver)
            ->get(route('driver.trip'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('driver/trip')
                ->where('trip.id', $next->id)
                ->where('trip.status', 'scheduled')
                ->has('trips', 2));
    }

    public function test_the_driver_starts_and_finishes_the_trip(): void
    {
        $trip = Trip::factory()->create(['scheduled_at' => now()]);

        $this->actingAs($trip->driver)->post(route('driver.trips.start', $trip))->assertRedirect();
        $this->assertSame(TripStatus::InProgress, $trip->fresh()->status);

        $this->post(route('driver.trips.finish', $trip))->assertRedirect();
        $this->assertSame(TripStatus::Completed, $trip->fresh()->status);
        $this->assertNotNull($trip->fresh()->ended_at);
    }

    public function test_another_driver_cannot_start_the_trip(): void
    {
        $trip = Trip::factory()->create();

        $this->actingAs(User::factory()->driver()->create())
            ->post(route('driver.trips.start', $trip))
            ->assertForbidden();
    }

    public function test_occupancy_stays_between_zero_and_capacity(): void
    {
        $trip = Trip::factory()->inProgress()->create();
        $capacity = $trip->vehicle->capacity;

        $this->actingAs($trip->driver)
            ->patch(route('driver.trips.occupancy', $trip), ['occupancy' => $capacity + 1])
            ->assertSessionHasErrors('occupancy');

        $this->patch(route('driver.trips.occupancy', $trip), ['occupancy' => 12])
            ->assertSessionHasNoErrors();

        $this->assertSame(12, $trip->fresh()->occupancy);
    }

    public function test_the_browser_position_is_recorded_for_the_trip(): void
    {
        $trip = Trip::factory()->inProgress()->create();

        $this->actingAs($trip->driver)
            ->postJson(route('driver.trips.positions.store', $trip), [
                'latitude' => 25.4232,
                'longitude' => -101.0005,
                'speed_kmh' => 32.5,
                'heading' => 181.6,
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('vehicle_positions', [
            'trip_id' => $trip->id,
            'vehicle_id' => $trip->vehicle_id,
            'heading' => 182,
        ]);
    }

    public function test_positions_are_rejected_when_the_trip_is_not_in_progress(): void
    {
        $trip = Trip::factory()->create();

        $this->actingAs($trip->driver)
            ->postJson(route('driver.trips.positions.store', $trip), [
                'latitude' => 25.4232,
                'longitude' => -101.0005,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('vehicle_positions', 0);
    }
}
