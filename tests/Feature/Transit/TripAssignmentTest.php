<?php

namespace Tests\Feature\Transit;

use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Models\DriverProfile;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function driver(): User
    {
        $user = User::factory()->driver()->create();
        DriverProfile::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function payload(Trip $trip, User $driver, Vehicle $vehicle): array
    {
        return ['driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'route_id' => $trip->route_id, 'reason' => 'Relevo de turno'];
    }

    public function test_assignment_saves_history_without_changing_trip_state(): void
    {
        $trip = Trip::factory()->inProgress()->create(['occupancy' => 12]);
        $driver = $this->driver();
        $vehicle = Vehicle::factory()->create(['capacity' => 40, 'status' => VehicleStatus::Standby]);
        $oldDriver = $trip->driver_id;
        $oldVehicle = $trip->vehicle_id;
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch('/admin/fleet/assignments/'.$trip->id, $this->payload($trip, $driver, $vehicle))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.assignments.edit', $trip));
        $trip->refresh();
        $this->assertSame($driver->id, $trip->driver_id);
        $this->assertSame($vehicle->id, $trip->vehicle_id);
        $this->assertSame(TripStatus::InProgress, $trip->status);
        $this->assertSame(12, $trip->occupancy);
        $this->assertDatabaseHas('trip_assignments', ['trip_id' => $trip->id, 'previous_driver_id' => $oldDriver, 'previous_vehicle_id' => $oldVehicle, 'driver_id' => $driver->id, 'changed_by' => $admin->id]);
        $this->get(route('admin.assignments.edit', $trip))->assertOk();
    }

    public function test_conflicting_driver_or_vehicle_is_rejected(): void
    {
        $driver = $this->driver();
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Standby]);
        Trip::factory()->inProgress()->create(['driver_id' => $driver->id, 'vehicle_id' => $vehicle->id]);
        $trip = Trip::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->patch('/admin/fleet/assignments/'.$trip->id, $this->payload($trip, $driver, $vehicle))
            ->assertSessionHasErrors(['driver_id', 'vehicle_id']);
        $this->assertDatabaseCount('trip_assignments', 0);
    }

    public function test_same_departure_time_conflicts_but_later_trips_are_allowed(): void
    {
        $driver = $this->driver();
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Standby, 'capacity' => 40]);
        $other = Trip::factory()->create(['driver_id' => $driver->id, 'vehicle_id' => $vehicle->id]);
        $trip = Trip::factory()->create(['scheduled_at' => $other->scheduled_at]);
        $this->actingAs(User::factory()->admin()->create())->patch('/admin/fleet/assignments/'.$trip->id, $this->payload($trip, $driver, $vehicle))
            ->assertSessionHasErrors(['driver_id', 'vehicle_id']);
        $trip->update(['scheduled_at' => $other->scheduled_at->addHours(3)]);
        $this->patch('/admin/fleet/assignments/'.$trip->id, $this->payload($trip, $driver, $vehicle))->assertSessionHasNoErrors();
    }

    public function test_maintenance_small_capacity_non_driver_and_route_change_are_rejected(): void
    {
        $trip = Trip::factory()->inProgress()->create(['occupancy' => 20]);
        $driver = User::factory()->student()->create();
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Maintenance, 'capacity' => 10]);
        $route = TransitRoute::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->patch('/admin/fleet/assignments/'.$trip->id, [...$this->payload($trip, $driver, $vehicle), 'route_id' => $route->id])
            ->assertSessionHasErrors(['driver_id', 'vehicle_id', 'route_id']);
        $this->assertDatabaseCount('trip_assignments', 0);
    }

    public function test_completed_trips_and_non_admin_changes_are_rejected(): void
    {
        $trip = Trip::factory()->completed()->create();
        $driver = $this->driver();
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::Standby]);
        $this->actingAs(User::factory()->admin()->create())->patch('/admin/fleet/assignments/'.$trip->id, $this->payload($trip, $driver, $vehicle))->assertSessionHasErrors('reason');
        $this->actingAs($driver)->get('/admin/fleet/assignments')->assertForbidden();
        $this->get('/admin/fleet/assignments/'.$trip->id.'/edit')->assertForbidden();
        $this->patch('/admin/fleet/assignments/'.$trip->id, $this->payload($trip, $driver, $vehicle))->assertForbidden();
        $this->assertDatabaseCount('trip_assignments', 0);
    }
}
