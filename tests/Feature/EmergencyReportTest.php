<?php

namespace Tests\Feature;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Models\Incident;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\TransitNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmergencyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_role_can_open_the_form(): void
    {
        $route = TransitRoute::factory()->create();

        $this->actingAs(User::factory()->student()->create(['campus_id' => $route->campus_id]))
            ->get(route('emergency.create', ['route' => $route->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('emergency/create')
                ->where('selected_route_id', $route->id)
                ->has('routes', 1)
                ->has('categories', 5));
    }

    public function test_a_student_report_becomes_a_critical_incident_and_alerts_admins(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $route = TransitRoute::factory()->create(['number' => 3]);

        $this->actingAs($student)
            ->post(route('emergency.store'), [
                'category' => 'health',
                'route_id' => $route->id,
                'message' => 'Una compañera se desmayó en la unidad.',
                'latitude' => 25.42,
                'longitude' => -100.99,
            ])
            ->assertRedirect(route('dashboard'));

        $incident = Incident::sole();
        $this->assertSame(IncidentType::Emergency, $incident->type);
        $this->assertSame(IncidentSeverity::Critical, $incident->severity);
        $this->assertSame('Salud: Una compañera se desmayó en la unidad.', $incident->message);
        $this->assertSame($student->id, $incident->reported_by);
        $this->assertSame(25.42, $incident->latitude);

        Notification::assertSentTo($admin, TransitNotification::class, fn (TransitNotification $notification): bool => $notification->type === TransitNotification::INCIDENT && $notification->routeId === $route->id);
        Notification::assertNotSentTo($student, TransitNotification::class);
    }

    public function test_a_driver_report_takes_the_route_and_unit_of_the_trip_in_progress(): void
    {
        Notification::fake();
        $trip = Trip::factory()->inProgress()->create();

        $this->actingAs($trip->driver)
            ->post(route('emergency.store'), [
                'category' => 'breakdown',
                'message' => 'Se encendió la luz del motor y la unidad perdió fuerza.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('incidents', [
            'route_id' => $trip->route_id,
            'vehicle_id' => $trip->vehicle_id,
        ]);
    }

    public function test_the_report_needs_a_category_and_a_description(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->post(route('emergency.store'), ['category' => 'fire', 'message' => 'Ayuda'])
            ->assertSessionHasErrors(['category', 'message']);

        $this->assertDatabaseCount('incidents', 0);
    }
}
