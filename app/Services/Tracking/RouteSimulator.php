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
