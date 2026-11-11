<?php

namespace Tests\Feature\Transit;

use App\Enums\UserRole;
use App\Models\Schedule;
use App\Models\TransitRoute;
use App\Models\User;
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

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed();
        $counts = [User::count(), TransitRoute::count(), Schedule::count()];

        $this->seed();

        $this->assertSame($counts, [User::count(), TransitRoute::count(), Schedule::count()]);
    }
}
