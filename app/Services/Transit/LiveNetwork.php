<?php

namespace App\Services\Transit;

use App\Models\Campus;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Services\Tracking\EtaCalculator;
use App\Services\Tracking\RoutePath;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Estado en vivo de la red: rutas con sus unidades en viaje, la siguiente
 * parada de cada una y las próximas salidas.
 *
 * Lo usan el Dashboard en Vivo y el Detalle de ruta. Los tipos de TypeScript
 * están en transit.ts (LiveRoute, LiveVehicle, RouteStopStatus).
 */
class LiveNetwork
{
    /**
     * Distancia a la que se considera que la unidad ya está en la parada.
     */
    private const float AT_STOP_METERS = 60.0;

    public function __construct(private readonly EtaCalculator $etaCalculator) {}

    /**
     * Rutas activas del campus para el Dashboard en Vivo.
     *
     * @return list<array<string, mixed>>
     */
    public function routesFor(Campus $campus, ?User $user): array
    {
        $routes = TransitRoute::query()
            ->active()
            ->forCampus($campus)
            ->with('stops')
            ->orderBy('number')
            ->get();

        $live = $this->liveTrips($routes->modelKeys())
            ->groupBy(fn (array $live): int => $live['trip']->route_id);
        $favorites = $user?->favoriteRoutes()->pluck('routes.id') ?? collect();
        $nextDepartures = $this->nextDepartures($routes->modelKeys());

        return $routes->map(fn (TransitRoute $route): array => [
            ...$this->routeSummary($route),
            'stops' => $this->stopPoints($route),
            'is_favorite' => $favorites->contains($route->id),
            'next_departure' => $nextDepartures->get($route->id),
            'vehicles' => $live->get($route->id, collect())
                ->map(fn (array $live): array => $this->liveVehicle($live['trip'], $live['etas']))
                ->values()
                ->all(),
        ])->all();
    }

