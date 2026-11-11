<?php

namespace Database\Factories;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Campus>
 */
class CampusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Campus '.fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'latitude' => fake()->latitude(25.35, 25.60),
            'longitude' => fake()->longitude(-101.10, -100.90),
        ];
    }
}
