<?php

namespace App\Console\Commands;

use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Tracking\RoutePath;
use App\Services\Tracking\RouteSimulator;
use App\Services\Tracking\SimulatedUnit;
use App\Services\Tracking\TripLifecycle;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SimulateFleetCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'uniroute:simular
        {--rutas= : IDs o números de ruta separados por coma; por defecto, todas las activas}
        {--intervalo=5 : Segundos entre actualizaciones de posición}
        {--unidades= : Cuántas unidades simular; por defecto, todas las disponibles}
        {--iteraciones=0 : Actualizaciones antes de salir; 0 = hasta Ctrl+C}
        {--iniciar : Inicia un viaje en cada ruta sin uno en curso y simula solo las unidades en viaje}';

    /**
     * @var string
     */
    protected $description = 'Mueve las unidades simuladas sobre la polilínea de cada ruta (S1-09)';

    public function handle(): int
    {
        $interval = (int) $this->option('intervalo');
        $iterations = (int) $this->option('iteraciones');

        if ($interval < 0 || $iterations < 0) {
            $this->error('El intervalo y las iteraciones deben ser cero o mayores.');

            return self::FAILURE;
        }

        if ($interval === 0 && $iterations === 0) {
            $this->error('Con --intervalo=0 usa --iteraciones mayor a 0 para evitar un bucle infinito.');

            return self::FAILURE;
        }

        $routes = $this->resolveRoutes();

        if ($routes->isEmpty()) {
            $this->error('No hay rutas activas que coincidan con --rutas.');

            return self::FAILURE;
        }

        if ($this->option('iniciar')) {
            $started = $this->startMissingTrips($routes);
            $this->info("Viajes iniciados: {$started}.");
        }

        $vehicles = $this->option('iniciar') ? $this->vehiclesOnTrips($routes) : $this->resolveVehicles();

        if ($vehicles->isEmpty()) {
            $this->error('No hay unidades disponibles. Ejecuta php artisan db:seed --class=FleetSeeder.');

            return self::FAILURE;
        }

        $units = $this->buildUnits($vehicles, $routes);

        if ($units === []) {
            $this->error('Ninguna ruta tiene puntos suficientes (encoded_polyline o paradas) para simular.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Simulando %d unidades en %d rutas cada %d s%s. Ctrl+C para detener.',
            count($units),
            $routes->count(),
            $interval,
            $iterations > 0 ? sprintf(' durante %d actualizaciones', $iterations) : '',
        ));

        $simulator = app(RouteSimulator::class);
        $round = 0;

        do {
            $round++;
            $recorded = $simulator->tick($units, $interval);
            $this->line(sprintf('[%s] %d posiciones registradas.', now()->format('H:i:s'), $recorded));

            if ($iterations > 0 && $round >= $iterations) {
                break;
            }

            if ($interval > 0) {
                sleep($interval);
            }
        } while (true);

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, TransitRoute>
     */
    private function resolveRoutes(): Collection
    {
        $filter = trim((string) $this->option('rutas'));
        $query = TransitRoute::query()->active()->with('stops')->orderBy('number');

        if ($filter === '') {
            return $query->get();
        }

        $tokens = array_values(array_filter(
            array_map('trim', explode(',', $filter)),
            fn (string $token): bool => $token !== '' && ctype_digit($token),
        ));

        if ($tokens === []) {
            return collect();
        }

        $ids = array_map('intval', $tokens);

        return $query->where(function ($query) use ($ids): void {
            $query->whereIn('id', $ids)->orWhereIn('number', $ids);
        })->get();
    }

    /**
     * @return Collection<int, Vehicle>
     */
    private function resolveVehicles(): Collection
    {
        $limit = $this->option('unidades');
        $query = Vehicle::query()
            ->where('status', '!=', VehicleStatus::Maintenance)
            ->orderBy('id');

        if ($limit !== null && $limit !== '') {
            $query->limit(max(1, (int) $limit));
        }

        return $query->get();
    }

    /**
     * Inicia un viaje en cada ruta que no tenga uno en curso: toma el viaje
     * programado de hoy más cercano (o crea uno para ahora) y le asigna una
     * unidad y un conductor libres.
     *
     * @param  Collection<int, TransitRoute>  $routes
     */
    private function startMissingTrips(Collection $routes): int
    {
        $busy = Trip::query()->inProgress()->get(['vehicle_id', 'driver_id']);
        $vehicles = Vehicle::query()
            ->where('status', '!=', VehicleStatus::Maintenance)
            ->whereNotIn('id', $busy->pluck('vehicle_id')->filter())
            ->orderBy('id')
            ->get();
        $drivers = User::query()
            ->where('role', UserRole::Driver)
            ->whereNotIn('id', $busy->pluck('driver_id')->filter())
            ->orderBy('id')
            ->get();
        $lifecycle = app(TripLifecycle::class);
        $started = 0;

        foreach ($routes as $route) {
            if (Trip::query()->inProgress()->where('route_id', $route->id)->exists()) {
                continue;
            }

            $vehicle = $vehicles->shift();

            if ($vehicle === null) {
                $this->warn('No quedan unidades libres para iniciar más viajes.');

                break;
            }

            $trip = Trip::query()
                ->where('route_id', $route->id)
                ->where('status', TripStatus::Scheduled)
                ->whereDate('scheduled_at', today())
                ->where('scheduled_at', '<=', now()->addMinutes(30))
                ->orderByDesc('scheduled_at')
                ->first() ?? new Trip([
                    'route_id' => $route->id,
                    'scheduled_at' => now(),
                    'status' => TripStatus::Scheduled,
                ]);

            $trip->fill([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $drivers->shift()?->id,
            ])->save();

            $lifecycle->start($trip);
            $started++;
        }

        return $started;
    }

    /**
     * Unidades con un viaje en curso en las rutas elegidas.
     *
     * @param  Collection<int, TransitRoute>  $routes
     * @return Collection<int, Vehicle>
     */
    private function vehiclesOnTrips(Collection $routes): Collection
    {
        return Vehicle::query()
            ->whereIn('id', Trip::query()->inProgress()->whereIn('route_id', $routes->modelKeys())->select('vehicle_id'))
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     * @param  Collection<int, TransitRoute>  $routes
     * @return list<SimulatedUnit>
     */
    private function buildUnits(Collection $vehicles, Collection $routes): array
    {
        /** @var array<int, RoutePath> $paths */
        $paths = [];

        foreach ($routes as $route) {
            $path = RoutePath::fromRoute($route);

            if ($path === null) {
                $this->warn("La ruta {$route->number} no tiene puntos suficientes; se omite.");

                continue;
            }

            $paths[$route->id] = $path;
        }

        if ($paths === []) {
            return [];
        }

        /** @var Collection<int, Trip> $trips */
        $trips = Trip::query()
            ->inProgress()
            ->whereIn('vehicle_id', $vehicles->pluck('id'))
            ->get()
            ->keyBy('vehicle_id');

        $units = [];
        $routeIds = array_keys($paths);
        $unitCount = $vehicles->count();

        foreach ($vehicles->values() as $index => $vehicle) {
            $trip = $trips->get($vehicle->id);

            if ($trip !== null && isset($paths[$trip->route_id])) {
                $path = $paths[$trip->route_id];
            } else {
                $trip = null;
                $path = $paths[$routeIds[$index % count($routeIds)]];
            }

            $speed = random_int(600, 1100) / 100;
            $progress = $path->lengthMeters() * (($index + 0.5) / $unitCount);

            $units[] = new SimulatedUnit($vehicle, $trip, $path, $speed, $progress);
        }

        return $units;
    }
}
