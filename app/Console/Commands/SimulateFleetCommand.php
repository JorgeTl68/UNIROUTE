<?php

namespace App\Console\Commands;

use App\Enums\VehicleStatus;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\Tracking\RoutePath;
use App\Services\Tracking\RouteSimulator;
use App\Services\Tracking\SimulatedUnit;
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
        {--iteraciones=0 : Actualizaciones antes de salir; 0 = hasta Ctrl+C}';

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

        $vehicles = $this->resolveVehicles();

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
