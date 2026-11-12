<?php

namespace Tests\Feature\Admin;

use App\Enums\IncidentSeverity;
use App\Models\ArrivalAlarm;
use App\Models\Incident;
use App\Models\TransitRoute;
use App\Models\User;
use App\Notifications\TransitNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IncidentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_manage_incidents(): void
    {
        $incident = Incident::factory()->create();

        foreach ([User::factory()->student()->create(), User::factory()->driver()->create()] as $user) {
            $this->actingAs($user)->get(route('admin.incidents.index'))->assertForbidden();
            $this->patch(route('admin.incidents.resolve', $incident))->assertForbidden();
        }
    }

    public function test_open_incidents_are_listed_by_severity(): void
    {
        $medium = Incident::factory()->create(['severity' => IncidentSeverity::Medium]);
        $critical = Incident::factory()->emergency()->create();
        Incident::factory()->resolved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.incidents.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/incidents/index')
                ->has('open', 2)
                ->where('open.0.id', $critical->id)
                ->where('open.1.id', $medium->id)
                ->has('resolved', 1)
                ->has('types', 3));
    }

    public function test_an_admin_registers_and_resolves_an_incident(): void
    {
        $admin = User::factory()->admin()->create();
        $route = TransitRoute::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.incidents.store'), [
                'type' => 'traffic',
                'severity' => 'high',
                'route_id' => $route->id,
                'message' => 'Choque en Blvd. Fundadores.',
            ])
            ->assertSessionHasNoErrors();

        $incident = Incident::sole();
        $this->assertSame($admin->id, $incident->reported_by);

        $this->patch(route('admin.incidents.resolve', $incident))->assertRedirect();
        $this->assertNotNull($incident->fresh()->resolved_at);
    }

    public function test_admins_cannot_register_an_emergency_from_the_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.incidents.store'), [
                'type' => 'emergency',
                'severity' => 'critical',
                'route_id' => TransitRoute::factory()->create()->id,
                'message' => 'Prueba de emergencia',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_notify_reaches_students_with_the_route_in_favorites_or_alarms(): void
    {
        Notification::fake();
        $incident = Incident::factory()->create();
        $favorite = User::factory()->student()->create();
        $favorite->favoriteRoutes()->attach($incident->route_id);
        $withAlarm = ArrivalAlarm::factory()->create(['route_id' => $incident->route_id])->user;
        $unrelated = User::factory()->student()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.incidents.notify', $incident))
            ->assertRedirect();

        Notification::assertSentTo([$favorite, $withAlarm], TransitNotification::class);
        Notification::assertNotSentTo($unrelated, TransitNotification::class);
        $this->assertNotNull($incident->fresh()->students_notified_at);
    }
}
