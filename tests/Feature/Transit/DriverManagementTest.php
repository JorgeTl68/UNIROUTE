<?php

namespace Tests\Feature\Transit;

use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DriverManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return ['name' => 'Nueva Conductora', 'email' => 'nueva@uniroute.test', 'password' => 'SecurePass123!', 'password_confirmation' => 'SecurePass123!', 'license_number' => ' lic-0123 ', 'hired_at' => '2025-01-01'];
    }

    public function test_admin_registers_driver_and_profile_atomically(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->admin()->create())->post('/admin/fleet/drivers', [...$this->payload(), 'role' => 'admin'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.drivers.index'));
        $driver = User::where('email', 'nueva@uniroute.test')->firstOrFail();
        $this->assertSame(UserRole::Driver, $driver->role);
        $this->assertTrue(Hash::check('SecurePass123!', $driver->password));
        $this->assertNull($driver->email_verified_at);
        $this->assertSame('LIC-0123', $driver->driverProfile->license_number);
        Notification::assertSentTo($driver, VerifyEmail::class);
    }

    public function test_registration_and_listing_are_admin_only(): void
    {
        $this->get('/admin/fleet/drivers')->assertRedirect(route('login'));
        foreach ([User::factory()->student()->create(), User::factory()->driver()->create()] as $user) {
            $this->actingAs($user)->get('/admin/fleet/drivers')->assertForbidden();
            $this->get('/admin/fleet/drivers/create')->assertForbidden();
            $this->post('/admin/fleet/drivers', $this->payload())->assertForbidden();
        }
        $this->assertDatabaseCount('driver_profiles', 0);
    }

    public function test_invalid_or_duplicate_details_are_rejected_without_partial_accounts(): void
    {
        $profile = DriverProfile::factory()->create(['license_number' => 'LIC-0123']);
        $this->actingAs(User::factory()->admin()->create())->post('/admin/fleet/drivers', $this->payload())->assertSessionHasErrors('license_number');
        $this->assertDatabaseMissing('users', ['email' => 'nueva@uniroute.test']);
        $this->post('/admin/fleet/drivers', [...$this->payload(), 'email' => $profile->user->email, 'hired_at' => now()->addDay()->toDateString(), 'password_confirmation' => 'different'])
            ->assertSessionHasErrors(['email', 'hired_at', 'password']);
    }

    public function test_driver_list_searches_licenses_and_omits_other_roles(): void
    {
        $driver = User::factory()->driver()->create();
        DriverProfile::factory()->create(['user_id' => $driver->id, 'license_number' => 'MATCH-123']);
        User::factory()->student()->create();
        $this->actingAs(User::factory()->admin()->create())->get('/admin/fleet/drivers?search=MATCH')
            ->assertInertia(fn (Assert $page) => $page->component('admin/fleet/drivers/index')->has('drivers.data', 1)->where('drivers.data.0.id', $driver->id)->missing('drivers.data.0.password'));
        Campus::factory()->create();
        $this->get('/admin/fleet/drivers/create')->assertOk();
    }
}
