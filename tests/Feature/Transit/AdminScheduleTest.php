<?php

namespace Tests\Feature\Transit;

use App\Models\Schedule;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminScheduleTest extends TestCase
{
    use RefreshDatabase;

    private TransitRoute $route;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route = TransitRoute::factory()->create([
            'number' => 4,
            'name' => 'Campus Central – Terminal',
            'frequency_minutes' => 15,
            'service_starts_at' => '06:00:00',
            'service_ends_at' => '22:00:00',
        ]);
    }

    private function indexUrl(?TransitRoute $route = null): string
    {
        return '/admin/routes/'.($route ?? $this->route)->id.'/schedules';
    }

    public function test_only_verified_administrators_can_manage_schedules(): void
    {
        $schedule = Schedule::factory()->for($this->route, 'route')->create();
        $this->get($this->indexUrl())->assertRedirect(route('login'));
        $this->post($this->indexUrl(), [])->assertRedirect(route('login'));

        foreach ([User::factory()->student()->create(), User::factory()->driver()->create()] as $user) {
            $this->actingAs($user)->get($this->indexUrl())->assertForbidden();
            $this->post($this->indexUrl(), [])->assertForbidden();
            $this->patch($this->indexUrl().'/'.$schedule->id, [])->assertForbidden();
            $this->post($this->indexUrl().'/generate', [])->assertForbidden();
        }

        $this->actingAs(User::factory()->admin()->unverified()->create())
            ->get($this->indexUrl())
            ->assertRedirect(route('verification.notice'));

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->indexUrl())
            ->assertOk();
    }

    public function test_schedule_list_is_ordered_by_weekday_and_time(): void
    {
        $tuesday = Schedule::factory()->for($this->route, 'route')->create([
            'weekday' => 2,
            'departs_at' => '06:00:00',
        ]);
        $lateMonday = Schedule::factory()->for($this->route, 'route')->create([
            'weekday' => 1,
            'departs_at' => '10:30:00',
        ]);
        $earlyMonday = Schedule::factory()->for($this->route, 'route')->create([
            'weekday' => 1,
            'departs_at' => '07:15:00',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get($this->indexUrl())
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/schedules/index')
                ->where('route.id', $this->route->id)
                ->where('route.name', $this->route->name)
                ->has('schedules', 3)
                ->where('schedules.0.id', $earlyMonday->id)
                ->where('schedules.0.departs_at', '07:15')
                ->where('schedules.1.id', $lateMonday->id)
                ->where('schedules.2.id', $tuesday->id));
    }

    public function test_administrator_adds_a_schedule(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post($this->indexUrl(), ['weekday' => 6, 'departs_at' => '08:45'])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->indexUrl())
            ->assertInertiaFlash('toast.message', 'Horario agregado correctamente.');

        $this->assertDatabaseHas('schedules', [
            'route_id' => $this->route->id,
            'weekday' => 6,
            'departs_at' => '08:45:00',
        ]);
    }

    public function test_administrator_edits_only_the_schedule_and_keeps_existing_trip_data(): void
    {
        $schedule = Schedule::factory()->for($this->route, 'route')->create([
            'weekday' => 1,
            'departs_at' => '06:00:00',
        ]);
        $trip = Trip::factory()->create([
            'route_id' => $this->route->id,
            'schedule_id' => $schedule->id,
            'scheduled_at' => '2026-11-16 06:00:00',
        ]);
        $originalVehicle = $trip->vehicle_id;
        $originalDriver = $trip->driver_id;

        $this->actingAs(User::factory()->admin()->create())
            ->patch($this->indexUrl().'/'.$schedule->id, [
                'weekday' => 3,
                'departs_at' => '09:30',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->indexUrl());

        $schedule->refresh();
        $trip->refresh();
        $this->assertSame(3, $schedule->weekday);
        $this->assertSame('09:30:00', $schedule->departs_at);
        $this->assertSame('2026-11-16 06:00:00', $trip->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame($originalVehicle, $trip->vehicle_id);
        $this->assertSame($originalDriver, $trip->driver_id);
    }

    public function test_schedule_from_another_route_cannot_be_edited(): void
    {
        $otherRoute = TransitRoute::factory()->create();
        $schedule = Schedule::factory()->for($otherRoute, 'route')->create([
            'weekday' => 1,
            'departs_at' => '06:00:00',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch($this->indexUrl().'/'.$schedule->id, [
                'weekday' => 2,
                'departs_at' => '07:00',
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'route_id' => $otherRoute->id,
            'weekday' => 1,
            'departs_at' => '06:00:00',
        ]);
    }

    public function test_duplicate_schedule_is_rejected_for_the_same_route(): void
    {
        $existing = Schedule::factory()->for($this->route, 'route')->create([
            'weekday' => 1,
            'departs_at' => '06:00:00',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post($this->indexUrl(), ['weekday' => 1, 'departs_at' => '06:00'])
            ->assertSessionHasErrors('departs_at');

        $other = Schedule::factory()->for($this->route, 'route')->create([
            'weekday' => 2,
            'departs_at' => '08:00:00',
        ]);
        $this->patch($this->indexUrl().'/'.$other->id, [
            'weekday' => 1,
            'departs_at' => '06:00',
        ])->assertSessionHasErrors('departs_at');

        $this->assertDatabaseCount('schedules', 2);
        $this->assertDatabaseHas('schedules', ['id' => $existing->id]);
    }

    public function test_invalid_schedule_and_generation_values_are_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post($this->indexUrl(), ['weekday' => 0, 'departs_at' => '25:00'])
            ->assertSessionHasErrors(['weekday', 'departs_at']);

        $this->post($this->indexUrl().'/generate', [
            'weekdays' => [],
            'starts_at' => '18:00',
            'ends_at' => '06:00',
            'interval_minutes' => 0,
        ])->assertSessionHasErrors(['weekdays', 'ends_at', 'interval_minutes']);

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_generation_includes_only_matching_end_time_and_is_idempotent(): void
    {
        Schedule::factory()->for($this->route, 'route')->create([
            'weekday' => 1,
            'departs_at' => '06:10:00',
        ]);
        $payload = [
            'weekdays' => [1, 3],
            'starts_at' => '06:00',
            'ends_at' => '06:25',
            'interval_minutes' => 10,
        ];

        $this->actingAs(User::factory()->admin()->create())
            ->post($this->indexUrl().'/generate', $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect($this->indexUrl())
            ->assertInertiaFlash(
                'toast.message',
                'Generación terminada. Se crearon 5 y ya existían 1.',
            );

        $this->assertDatabaseCount('schedules', 6);
        $this->assertDatabaseMissing('schedules', ['departs_at' => '06:25:00']);

        $this->post($this->indexUrl().'/generate', $payload)
            ->assertSessionHasNoErrors()
            ->assertInertiaFlash(
                'toast.message',
                'Generación terminada. Se crearon 0 y ya existían 6.',
            );
        $this->assertDatabaseCount('schedules', 6);
    }
}
