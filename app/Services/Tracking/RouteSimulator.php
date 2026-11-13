<?php

namespace App\Services\Tracking;

use App\Enums\TripStatus;
use App\Models\Trip;

/**
 * Hace avanzar las unidades simuladas y registra cada posición. Los pasajeros
 * suben en las paradas y bajan todos en la última; al terminar el recorrido
 * el viaje se cierra y la unidad sale otra vez desde el inicio con uno nuevo.
 */
class RouteSimulator
{
    public function __construct(
        private readonly PositionRecorder $recorder,
        private readonly TripLifecycle $lifecycle,
    ) {}

    /**
     * @param  list<SimulatedUnit>  $units
     * @return int Posiciones registradas.
     */
    public function tick(array $units, int $intervalSeconds): int
    {
        $recorded = 0;

        foreach ($units as $unit) {
            $step = $unit->advance($intervalSeconds);

            if ($step['finished']) {
                $unit->restart($this->nextTrip($unit->trip));
                $step = $unit->advance(0);
            }

            if ($unit->trip !== null && $step['arrived_at_stop'] !== null) {
                $this->boardPassengers($unit, $step['is_last_stop']);
            }

            $this->recorder->record(
                $unit->vehicle,
                $step['latitude'],
                $step['longitude'],
                $step['speed_kmh'],
                $step['heading'],
                $unit->trip,
            );

            $recorded++;
        }

        return $recorded;
    }

    /**
     * En cada parada suben algunos pasajeros; en la última (la UTC) bajan todos.
     */
    private function boardPassengers(SimulatedUnit $unit, bool $isLastStop): void
    {
        $trip = $unit->trip;

        if ($trip === null) {
            return;
        }

        $trip->update([
            'occupancy' => $isLastStop
                ? 0
                : min($unit->vehicle->capacity, $trip->occupancy + random_int(2, 9)),
        ]);
    }

    /**
     * Cierra el viaje que llegó al final y abre el siguiente con la misma
     * unidad y el mismo conductor, como una nueva salida.
     */
    private function nextTrip(?Trip $finished): ?Trip
    {
        if ($finished === null || $finished->status !== TripStatus::InProgress) {
            return null;
        }

        $this->lifecycle->finish($finished);

        $trip = Trip::create([
            'route_id' => $finished->route_id,
            'vehicle_id' => $finished->vehicle_id,
            'driver_id' => $finished->driver_id,
            'scheduled_at' => now(),
            'status' => TripStatus::Scheduled,
            'occupancy' => 0,
        ]);

        return $this->lifecycle->start($trip);
    }
}
