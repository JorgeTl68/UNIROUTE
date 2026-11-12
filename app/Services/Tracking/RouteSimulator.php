<?php

namespace App\Services\Tracking;

/**
 * Hace avanzar las unidades simuladas y registra cada posición.
 */
class RouteSimulator
{
    public function __construct(private readonly PositionRecorder $recorder) {}

    /**
     * @param  list<SimulatedUnit>  $units
     * @return int Posiciones registradas.
     */
    public function tick(array $units, int $intervalSeconds): int
    {
        $recorded = 0;

        foreach ($units as $unit) {
            $position = $unit->advance($intervalSeconds);

            // Suben y bajan pasajeros poco a poco, sin pasar de la capacidad.
            if ($unit->trip !== null) {
                $unit->trip->update([
                    'occupancy' => max(0, min(
                        $unit->vehicle->capacity,
                        $unit->trip->occupancy + random_int(-2, 3),
                    )),
                ]);
            }

            $this->recorder->record(
                $unit->vehicle,
                $position['latitude'],
                $position['longitude'],
                round($unit->speedMetersPerSecond * 3.6, 2),
                $position['heading'],
                $unit->trip,
            );

            $recorded++;
        }

        return $recorded;
    }
}
