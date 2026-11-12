<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehiclePosition>
 */
class VehiclePositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'trip_id' => null,
            'latitude' => fake()->latitude(25.38, 25.46),
            'longitude' => fake()->longitude(-101.02, -100.94),
            'speed_kmh' => fake()->randomFloat(2, 0, 60),
            'heading' => fake()->numberBetween(0, 359),
            'recorded_at' => now(),
        ];
    }

    /**
     * Posición asociada a un viaje y a su unidad.
     */
    public function forTrip(Trip $trip): static
    {
        return $this->state(fn (array $attributes) => [
            'vehicle_id' => $trip->vehicle_id ?? Vehicle::factory(),
            'trip_id' => $trip->id,
        ]);
    }

    /**
     * Unidad detenida en una parada.
     */
    public function stopped(): static
    {
        return $this->state(fn (array $attributes) => [
            'speed_kmh' => 0,
            'heading' => null,
        ]);
    }
}
