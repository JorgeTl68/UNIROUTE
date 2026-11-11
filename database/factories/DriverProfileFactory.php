<?php

namespace Database\Factories;

use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverProfile>
 */
class DriverProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->driver(),
            'license_number' => strtoupper(fake()->bothify('COA-######')),
            'hired_at' => fake()->dateTimeBetween('-8 years', '-6 months')->format('Y-m-d'),
            'rating' => fake()->randomFloat(1, 3.5, 5),
        ];
    }
}
