<?php

namespace Tests\Feature\Transit;

use App\Enums\UserRole;
use App\Models\Schedule;
use App\Models\TransitRoute;
use App\Models\User;
use App\Services\Tracking\RoutePath;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\FleetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_demo_users_with_their_roles(): void
    {
        $this->seed();

        $expected = [
            DemoUserSeeder::ADMIN_EMAIL => UserRole::Admin,
            DemoUserSeeder::STUDENT_EMAIL => UserRole::Student,
            FleetSeeder::DRIVER_EMAIL => UserRole::Driver,
        ];

        foreach ($expected as $email => $role) {
            $user = User::where('email', $email)->firstOrFail();

            $this->assertSame($role, $user->role);
            $this->assertNotNull($user->email_verified_at);
            $this->assertTrue(Hash::check(DemoUserSeeder::DEMO_PASSWORD, $user->password));
        }

        $this->assertNotNull(User::where('email', FleetSeeder::DRIVER_EMAIL)->firstOrFail()->driverProfile);
    }

    public function test_every_route_has_ordered_stops_and_weekday_schedules(): void
    {
        $this->seed();

        foreach (TransitRoute::with('stops')->get() as $route) {
            $positions = $route->stops->map(fn ($stop) => (int) $stop->getRelation('pivot')->getAttribute('position'))->all();

            $this->assertGreaterThanOrEqual(2, count($positions));
            $this->assertSame(range(1, count($positions)), $positions);
            $this->assertTrue($route->schedules()->exists());
        }

        $this->assertSame([1, 2, 3, 4, 5], Schedule::distinct()->orderBy('weekday')->pluck('weekday')->all());
    }

    public function test_seeder_loads_the_utc_routes_from_the_tecsa_sketches(): void
    {
        $this->seed();

        $routes = TransitRoute::with('stops')->orderBy('number')->get();

        $this->assertSame([1, 2, 7, 11, 12], $routes->pluck('number')->all());

        foreach ($routes as $route) {
            $this->assertNull($route->frequency_minutes);
            $this->assertSame('UTC', $route->stops->last()?->name);
            $this->assertSame('07:45:00', $route->service_ends_at);
            $this->assertSame([$route->service_starts_at], $route->schedules()->distinct()->pluck('departs_at')->all());
        }

        // Ruta 2 Central: 06:35, 06:45, 07:10, 07:25 y 07:45 en la UTC.
        $central = $routes->firstWhere('number', 2);
        $this->assertSame(
            [0, 10, 35, 50, 70],
            $central?->stops->map(fn ($stop) => (int) $stop->getRelation('pivot')->getAttribute('minutes_from_start'))->all(),
        );
    }

    public function test_every_stop_sits_on_its_route_trace_in_order(): void
    {
        $this->seed();

        foreach (TransitRoute::with('stops')->get() as $route) {
            $path = RoutePath::fromRoute($route);
            $this->assertNotNull($path, "La ruta {$route->number} no tiene trazado.");

            $previous = -1.0;
            foreach ($route->stops as $stop) {
                $along = $path->distanceAlongFor($stop->latitude, $stop->longitude);
                $closest = $path->positionAt($along);
                $offset = RoutePath::distanceMeters($stop->latitude, $stop->longitude, $closest['latitude'], $closest['longitude']);

                $this->assertLessThan(60, $offset, "{$stop->name} queda lejos del trazado de la ruta {$route->number}.");
                $this->assertGreaterThan($previous, $along, "{$stop->name} está fuera de orden en la ruta {$route->number}.");
                $previous = $along;
            }
        }
    }

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed();
        $counts = [User::count(), TransitRoute::count(), Schedule::count()];

        $this->seed();

        $this->assertSame($counts, [User::count(), TransitRoute::count(), Schedule::count()]);
    }
}
