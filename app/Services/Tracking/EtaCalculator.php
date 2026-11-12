<?php

namespace App\Services\Tracking;

use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Calcula la llegada estimada (ETA) de una unidad a cada parada de su ruta.
 *
 * La distancia se mide sobre la polilínea y se divide entre la velocidad
 * promedio. El retraso se compara contra la hora programada del viaje.
 */
class EtaCalculator
{
    /**
     * @return list<array{stop_id: int, stop_name: string, trip_id: int|null, distance_meters: float, eta_minutes: int, arrives_at: CarbonImmutable, delay_minutes: int}>
     */
    public function forRoute(
        TransitRoute $route,
        float $latitude,
        float $longitude,
        float $averageSpeedKmh,
        ?CarbonInterface $at = null,
        ?Trip $trip = null,
    ): array {
        if ($averageSpeedKmh <= 0.0) {
            throw new InvalidArgumentException('La velocidad promedio debe ser mayor a 0.');
        }

        $path = RoutePath::fromRoute($route);

        if ($path === null) {
            return [];
        }

        if (! $route->relationLoaded('stops')) {
            $route->load('stops');
        }

        $now = $at === null ? CarbonImmutable::now() : CarbonImmutable::instance($at);
        $currentAlong = $path->distanceAlongFor($latitude, $longitude);
        $metersPerMinute = $averageSpeedKmh * 1000 / 60;
        $etas = [];

        foreach ($route->stops as $stop) {
            $stopAlong = $path->distanceAlongFor($stop->latitude, $stop->longitude);
            $remaining = max(0.0, $stopAlong - $currentAlong);
            $etaMinutes = (int) ceil($remaining / $metersPerMinute);
            $arrivesAt = $now->addMinutes($etaMinutes);

            $delayMinutes = 0;

            if ($trip !== null) {
                $minutesFromStart = (int) ($stop->pivot->minutes_from_start ?? 0);
                $scheduledArrival = CarbonImmutable::instance($trip->scheduled_at)
                    ->addMinutes($minutesFromStart);
                $delayMinutes = (int) round(
                    ($arrivesAt->getTimestamp() - $scheduledArrival->getTimestamp()) / 60,
                );
            }

            $etas[] = [
                'stop_id' => $stop->id,
                'stop_name' => $stop->name,
                'trip_id' => $trip?->id,
                'distance_meters' => round($remaining, 1),
                'eta_minutes' => $etaMinutes,
                'arrives_at' => $arrivesAt,
                'delay_minutes' => $delayMinutes,
            ];
        }

        return $etas;
    }

    /**
     * ETA de un viaje en curso usando su última posición y velocidad reciente.
     *
     * @return list<array{stop_id: int, stop_name: string, trip_id: int|null, distance_meters: float, eta_minutes: int, arrives_at: CarbonImmutable, delay_minutes: int}>
     */
    public function forTrip(
        Trip $trip,
        ?CarbonInterface $now = null,
        ?float $averageSpeedKmh = null,
    ): array {
        $route = $trip->route;

        if ($route === null) {
            return [];
        }

        $position = VehiclePosition::query()
            ->where('trip_id', $trip->id)
            ->latest('recorded_at')
            ->first();

        if ($position === null && $trip->vehicle_id !== null) {
            $position = VehiclePosition::query()
                ->where('vehicle_id', $trip->vehicle_id)
                ->latest('recorded_at')
                ->first();
        }

        if ($position === null) {
            return [];
        }

        $at = $now ?? $position->recorded_at;
        $speed = $averageSpeedKmh;
        $vehicle = $trip->vehicle;

        if ($speed === null && $vehicle !== null) {
            $speed = $this->averageSpeedKmh($vehicle, $trip, now: $at)
                ?? $this->averageSpeedKmh($vehicle, now: $at)
                ?? $this->fallbackAverageSpeedKmh($route);
        }

        $speed ??= $this->fallbackAverageSpeedKmh($route);

        if ($speed === null || $speed <= 0.0) {
            return [];
        }

        return $this->forRoute(
            $route,
            $position->latitude,
            $position->longitude,
            $speed,
            $at,
            $trip,
        );
    }

    /**
     * Velocidad promedio (km/h) de las posiciones recientes de una unidad.
     */
    public function averageSpeedKmh(
        Vehicle $vehicle,
        ?Trip $trip = null,
        int $windowMinutes = 15,
        ?CarbonInterface $now = null,
    ): ?float {
        $until = $now === null ? CarbonImmutable::now() : CarbonImmutable::instance($now);
        $since = $until->subMinutes(max(1, $windowMinutes));

        $query = VehiclePosition::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereBetween('recorded_at', [$since, $until])
            ->whereNotNull('speed_kmh')
            ->where('speed_kmh', '>', 0);

        if ($trip !== null) {
            $query->where('trip_id', $trip->id);
        }

        $average = $query->avg('speed_kmh');

        return $average === null ? null : round((float) $average, 2);
    }

    /**
     * Velocidad estimada a partir de la duración programada entre paradas.
     */
    public function fallbackAverageSpeedKmh(TransitRoute $route): ?float
    {
        $path = RoutePath::fromRoute($route);

        if ($path === null) {
            return null;
        }

        if (! $route->relationLoaded('stops')) {
            $route->load('stops');
        }

        $totalMinutes = (int) $route->stops->max(
            fn (Stop $stop): int => (int) ($stop->pivot->minutes_from_start ?? 0),
        );

        if ($totalMinutes <= 0) {
            return null;
        }

        return round($path->lengthMeters() / ($totalMinutes * 60) * 3.6, 2);
    }
}
