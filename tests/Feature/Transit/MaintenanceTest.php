<?php

namespace Tests\Feature\Transit;

use App\Enums\VehicleStatus;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Vehicle $vehicle): array
    {
        return ['vehicle_id' => $vehicle->id, 'severity' => 'major', 'workshop' => 'Taller del campus', 'description' => 'Falla del sistema de frenado.'];
    }

    public function test_report_retires_vehicle_and_repair_returns_it_to_standby_with_history(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::InService]);
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post('/admin/fleet/maintenance', $this->payload($vehicle))->assertSessionHasNoErrors();
        $record = DB::table('vehicle_maintenance_records')->sole();
        $this->assertSame(VehicleStatus::Maintenance, $vehicle->refresh()->status);
        $this->assertSame($admin->id, $record->reported_by);
        $this->get('/admin/fleet/maintenance/'.$record->id)->assertOk();
        $this->patch('/admin/fleet/maintenance/'.$record->id.'/complete', ['resolution' => 'Se reemplazan pastillas y se verifican frenos.', 'cost' => '1500.50'])->assertSessionHasNoErrors();
        $this->assertSame(VehicleStatus::Standby, $vehicle->refresh()->status);
        $this->assertDatabaseHas('vehicle_maintenance_records', ['id' => $record->id, 'completed_by' => $admin->id, 'description' => $record->description, 'resolution' => 'Se reemplazan pastillas y se verifican frenos.']);
        $closed = DB::table('vehicle_maintenance_records')->sole();
        $this->assertNotNull($closed->completed_at);
        $this->patch('/admin/fleet/maintenance/'.$record->id.'/complete', ['resolution' => 'Intento de sobrescribir el historial.'])->assertSessionHasErrors('resolution');
        $this->assertSame($closed->resolution, DB::table('vehicle_maintenance_records')->sole()->resolution);
    }

    public function test_vehicle_on_active_trip_cannot_enter_workshop(): void
    {
        $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::InService]);
        Trip::factory()->inProgress()->create(['vehicle_id' => $vehicle->id]);
        $this->actingAs(User::factory()->admin()->create())->post('/admin/fleet/maintenance', $this->payload($vehicle))->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('vehicle_maintenance_records', 0);
        $this->assertSame(VehicleStatus::InService, $vehicle->refresh()->status);
    }

    public function test_open_reports_cannot_be_duplicated_but_new_repairs_keep_old_records(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->post('/admin/fleet/maintenance', $this->payload($vehicle))->assertSessionHasNoErrors();
        $this->post('/admin/fleet/maintenance', $this->payload($vehicle))->assertSessionHasErrors('vehicle_id');
        $record = DB::table('vehicle_maintenance_records')->sole();
        $this->patch('/admin/fleet/maintenance/'.$record->id.'/complete', ['resolution' => 'Se repara y verifica el sistema.'])->assertSessionHasNoErrors();
        $this->post('/admin/fleet/maintenance', $this->payload($vehicle))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('vehicle_maintenance_records', 2);
    }

    public function test_only_verified_administrators_manage_reports(): void
    {
        $vehicle = Vehicle::factory()->create();
        $this->get('/admin/fleet/maintenance')->assertRedirect(route('login'));
        $this->post('/admin/fleet/maintenance', $this->payload($vehicle))->assertRedirect(route('login'));
        foreach ([User::factory()->student()->create(), User::factory()->driver()->create()] as $user) {
            $this->actingAs($user)->get('/admin/fleet/maintenance')->assertForbidden();
            $this->get('/admin/fleet/maintenance/create')->assertForbidden();
            $this->post('/admin/fleet/maintenance', $this->payload($vehicle))->assertForbidden();
            $this->patch('/admin/fleet/maintenance/1/complete', ['resolution' => 'Intento sin permisos.'])->assertForbidden();
        }
        $this->actingAs(User::factory()->admin()->unverified()->create())->get('/admin/fleet/maintenance')->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('vehicle_maintenance_records', 0);
    }

    public function test_validation_and_filters_and_legacy_units(): void
    {
        $legacy = Vehicle::factory()->create(['status' => VehicleStatus::Maintenance]);
        $this->actingAs(User::factory()->admin()->create())->get('/admin/fleet/maintenance')
            ->assertInertia(fn (Assert $page) => $page->component('admin/fleet/maintenance/index')->has('without_report', 1));
        $this->post('/admin/fleet/maintenance', [...$this->payload($legacy), 'severity' => 'invalid', 'description' => 'short'])->assertSessionHasErrors(['severity', 'description']);
        $this->post('/admin/fleet/maintenance', $this->payload($legacy))->assertSessionHasNoErrors();
        $record = DB::table('vehicle_maintenance_records')->sole();
        $this->patch('/admin/fleet/maintenance/'.$record->id.'/complete', ['resolution' => 'Se realiza la reparación.', 'cost' => -1])->assertSessionHasErrors('cost');
        $this->assertSame(VehicleStatus::Maintenance, $legacy->refresh()->status);
        $this->get('/admin/fleet/maintenance?state=open&vehicle_id='.$legacy->id)
            ->assertInertia(fn (Assert $page) => $page->has('records.data', 1)->has('without_report', 0));
        $this->get('/admin/fleet/maintenance?state=closed')->assertInertia(fn (Assert $page) => $page->has('records.data', 0));
        $this->get('/admin/fleet/maintenance/999999')->assertNotFound();
    }
}
