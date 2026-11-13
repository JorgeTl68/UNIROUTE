<?php

namespace Tests\Feature\Admin;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Enums\TripStatus;
use App\Events\IncidentReported;
use App\Models\Incident;
use App\Models\RouteDetour;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Tracking\Polyline;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdministrationCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.ssr.enabled' => false]);
        $this->withoutVite();
        Event::fake([IncidentReported::class]);
        Http::preventStrayRequests();
    }

    /** @return array{TransitRoute, array<int, Stop>, Incident} */
    private function detourRoute(): array
    {
        $route = TransitRoute::factory()->create(['encoded_polyline' => Polyline::encode([[25.4, -101], [25.41, -101], [25.42, -101]])]);
        $stops = [];
        foreach ([25.4, 25.41, 25.42] as $index => $latitude) {
            $stop = Stop::factory()->create(['latitude' => $latitude, 'longitude' => -101]);
            $route->stops()->attach($stop->id, ['position' => $index + 1, 'minutes_from_start' => $index * 5]);
            $stops[] = $stop;
        }
        $incident = Incident::factory()->create(['route_id' => $route->id, 'type' => IncidentType::Detour, 'resolved_at' => null]);

        return [$route, $stops, $incident];
    }

    private function fakeOsrm(): void
    {
        Http::fake(['*' => Http::response(['code' => 'Ok', 'routes' => [['geometry' => Polyline::encode([[25.4, -101], [25.42, -101]]), 'legs' => [['duration' => 480]]]]])]);
    }

    public function test_kpis_use_today_non_cancelled_trips_and_actual_departures(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 12:00:00', 'America/Monterrey'));
        $vehicle = Vehicle::factory()->create(['capacity' => 40]);
        foreach ([['11:01:00', 10], ['11:05:00', 20]] as [$start, $occupancy]) {
            Trip::factory()->create(['vehicle_id' => $vehicle->id, 'status' => TripStatus::InProgress, 'scheduled_at' => '2026-11-12 11:00:00', 'started_at' => '2026-11-12 '.$start, 'occupancy' => $occupancy]);
        }
        Trip::factory()->create(['scheduled_at' => '2026-11-12 14:00:00']);
        Trip::factory()->create(['status' => TripStatus::Cancelled, 'scheduled_at' => '2026-11-12 13:00:00']);
        Trip::factory()->create(['scheduled_at' => '2026-11-11 14:00:00']);
        $this->actingAs(User::factory()->admin()->create())->get('/admin/fleet')
            ->assertInertia(fn (Assert $page) => $page->where('stats.trips_today', 3)->where('stats.started_trips', 2)->where('stats.punctuality_percent', 50)->where('stats.average_occupancy_percent', 37.5));
    }

    public function test_empty_kpis_are_unknown_and_alert_selects_highest_severity_even_without_route(): void
    {
        Incident::factory()->create(['severity' => IncidentSeverity::High, 'resolved_at' => null]);
        $worst = Incident::factory()->create(['severity' => IncidentSeverity::Critical, 'route_id' => null, 'resolved_at' => null]);
        Incident::factory()->create(['severity' => IncidentSeverity::Critical, 'resolved_at' => now()]);
        $this->actingAs(User::factory()->admin()->create())->get('/admin/fleet')
            ->assertInertia(fn (Assert $page) => $page->where('stats.punctuality_percent', null)->where('stats.average_occupancy_percent', null)->where('alert.id', $worst->id)->where('alert.route', null));
    }

    public function test_administrator_can_create_map_stop_with_valid_coordinates(): void
    {
        $this->actingAs(User::factory()->admin()->create())->from('/admin/routes/create')->post('/admin/stops', ['name' => 'Nueva parada', 'latitude' => 25.4, 'longitude' => -101, 'has_shelter' => true])
            ->assertSessionHasNoErrors()->assertSessionHas('created_stop_id')->assertRedirect('/admin/routes/create');
        $this->assertDatabaseHas('stops', ['name' => 'Nueva parada', 'latitude' => 25.4, 'has_shelter' => true]);
        $this->post('/admin/stops', ['name' => 'Inválida', 'latitude' => 91, 'longitude' => -181])->assertSessionHasErrors(['latitude', 'longitude']);
    }

    public function test_detour_changes_shared_route_and_resolving_restores_original_geometry_and_stops(): void
    {
        [$route, $stops, $incident] = $this->detourRoute();
        $original = $route->encoded_polyline;
        $trip = Trip::factory()->inProgress()->create(['route_id' => $route->id]);
        $this->fakeOsrm();
        $this->actingAs(User::factory()->admin()->create())->post('/admin/incidents/'.$incident->id.'/detour', ['skipped_stop_ids' => [$stops[1]->id]])->assertSessionHasNoErrors();
        $this->assertSame([$stops[0]->id, $stops[2]->id], $route->fresh()->stops->modelKeys());
        $this->assertSame(8, $route->fresh()->stops->last()->pivot->minutes_from_start);
        $this->assertNotSame($original, $route->fresh()->encoded_polyline);
        $this->assertSame($trip->driver_id, $trip->fresh()->driver_id);
        $this->assertDatabaseCount('route_detours', 1);
        $this->patch('/admin/incidents/'.$incident->id.'/resolve')->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($original, $route->fresh()->encoded_polyline);
        $this->assertSame(array_map(fn ($stop) => $stop->id, $stops), $route->fresh()->stops->modelKeys());
        $this->assertSame(10, $route->fresh()->stops->last()->pivot->minutes_from_start);
        $this->assertNotNull(RouteDetour::sole()->restored_at);
        Event::assertDispatchedTimes(IncidentReported::class, 2);
    }

    public function test_failed_osrm_does_not_change_route_or_create_detour_history(): void
    {
        [$route, $stops, $incident] = $this->detourRoute();
        Http::fake(['*' => Http::response([], 503)]);
        $this->actingAs(User::factory()->admin()->create())->post('/admin/incidents/'.$incident->id.'/detour', ['skipped_stop_ids' => [$stops[1]->id]])->assertSessionHasErrors('skipped_stop_ids');
        $this->assertCount(3, $route->fresh()->stops);
        $this->assertDatabaseCount('route_detours', 0);
    }

    public function test_detour_rejects_foreign_stops_short_routes_closed_incidents_and_competing_detours(): void
    {
        [$route, $stops, $incident] = $this->detourRoute();
        $this->actingAs(User::factory()->admin()->create());
        $this->post('/admin/incidents/'.$incident->id.'/detour', ['skipped_stop_ids' => [Stop::factory()->create()->id]])->assertSessionHasErrors('skipped_stop_ids');
        $this->post('/admin/incidents/'.$incident->id.'/detour', ['skipped_stop_ids' => [$stops[0]->id, $stops[1]->id]])->assertSessionHasErrors('skipped_stop_ids');
        $this->fakeOsrm();
        $this->post('/admin/incidents/'.$incident->id.'/detour', ['skipped_stop_ids' => [$stops[1]->id]])->assertSessionHasNoErrors();
        $other = Incident::factory()->create(['route_id' => $route->id, 'type' => IncidentType::Detour, 'resolved_at' => null]);
        $this->post('/admin/incidents/'.$other->id.'/detour', ['skipped_stop_ids' => [$stops[0]->id]])->assertSessionHasErrors('skipped_stop_ids');
        $this->patch('/admin/incidents/'.$incident->id.'/resolve');
        $this->post('/admin/incidents/'.$incident->id.'/detour', ['skipped_stop_ids' => [$stops[1]->id]])->assertSessionHasErrors('skipped_stop_ids');
        $this->assertDatabaseCount('route_detours', 1);
    }

    public function test_new_actions_are_admin_only(): void
    {
        [, $stops, $incident] = $this->detourRoute();
        $this->post('/admin/stops', [])->assertRedirect(route('login'));
        $this->post('/admin/incidents/'.$incident->id.'/detour', [])->assertRedirect(route('login'));
        foreach ([User::factory()->student()->create(), User::factory()->driver()->create()] as $user) {
            $this->actingAs($user)->post('/admin/stops', [])->assertForbidden();
            $this->post('/admin/incidents/'.$incident->id.'/detour', ['skipped_stop_ids' => [$stops[1]->id]])->assertForbidden();
        }
        $this->assertDatabaseCount('route_detours', 0);
    }

    public function test_maintenance_and_assignment_history_dates_have_explicit_local_offset(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-12 12:00:00', 'America/Monterrey'));
        $admin = User::factory()->admin()->create();
        $vehicle = Vehicle::factory()->create();
        $trip = Trip::factory()->create();
        $this->actingAs($admin)->post('/admin/fleet/maintenance', ['vehicle_id' => $vehicle->id, 'severity' => 'major', 'workshop' => 'Taller campus', 'description' => 'Revisión del sistema de frenos.'])->assertSessionHasNoErrors();
        $this->get('/admin/fleet/maintenance')->assertInertia(fn (Assert $page) => $page->where('records.data.0.created_at', '2026-11-12T12:00:00-06:00'));
        DB::table('trip_assignments')->insert(['trip_id' => $trip->id, 'changed_by' => $admin->id, 'reason' => 'Cambio de turno', 'created_at' => now(), 'updated_at' => now()]);
        $this->get('/admin/fleet/assignments/'.$trip->id.'/edit')->assertInertia(fn (Assert $page) => $page->where('history.0.created_at', '2026-11-12T12:00:00-06:00'));
    }
}
