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

    public function test_the_modal_data_is_only_sent_when_the_modal_asks_for_it(): void
    {
        $route = TransitRoute::factory()->create();

        $this->actingAs(User::factory()->student()->create(['campus_id' => $route->campus_id]))
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('emergency')
                ->reloadOnly('emergency', fn (Assert $reload) => $reload
                    ->has('emergency.routes', 1)
                    ->where('emergency.routes.0.id', $route->id)
                    ->has('emergency.categories', 5)
                    ->where('emergency.driver_route_id', null)));
    }

    public function test_the_modal_preselects_the_route_of_the_driver_trip(): void
    {
        $trip = Trip::factory()->inProgress()->create();

        $this->actingAs($trip->driver)
            ->get(route('driver.trip'))
            ->assertInertia(fn (Assert $page) => $page
                ->reloadOnly('emergency', fn (Assert $reload) => $reload
                    ->where('emergency.driver_route_id', $trip->route_id)));
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
            ->assertRedirect();

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
