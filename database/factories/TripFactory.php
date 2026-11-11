<?php

namespace Database\Factories;

use App\Enums\TripStatus;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'route_id' => TransitRoute::factory(),
            'schedule_id' => null,
            'vehicle_id' => Vehicle::factory(),
            'driver_id' => User::factory()->driver(),
            'scheduled_at' => now()->addMinutes(fake()->numberBetween(5, 240)),
            'started_at' => null,
            'ended_at' => null,
            'status' => TripStatus::Scheduled,
            'occupancy' => 0,
        ];
    }

    /**
     * Viaje que ya salió y sigue en ruta.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->subMinutes(10),
            'started_at' => now()->subMinutes(8),
            'status' => TripStatus::InProgress,
            'occupancy' => fake()->numberBetween(5, 40),
        ]);
    }

    /**
     * Viaje terminado.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->subHours(2),
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
            'status' => TripStatus::Completed,
        ]);
    }
}