    /**
     * Todo lo que necesita el Detalle de ruta: paradas con su estado, la unidad
     * principal y las próximas salidas del día.
     *
     * @return array<string, mixed>
     */
    public function routeDetail(TransitRoute $route, ?User $user): array
    {
        $route->loadMissing(['stops', 'campus']);

        $live = $this->liveTrips([$route->id]);
        $lead = $live->first();

        return [
            'route' => [
                ...$this->routeSummary($route),
                'frequency_minutes' => $route->frequency_minutes,
                'service_starts_at' => substr($route->service_starts_at, 0, 5),
                'service_ends_at' => substr($route->service_ends_at, 0, 5),
                'campus' => ['id' => $route->campus->id, 'name' => $route->campus->name],
                'is_favorite' => $user !== null && $user->favoriteRoutes()->whereKey($route->id)->exists(),
            ],
            'stops' => $this->stopTimeline($route, $lead === null ? null : collect($lead['etas'])->keyBy('stop_id')),
            'vehicles' => $live->map(fn (array $live): array => $this->liveVehicle($live['trip'], $live['etas']))->all(),
            'upcoming_departures' => $this->upcomingDepartures($route),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function routeSummary(TransitRoute $route): array
    {
        return [
            'id' => $route->id,
            'number' => $route->number,
            'name' => $route->name,
            'description' => $route->description,
            'color' => $route->color,
            'encoded_polyline' => $route->encoded_polyline,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function stopPoints(TransitRoute $route): array
    {
        return $route->stops->map(fn (Stop $stop): array => [
            'id' => $stop->id,
            'name' => $stop->name,
            'latitude' => $stop->latitude,
            'longitude' => $stop->longitude,
            'position' => (int) $stop->pivot->getAttribute('position'),
            'minutes_from_start' => (int) $stop->pivot->getAttribute('minutes_from_start'),
            'has_shelter' => $stop->has_shelter,
        ])->values()->all();
    }

    /**
     * Viajes en curso con sus llegadas estimadas, el que llega antes primero.
     *
     * @param  array<int, int>  $routeIds
     * @return Collection<int, array{trip: Trip, etas: list<array{stop_id: int, stop_name: string, trip_id: int|null, distance_meters: float, eta_minutes: int, arrives_at: CarbonImmutable, delay_minutes: int}>}>
     */
    private function liveTrips(array $routeIds): Collection
    {
        return Trip::query()
            ->inProgress()
            ->whereIn('route_id', $routeIds)
            ->whereNotNull('vehicle_id')
            ->with(['vehicle', 'driver:id,name', 'latestPosition', 'route.stops'])
            ->get()
            ->map(fn (Trip $trip): array => ['trip' => $trip, 'etas' => $this->etaCalculator->forTrip($trip)])
            ->sortBy(fn (array $live): int => $live['etas'][0]['eta_minutes'] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * @param  list<array{stop_id: int, stop_name: string, trip_id: int|null, distance_meters: float, eta_minutes: int, arrives_at: CarbonImmutable, delay_minutes: int}>  $etas
     * @return array<string, mixed>
     */
    private function liveVehicle(Trip $trip, array $etas): array
    {
        $position = $trip->latestPosition;
        $next = $etas[0] ?? null;
        $delay = $next['delay_minutes'] ?? 0;

        return [
            'trip_id' => $trip->id,
            'route_id' => $trip->route_id,
            'vehicle' => [
                'id' => $trip->vehicle->id,
                'code' => $trip->vehicle->code,
                'plate' => $trip->vehicle->plate,
                'model' => $trip->vehicle->model,
                'capacity' => $trip->vehicle->capacity,
                'amenities' => $trip->vehicle->amenities ?? [],
            ],
            'driver' => $trip->driver === null ? null : [
                'id' => $trip->driver->id,
                'name' => $trip->driver->name,
            ],
            'occupancy' => $trip->occupancy,
            'position' => $position === null ? null : [
                'latitude' => $position->latitude,
                'longitude' => $position->longitude,
                'speed_kmh' => $position->speed_kmh,
                'heading' => $position->heading,
                'recorded_at' => $position->recorded_at->toIso8601String(),
            ],
            'next_stop' => $next === null ? null : [
                'id' => $next['stop_id'],
                'name' => $next['stop_name'],
                'eta_minutes' => $next['eta_minutes'],
                'arrives_at' => $next['arrives_at']->toIso8601String(),
                'distance_meters' => $next['distance_meters'],
            ],
            'delay_minutes' => $delay,
            'is_delayed' => $delay >= EtaCalculator::DELAY_THRESHOLD_MINUTES,
            'progress' => $this->progress($trip),
        ];
    }

    /**
     * Fracción del recorrido ya hecha (0 a 1), o null sin posición.
     */
    private function progress(Trip $trip): ?float
    {
        $position = $trip->latestPosition;
        $path = RoutePath::fromRoute($trip->route);

        if ($position === null || $path === null) {
            return null;
        }

        return round($path->distanceAlongFor($position->latitude, $position->longitude) / $path->lengthMeters(), 3);
    }

    /**
     * Paradas en orden con su estado respecto a la unidad que llega antes:
     * passed (ya pasó), current (la unidad está ahí), next (siguiente) o
     * pending. Sin unidad en viaje, todas quedan pending.
     *
     * @param  Collection<int, array{stop_id: int, stop_name: string, trip_id: int|null, distance_meters: float, eta_minutes: int, arrives_at: CarbonImmutable, delay_minutes: int}>|null  $etas
     * @return list<array<string, mixed>>
     */
    private function stopTimeline(TransitRoute $route, ?Collection $etas): array
    {
        $firstAhead = $etas?->keys()->first();

        return collect($this->stopPoints($route))->map(function (array $stop) use ($etas, $firstAhead): array {
            $eta = $etas?->get($stop['id']);
            $state = match (true) {
                $etas === null => 'pending',
                $eta === null => 'passed',
                $stop['id'] === $firstAhead && $eta['distance_meters'] <= self::AT_STOP_METERS => 'current',
                $stop['id'] === $firstAhead => 'next',
                default => 'pending',
            };

            return [
                ...$stop,
                'state' => $state,
                'eta_minutes' => $eta['eta_minutes'] ?? null,
                'arrives_at' => $eta === null ? null : $eta['arrives_at']->toIso8601String(),
            ];
        })->all();
    }

    /**
     * Primera salida que queda hoy por ruta ("HH:MM").
     *
     * @param  array<int, int>  $routeIds
     * @return Collection<int, string>
     */
    private function nextDepartures(array $routeIds): Collection
    {
        $now = CarbonImmutable::now();

        return Schedule::query()
            ->whereIn('route_id', $routeIds)
            ->where('weekday', $now->dayOfWeekIso)
            ->where('departs_at', '>=', $now->format('H:i:s'))
            ->orderBy('departs_at')
            ->get(['route_id', 'departs_at'])
            ->unique('route_id')
            ->mapWithKeys(fn (Schedule $schedule): array => [$schedule->route_id => substr($schedule->departs_at, 0, 5)]);
    }

    /**
     * Salidas que quedan hoy, con la unidad y el conductor si el viaje ya
     * está generado y asignado.
     *
     * @return list<array<string, mixed>>
     */
    private function upcomingDepartures(TransitRoute $route): array
    {
        $now = CarbonImmutable::now();
        $schedules = $route->schedules()
            ->where('weekday', $now->dayOfWeekIso)
            ->where('departs_at', '>=', $now->format('H:i:s'))
            ->orderBy('departs_at')
            ->limit(6)
            ->get();

        $trips = Trip::query()
            ->whereIn('schedule_id', $schedules->modelKeys())
            ->whereDate('scheduled_at', $now->toDateString())
            ->with(['vehicle:id,code', 'driver:id,name'])
            ->get()
            ->keyBy('schedule_id');

        return $schedules->map(function (Schedule $schedule) use ($trips): array {
            $trip = $trips->get($schedule->id);

            return [
                'schedule_id' => $schedule->id,
                'departs_at' => substr($schedule->departs_at, 0, 5),
                'status' => $trip?->status->value ?? 'scheduled',
                'vehicle_code' => $trip?->vehicle?->code,
                'driver_name' => $trip?->driver?->name,
            ];
        })->all();
    }
}
