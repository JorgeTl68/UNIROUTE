<?php

namespace Tests\Feature\Transit;

use App\Models\Campus;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransitRouteIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_administrators_can_access_the_route_list(): void
    {
        $this->get('/admin/routes')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->student()->create())->get('/admin/routes')->assertForbidden();
        $this->actingAs(User::factory()->driver()->create())->get('/admin/routes')->assertForbidden();
        $this->actingAs(User::factory()->admin()->unverified()->create())->get('/admin/routes')->assertRedirect(route('verification.notice'));
    }

    public function test_verified_administrator_can_view_routes_and_stops(): void
    {
        $central = Campus::factory()->create(['name' => 'Campus Central']);
        $north = Campus::factory()->create(['name' => 'Campus Norte']);
        $northRoute = TransitRoute::factory()->for($north)->create(['number' => 1]);
        $inactive = TransitRoute::factory()->for($central)->inactive()->create(['number' => 3]);
        $route = TransitRoute::factory()->for($central)->create([
            'number' => 2,
            'name' => 'Campus Central – Santa Fe',
            'color' => '#91db2a',
            'frequency_minutes' => 12,
        ]);
        $shared = Stop::factory()->create(['name' => 'Acceso principal', 'has_shelter' => true]);
        $terminal = Stop::factory()->create(['name' => 'Terminal', 'has_shelter' => false]);

        $route->stops()->attach([
            $terminal->id => ['position' => 2, 'minutes_from_start' => 25],
            $shared->id => ['position' => 1, 'minutes_from_start' => 0],
        ]);
        $inactive->stops()->attach($shared->id, ['position' => 1, 'minutes_from_start' => 0]);
        Schedule::factory()->count(2)->for($route, 'route')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.routes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/routes/index')
                ->has('routes', 3)
                ->where('routes.0.id', $route->id)
                ->where('routes.0.number', 2)
                ->where('routes.0.name', $route->name)
                ->where('routes.0.color', '#91db2a')
                ->where('routes.0.campus', 'Campus Central')
                ->where('routes.0.is_active', true)
                ->where('routes.0.stops_count', 2)
                ->where('routes.0.schedules_count', 2)
                ->where('routes.0.duration_minutes', 25)
                ->where('routes.0.frequency_minutes', 12)
                ->where('routes.0.service_starts_at', '06:00:00')
                ->where('routes.0.service_ends_at', '22:00:00')
                ->where('routes.1.id', $inactive->id)
                ->where('routes.1.is_active', false)
                ->where('routes.1.duration_minutes', 0)
                ->where('routes.2.id', $northRoute->id)
                ->where('routes.2.duration_minutes', null)
                ->has('stops', 2)
                ->where('stops.0.name', 'Acceso principal')
                ->where('stops.0.has_shelter', true)
                ->has('stops.0.routes', 2)
                ->where('stops.0.routes.0.id', $route->id)
                ->where('stops.0.routes.1.id', $inactive->id)
                ->where('stops.1.name', 'Terminal')
                ->where('stops.1.has_shelter', false));
    }

    public function test_empty_routes_and_stops_can_be_displayed(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/routes')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/routes/index')
                ->has('routes', 0)
                ->has('stops', 0));
    }
}
