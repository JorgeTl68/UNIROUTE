<?php

namespace Tests\Feature\Transit;

use App\Models\Campus;
use App\Models\Incident;
use App\Models\TransitRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsLiveRoutes;
use Tests\TestCase;

class LiveDashboardTest extends TestCase
{
    use BuildsLiveRoutes, RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_students_and_admins_see_the_live_routes_of_their_campus(): void
    {
        ['route' => $route, 'trip' => $trip] = $this->liveRoute();
        Incident::factory()->create(['route_id' => $route->id, 'message' => 'Tráfico en el bulevar']);

        foreach ([User::factory()->student(), User::factory()->admin()] as $factory) {
            $user = $factory->create(['campus_id' => $route->campus_id]);

            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('dashboard')
                    ->where('campus.id', $route->campus_id)
                    ->has('routes', 1)
                    ->where('routes.0.id', $route->id)
                    ->has('routes.0.stops', 3)
                    ->has('routes.0.vehicles', 1)
                    ->where('routes.0.vehicles.0.trip_id', $trip->id)
                    ->where('routes.0.vehicles.0.next_stop.name', 'Media')
                    ->where('routes.0.vehicles.0.position.heading', 90)
                    ->has('incidents', 1)
                    ->where('incidents.0.message', 'Tráfico en el bulevar'));
        }
    }

    public function test_drivers_go_straight_to_driver_mode(): void
    {
        $this->actingAs(User::factory()->driver()->create())
            ->get(route('dashboard'))
            ->assertRedirect(route('driver.trip'));
    }

    public function test_the_campus_can_be_chosen_from_the_header(): void
    {
        $this->liveRoute();
        $other = Campus::factory()->create(['slug' => 'ramos-arizpe']);
        $otherRoute = TransitRoute::factory()->for($other)->create();

        $this->actingAs(User::factory()->student()->create())
            ->get(route('dashboard', ['campus' => 'ramos-arizpe']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('campus.slug', 'ramos-arizpe')
                ->where('currentCampus.slug', 'ramos-arizpe')
                ->has('routes', 1)
                ->where('routes.0.id', $otherRoute->id)
                ->has('routes.0.vehicles', 0));
    }

    public function test_shared_props_include_live_units_and_notifications(): void
    {
        $this->liveRoute();

        $this->actingAs(User::factory()->student()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('liveUnits', 1)
                ->where('notifications.unread', 0)
                ->has('campuses', 1));
    }
}
