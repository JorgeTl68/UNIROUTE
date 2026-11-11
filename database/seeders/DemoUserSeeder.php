<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios de prueba, uno por rol. Solo para desarrollo local y la demo.
 * Todos usan la contraseña DEMO_PASSWORD y ya tienen el correo verificado.
 */
class DemoUserSeeder extends Seeder
{
    public const string DEMO_PASSWORD = 'password';

    public const string ADMIN_EMAIL = 'admin@uniroute.test';

    public const string STUDENT_EMAIL = 'estudiante@uniroute.test';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $campusId = Campus::where('slug', CampusSeeder::CENTRAL)->value('id');

        self::upsert(self::ADMIN_EMAIL, 'Administración UniRoute', UserRole::Admin, ['campus_id' => $campusId]);

        self::upsert(self::STUDENT_EMAIL, 'Estudiante de Prueba', UserRole::Student, [
            'student_id' => '20260001',
            'campus_id' => $campusId,
        ]);
    }

    /**
     * Crea o actualiza un usuario de prueba con su rol y el correo verificado.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function upsert(string $email, string $name, UserRole $role, array $attributes = []): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => self::DEMO_PASSWORD, ...$attributes],
        );

        $user->forceFill(['role' => $role, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        return $user;
    }
}
