<?php

namespace Database\Factories;

use App\Models\ArrivalAlarm;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArrivalAlarm>
 */
class ArrivalAlarmFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'route_id' => TransitRoute::factory(),
            'stop_id' => Stop::factory(),
            'minutes_before' => 5,
            'is_active' => true,
            'last_trip_id' => null,
        ];
    }
}
