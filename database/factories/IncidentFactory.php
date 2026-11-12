<?php

namespace Database\Factories;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Models\Incident;
use App\Models\TransitRoute;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => IncidentType::Traffic,
            'severity' => IncidentSeverity::Medium,
            'route_id' => TransitRoute::factory(),
            'vehicle_id' => null,
            'reported_by' => User::factory()->admin(),
            'message' => 'Tráfico denso en Blvd. Venustiano Carranza.',
            'latitude' => null,
            'longitude' => null,
            'students_notified_at' => null,
            'resolved_at' => null,
        ];
    }

    /**
     * Emergencia reportada desde "Soporte en Ruta".
     */
    public function emergency(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IncidentType::Emergency,
            'severity' => IncidentSeverity::Critical,
            'message' => 'Una persona se siente mal dentro de la unidad.',
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => now(),
        ]);
    }
}
