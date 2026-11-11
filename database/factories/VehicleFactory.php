<?php

namespace Database\Factories;

use App\Enums\VehicleAmenity;
use App\Enums\VehicleStatus;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'BUS-UTC-'.fake()->unique()->numerify('###'),
            'plate' => strtoupper(fake()->unique()->bothify('UTC-###-?')),
            'model' => fake()->randomElement(['Mercedes Sprinter', 'Volvo Access', 'Marcopolo Boxer']),
            'capacity' => fake()->randomElement([40, 45]),
            'status' => VehicleStatus::InService,
            'amenities' => [VehicleAmenity::AirConditioning->value],
        ];
    }

    /**
     * Unidad en base, sin ruta asignada.
     */
    public function standby(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::Standby,
        ]);
    }

    /**
     * Unidad en el taller.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VehicleStatus::Maintenance,
        ]);
    }
}
