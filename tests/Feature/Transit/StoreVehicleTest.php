<?php

namespace Tests\Feature\Transit;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StoreVehicleTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'code' => ' bus-utc-21 ', 'plate' => ' abc-123-a ',
            'model' => 'Mercedes Sprinter', 'capacity' => 40,
            'status' => 'standby', 'amenities' => ['wifi', 'accessible_ramp'],
        ], $overrides);
    }

    public function test_admin_opens_the_form_from_the_fleet_console_and_registers_a_vehicle(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/fleet')
            ->assertInertia(fn (Assert $page) => $page->component('admin/fleet/index')->has('statuses', 3)->has('amenities', 3));
        $this->post('/admin/fleet', $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('admin.fleet.index'));
        $vehicle = Vehicle::sole();
        $this->assertSame('BUS-UTC-21', $vehicle->code);
        $this->assertSame('ABC-123-A', $vehicle->plate);
        $this->assertSame(40, $vehicle->capacity);
        $this->assertSame(['wifi', 'accessible_ramp'], $vehicle->amenities);
    }

    public function test_access_is_denied_to_guests_other_roles_and_unverified_users(): void
    {
        $this->post('/admin/fleet', $this->payload())->assertRedirect(route('login'));
        foreach ([User::factory()->student()->create(), User::factory()->driver()->create()] as $user) {
            $this->actingAs($user)->post('/admin/fleet', $this->payload())->assertForbidden();
        }
        $this->actingAs(User::factory()->admin()->unverified()->create())
            ->post('/admin/fleet', $this->payload())->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_duplicate_identifiers_are_rejected_after_normalization(): void
    {
        Vehicle::factory()->create(['code' => 'BUS-UTC-21', 'plate' => 'ABC-123-A']);
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/fleet', $this->payload())->assertSessionHasErrors(['code', 'plate']);
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_invalid_values_do_not_create_vehicles(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        foreach ([0, -1, 1.5, 65536] as $capacity) {
            $this->post('/admin/fleet', $this->payload(['capacity' => $capacity]))->assertSessionHasErrors('capacity');
        }
        $this->post('/admin/fleet', $this->payload(['status' => 'invalid', 'amenities' => ['invalid']]))
            ->assertSessionHasErrors(['status', 'amenities.0']);
        $this->post('/admin/fleet', $this->payload(['amenities' => ['wifi', 'wifi']]))->assertSessionHasErrors('amenities.0');
        $this->post('/admin/fleet', [])->assertSessionHasErrors(['code', 'plate', 'model', 'capacity', 'status']);
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_amenities_are_optional(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/admin/fleet', $this->payload(['amenities' => []]))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.fleet.index'));
        $this->assertSame([], Vehicle::sole()->amenities);
    }
}
