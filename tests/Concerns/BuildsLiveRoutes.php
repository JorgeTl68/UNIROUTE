<?php

namespace Tests\Concerns;

use App\Models\Campus;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\VehiclePosition;
use App\Services\Tracking\Polyline;

/**
 * Arma una ruta recta de tres paradas (Inicio, Media y Final, a ~1 km entre
 * sí) con una unidad en viaje que ya pasó "Inicio" y va hacia "Media".
 */
trait BuildsLiveRoutes
{
    /**
     * @return array{route: TransitRoute, stops: list<Stop>, trip: Trip}
     */
    protected function liveRoute(?Campus $campus = null): array
    {
        $route = TransitRoute::factory()->for($campus ?? Campus::factory()->create())->create([
            'encoded_polyline' => Polyline::encode([
                [25.40, -101.00],
                [25.40, -100.99],
                [25.40, -100.98],
            ]),
        ]);

        $stops = [];

        foreach (['Inicio' => -101.00, 'Media' => -100.99, 'Final' => -100.98] as $name => $longitude) {
            $stops[] = Stop::factory()->create(['name' => $name, 'latitude' => 25.40, 'longitude' => $longitude]);
        }

        foreach ($stops as $index => $stop) {
            $route->stops()->attach($stop->id, ['position' => $index + 1, 'minutes_from_start' => $index * 5]);
        }

        $trip = Trip::factory()->inProgress()->create([
            'route_id' => $route->id,
            'scheduled_at' => now()->subMinutes(3),
            'occupancy' => 20,
        ]);

        VehiclePosition::factory()->forTrip($trip)->create([
            'latitude' => 25.40,
            'longitude' => -100.995,
            'speed_kmh' => 30,
            'heading' => 90,
            'recorded_at' => now(),
        ]);

        return ['route' => $route, 'stops' => $stops, 'trip' => $trip];
    }
}
