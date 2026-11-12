<?php

namespace Tests\Feature\Transit;

use App\Models\ArrivalAlarm;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsLiveRoutes;
use Tests\TestCase;

class RouteDetailTest extends TestCase
{
    use BuildsLiveRoutes, RefreshDatabase;

    public function test_the_timeline_marks_passed_next_and_pending_stops(): void
    {
        ['route' => $route] = $this->liveRoute();

        $this->actingAs(User::factory()->student()->create())
            ->get(route('routes.show', $route))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('routes/show')
                ->where('route.id', $route->id)
                ->where('route.is_favorite', false)
                ->where('stops.0.state', 'passed')
                ->where('stops.1.state', 'next')
                ->where('stops.2.state', 'pending')
                ->where('stops.0.eta_minutes', null)
                ->has('vehicles', 1));
    }

    public function test_the_menu_opens_the_first_route_of_the_campus(): void
    {
        ['route' => $route] = $this->liveRoute();

        $this->actingAs(User::factory()->student()->create(['campus_id' => $route->campus_id]))
            ->get(route('routes.index'))
            ->assertRedirect(route('routes.show', $route));
    }

    public function test_a_student_marks_and_unmarks_a_favorite_route(): void
    {
        ['route' => $route] = $this->liveRoute();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->post(route('routes.favorite.store', $route))->assertRedirect();
        $this->assertTrue($student->favoriteRoutes()->whereKey($route->id)->exists());

        $this->delete(route('routes.favorite.destroy', $route))->assertRedirect();
        $this->assertFalse($student->favoriteRoutes()->whereKey($route->id)->exists());
    }

    public function test_an_arrival_alarm_needs_a_stop_of_the_same_route(): void
    {
        ['route' => $route, 'stops' => $stops] = $this->liveRoute();
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('arrival-alarms.store'), [
                'route_id' => $route->id,
                'stop_id' => Stop::factory()->create()->id,
                'minutes_before' => 5,
            ])
            ->assertSessionHasErrors('stop_id');

        $this->post(route('arrival-alarms.store'), [
            'route_id' => $route->id,
            'stop_id' => $stops[2]->id,
            'minutes_before' => 10,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('arrival_alarms', [
            'user_id' => $student->id,
            'stop_id' => $stops[2]->id,
            'minutes_before' => 10,
            'is_active' => true,
        ]);
    }

    public function test_only_the_owner_removes_an_alarm(): void
    {
        $alarm = ArrivalAlarm::factory()->create();

        $this->actingAs(User::factory()->student()->create())
            ->delete(route('arrival-alarms.destroy', $alarm))
            ->assertForbidden();

        $this->actingAs($alarm->user)
            ->delete(route('arrival-alarms.destroy', $alarm))
            ->assertRedirect();

        $this->assertModelMissing($alarm);
    }
}
