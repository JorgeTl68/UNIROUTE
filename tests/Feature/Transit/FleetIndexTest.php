<?php

namespace Tests\Feature\Transit;

use App\Enums\TripStatus;
use App\Enums\VehicleStatus;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FleetIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_administrators_can_access_the_fleet(): void
    {
        $this->get('/admin/fleet')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->student()->create())->get('/admin/fleet')->assertForbidden();
        $this->actingAs(User::factory()->driver()->create())->get('/admin/fleet')->assertForbidden();
        $this->actingAs(User::factory()->admin()->unverified()->create())->get('/admin/fleet')->assertRedirect(route('verification.notice'));
        $this->actingAs(User::factory()->admin()->create())->get('/admin/fleet')->assertOk();
    }

    public function test_fleet_is_filtered_and_paginated_without_changing_global_totals(): void
    {
        Vehicle::factory()->count(6)->create(['status' => VehicleStatus::InService]);
        Vehicle::factory()->create(['status' => VehicleStatus::Maintenance]);
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/fleet?status=in_service')
            ->assertInertia(fn (Assert $page) => $page->component('admin/fleet/index')
                ->has('vehicles.data', 5)
                ->where('vehicles.total', 6)
                ->where('vehicles.data.0.status', 'in_service')
                ->where('stats.total', 7)
                ->where('stats.maintenance', 1)
                ->where('filters.status', 'in_service')
                ->where('vehicles.next_page_url', fn ($url) => str_contains($url, 'status=in_service')));
        $this->get('/admin/fleet?status=in_service&page=2')
            ->assertInertia(fn (Assert $page) => $page->has('vehicles.data', 1));
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->getJson('/admin/fleet?status=invalid')
            ->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_empty_fleet_can_be_displayed(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/fleet')
            ->assertInertia(fn (Assert $page) => $page->has('vehicles.data', 0)->where('stats.total', 0));
    }

    public function test_operational_data_uses_active_trips_and_search_preserves_totals(): void
    {
        $vehicle = Vehicle::factory()->create(['code' => 'BUS-SEARCH-01']);
        Vehicle::factory()->create(['code' => 'OTHER-02']);
        $driver = User::factory()->driver()->create();
        $trip = Trip::factory()->create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'status' => TripStatus::InProgress,
            'scheduled_at' => now(),
            'occupancy' => 12,
        ]);
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/fleet?search=BUS-SEARCH')
            ->assertInertia(fn (Assert $page) => $page
                ->has('vehicles.data', 1)
                ->where('vehicles.data.0.trips.0.id', $trip->id)
                ->where('vehicles.data.0.trips.0.driver.name', $driver->name)
                ->where('operations.hourly.0.occupancy', 12)
                ->where('stats.total', 2)
                ->where('filters.search', 'BUS-SEARCH'));
    }
}
