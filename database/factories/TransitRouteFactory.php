<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\TransitRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransitRoute>
 */
class TransitRouteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campus_id' => Campus::factory(),
            'number' => fake()->unique()->numberBetween(1, 999),
            'name' => fake()->streetName().' – '.fake()->streetName(),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'encoded_polyline' => null,
            'frequency_minutes' => fake()->randomElement([10, 12, 15, 20, 30]),
            'service_starts_at' => '06:00:00',
            'service_ends_at' => '22:00:00',
            'is_active' => true,
        ];
    }

    /**
     * Ruta dada de baja.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
