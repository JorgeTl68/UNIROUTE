<?php

namespace Database\Factories;

use App\Models\Stop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stop>
 */
class StopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->streetName(),
            'latitude' => fake()->latitude(25.35, 25.60),
            'longitude' => fake()->longitude(-101.10, -100.90),
            'has_shelter' => fake()->boolean(),
            'notes' => null,
        ];
    }
}
