<?php

namespace Tests\Feature\Transit;

use App\Models\Schedule;
use App\Models\TransitRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_departures_are_listed_for_the_chosen_day(): void
    {
        $route = TransitRoute::factory()->create();
        Schedule::factory()->for($route, 'route')->create(['weekday' => 2, 'departs_at' => '07:30:00']);
        Schedule::factory()->for($route, 'route')->create(['weekday' => 2, 'departs_at' => '06:15:00']);
        Schedule::factory()->for($route, 'route')->create(['weekday' => 3, 'departs_at' => '08:00:00']);

        $this->actingAs(User::factory()->student()->create(['campus_id' => $route->campus_id]))
            ->get(route('schedules.index', ['day' => 2]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('schedules/index')
                ->where('weekday', 2)
                ->has('routes', 1)
                ->where('routes.0.departures', ['06:15', '07:30']));
    }

    public function test_an_invalid_day_falls_back_to_today(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('schedules.index', ['day' => 9]))
            ->assertInertia(fn (Assert $page) => $page->where('weekday', now()->dayOfWeekIso));
    }
}
