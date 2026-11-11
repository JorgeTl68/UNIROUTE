<?php

namespace Tests\Feature\Transit;

use App\Enums\UserRole;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RolesAndPoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin'])
            ->get('/_pruebas/solo-admin', fn () => 'ok');

        Route::middleware(['web', 'auth', 'role:admin,driver'])
            ->get('/_pruebas/admin-o-conductor', fn () => 'ok');
    }

    public function test_people_who_register_are_students(): void
    {
        $this->skipUnlessFortifyHas(Features::registration());

        $this->post(route('register.store'), [
            'name' => 'Nueva Estudiante',
            'email' => 'nueva@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $user = User::where('email', 'nueva@example.com')->firstOrFail();

        $this->assertSame(UserRole::Student, $user->role);
    }

    public function test_role_middleware_only_lets_listed_roles_in(): void
    {
        $this->get('/_pruebas/solo-admin')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->student()->create())
            ->get('/_pruebas/solo-admin')->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/_pruebas/solo-admin')->assertOk();

        $this->actingAs(User::factory()->driver()->create())
            ->get('/_pruebas/admin-o-conductor')->assertOk();
    }

    public function test_only_admins_manage_routes_stops_and_vehicles(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $driver = User::factory()->driver()->create();
        $route = TransitRoute::factory()->create();

        $this->assertTrue($admin->can('create', TransitRoute::class));
        $this->assertTrue($admin->can('update', $route));
        $this->assertTrue($admin->can('delete', Stop::factory()->create()));
        $this->assertTrue($admin->can('viewAny', Vehicle::class));

        foreach ([$student, $driver] as $user) {
            $this->assertFalse($user->can('create', TransitRoute::class));
            $this->assertFalse($user->can('update', $route));
            $this->assertFalse($user->can('viewAny', Vehicle::class));
            $this->assertTrue($user->can('view', $route));
        }
    }

    public function test_inactive_routes_are_only_visible_to_admins(): void
    {
        $route = TransitRoute::factory()->inactive()->create();

        $this->assertFalse(User::factory()->student()->create()->can('view', $route));
        $this->assertTrue(User::factory()->admin()->create()->can('view', $route));
    }

    public function test_only_the_assigned_driver_or_an_admin_updates_a_trip(): void
    {
        $assigned = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $trip = Trip::factory()->create(['driver_id' => $assigned->id]);

        $this->assertTrue($assigned->can('update', $trip));
        $this->assertFalse($otherDriver->can('update', $trip));
        $this->assertFalse(User::factory()->student()->create()->can('update', $trip));
        $this->assertTrue(User::factory()->admin()->create()->can('update', $trip));
    }
}
