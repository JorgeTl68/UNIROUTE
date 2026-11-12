<?php

namespace App\Services\Tracking;

use App\Models\Trip;
use App\Models\Vehicle;

/**
 * Unidad simulada: avanza sobre el trazado de su ruta y guarda su posición.
 */
final class SimulatedUnit
{
    public function __construct(
        public readonly Vehicle $vehicle,
        public readonly ?Trip $trip,
        public readonly RoutePath $path,
        public readonly float $speedMetersPerSecond,
        public float $progressMeters,
    ) {}

    /**
     * Avanza la unidad y devuelve su nueva posición.
     *
     * @return array{latitude: float, longitude: float, heading: int}
     */
    public function advance(int $intervalSeconds): array
    {
        $length = $this->path->lengthMeters();
        $this->progressMeters = fmod(
            $this->progressMeters + $this->speedMetersPerSecond * $intervalSeconds,
            $length,
        );

        return $this->path->positionAt($this->progressMeters);
    }
}
