<?php

namespace App\Services\Tracking;

use App\Models\Trip;
use App\Models\Vehicle;

/**
 * Unidad simulada: avanza sobre el trazado de su ruta como un camión de
 * verdad. Cambia un poco de velocidad con el tráfico, frena al acercarse a
 * cada parada, se detiene en ella para que suban pasajeros y, al llegar a la
 * última, espera y termina el recorrido.
 */
final class SimulatedUnit
{
    /**
     * Segundos detenida en una parada (mínimo y máximo).
     */
    private const array DWELL_SECONDS = [15, 35];

    /**
     * Segundos detenida en la última parada antes de terminar el recorrido.
     */
    private const int FINAL_DWELL_SECONDS = 45;

    /**
     * Distancia antes de una parada en la que empieza a frenar.
     */
    private const float BRAKING_METERS = 150.0;

    /**
     * Rango de la velocidad respecto a la de crucero por el tráfico.
     */
    private const float MIN_TRAFFIC = 0.7;

    private const float MAX_TRAFFIC = 1.15;

    private float $speed;

    private float $dwellRemaining = 0.0;

    private int $nextStop;

    /**
     * @param  float  $speedMetersPerSecond  Velocidad de crucero.
     * @param  list<float>  $stopDistances  Distancia de cada parada sobre el trazado, en orden.
     */
    public function __construct(
        public readonly Vehicle $vehicle,
        public ?Trip $trip,
        public readonly RoutePath $path,
        public readonly float $speedMetersPerSecond,
        public float $progressMeters,
        private readonly array $stopDistances = [],
    ) {
        $this->speed = $speedMetersPerSecond;
        $this->nextStop = $this->firstStopFrom($progressMeters);
    }

    /**
     * Avanza la unidad y devuelve su nueva posición.
     *
     * @return array{latitude: float, longitude: float, heading: int, speed_kmh: float, arrived_at_stop: int|null, is_last_stop: bool, finished: bool}
     */
    public function advance(int $intervalSeconds): array
    {
        $seconds = (float) $intervalSeconds;
        $moved = 0.0;
        $arrivedAt = null;
        $finished = false;

        if ($this->dwellRemaining > 0) {
            $waited = min($this->dwellRemaining, $seconds);
            $this->dwellRemaining -= $waited;
            $seconds -= $waited;

            // Terminó de esperar en la última parada: el recorrido acabó.
            if ($this->dwellRemaining <= 0 && $this->nextStop >= count($this->stopDistances) && $this->stopDistances !== []) {
                $finished = true;
                $seconds = 0.0;
            }
        }

        if ($seconds > 0) {
            [$moved, $arrivedAt] = $this->drive($seconds);
        }

        $position = $this->path->positionAt($this->progressMeters);
        $speed = $intervalSeconds > 0 ? $moved / $intervalSeconds : ($this->dwellRemaining > 0 ? 0.0 : $this->speed);

        return [
            ...$position,
            'speed_kmh' => round($speed * 3.6, 2),
            'arrived_at_stop' => $arrivedAt,
            'is_last_stop' => $arrivedAt !== null && $arrivedAt === count($this->stopDistances) - 1,
            'finished' => $finished,
        ];
    }

    /**
     * Empieza el recorrido otra vez desde el inicio (nuevo viaje).
     */
    public function restart(?Trip $trip): void
    {
        $this->trip = $trip;
        $this->progressMeters = 0.0;
        $this->dwellRemaining = 0.0;
        $this->speed = $this->speedMetersPerSecond;
        $this->nextStop = $this->firstStopFrom(0.0);
    }

    /**
     * Avanza durante $seconds; se detiene si alcanza la siguiente parada.
     *
     * @return array{0: float, 1: int|null} Metros recorridos y la parada a la que llegó.
     */
    private function drive(float $seconds): array
    {
        $cruise = $this->speedMetersPerSecond;
        $traffic = random_int(92, 108) / 100;
        $this->speed = max($cruise * self::MIN_TRAFFIC, min($cruise * self::MAX_TRAFFIC, $this->speed * $traffic));

        $stopAt = $this->stopDistances[$this->nextStop] ?? null;
        $speed = $this->speed;

        if ($stopAt !== null) {
            $toStop = $stopAt - $this->progressMeters;

            // Frena al acercarse, sin quedarse casi parada.
            if ($toStop < self::BRAKING_METERS) {
                $speed = max($cruise * 0.35, $speed * $toStop / self::BRAKING_METERS);
            }

            if ($speed * $seconds >= $toStop) {
                $this->progressMeters = $stopAt;
                $arrived = $this->nextStop;
                $this->nextStop++;
                $this->dwellRemaining = $this->nextStop >= count($this->stopDistances)
                    ? self::FINAL_DWELL_SECONDS
                    : random_int(self::DWELL_SECONDS[0], self::DWELL_SECONDS[1]);

                return [max(0.0, $toStop), $arrived];
            }
        }

        $distance = $speed * $seconds;
        $length = $this->path->lengthMeters();
        $this->progressMeters += $distance;

        // Sin paradas que marquen el final, la unidad da vueltas al trazado.
        if ($this->progressMeters >= $length) {
            $this->progressMeters = fmod($this->progressMeters, $length);
            $this->nextStop = $this->firstStopFrom($this->progressMeters);
        }

        return [$distance, null];
    }

    /**
     * Índice de la primera parada que queda en o después de $meters.
     */
    private function firstStopFrom(float $meters): int
    {
        foreach ($this->stopDistances as $index => $distance) {
            if ($distance >= $meters) {
                return $index;
            }
        }

        return count($this->stopDistances);
    }
}
