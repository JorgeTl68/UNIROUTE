<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\TransitRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
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
            'weekday' => fake()->numberBetween(1, 5),
            'departs_at' => sprintf('%02d:%02d:00', fake()->numberBetween(6, 21), fake()->randomElement([0, 15, 30, 45])),
        ];
    }
}
