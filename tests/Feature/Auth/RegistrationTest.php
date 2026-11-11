<?php

namespace Tests\Feature\Auth;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_stores_student_details_and_keeps_student_role(): void
    {
        $campus = Campus::factory()->create();
        $this->post(route('register.store'), [
            'name' => 'Estudiante UniRoute',
            'email' => 'nuevo@uniroute.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'student_id' => '20261234',
            'campus_id' => $campus->id,
            'role' => 'admin',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@uniroute.test',
            'student_id' => '20261234',
            'campus_id' => $campus->id,
            'role' => 'student',
        ]);
    }

    public function test_registration_rejects_duplicate_student_id_and_unknown_campus(): void
    {
        User::factory()->create(['student_id' => '20261234']);
        $this->post(route('register.store'), [
            'name' => 'Estudiante UniRoute',
            'email' => 'nuevo@uniroute.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'student_id' => '20261234',
            'campus_id' => 999999,
        ])->assertSessionHasErrors(['student_id', 'campus_id']);
        $this->assertDatabaseMissing('users', ['email' => 'nuevo@uniroute.test']);
    }
}
