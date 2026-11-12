<?php

namespace App\Services\Tracking;

use App\Events\VehiclePositionUpdated;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use App\Services\Notifications\TripAlerts;
use App\Services\Realtime\SafeBroadcaster;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Registra posiciones de las unidades en vehicle_positions.
 *
 * Lo usan el simulador (S1-09), la pantalla del conductor y el rastreo en
 * tiempo real. Normaliza los valores para que el mapa reciba datos válidos.
 */
class PositionRecorder
{
    public function __construct(
        private readonly TripAlerts $alerts,
        private readonly SafeBroadcaster $broadcaster,
    ) {}

    /**
     * Guarda una posición y devuelve el registro creado.
     *
     * @param  float  $latitude  Latitud entre -90 y 90.
     * @param  float  $longitude  Longitud entre -180 y 180.
     * @param  float|null  $speedKmh  Velocidad en km/h; los valores negativos se ajustan a 0.
     * @param  int|null  $heading  Rumbo en grados; se normaliza al rango 0-359.
     * @param  Trip|null  $trip  Viaje en curso al que pertenece la posición.
     * @param  CarbonInterface|null  $recordedAt  Momento del registro; por defecto, ahora.
     */
    public function record(
        Vehicle $vehicle,
        float $latitude,
        float $longitude,
        ?float $speedKmh = null,
        ?int $heading = null,
        ?Trip $trip = null,
        ?CarbonInterface $recordedAt = null,
    ): VehiclePosition {
        $this->ensureValidCoordinates($latitude, $longitude);

        if ($trip !== null && $trip->vehicle_id !== null && $trip->vehicle_id !== $vehicle->id) {
            throw new InvalidArgumentException('El viaje indicado no pertenece a la unidad.');
        }

        $position = VehiclePosition::create([
            'vehicle_id' => $vehicle->id,
            'trip_id' => $trip?->id,
            'latitude' => round($latitude, 7),
            'longitude' => round($longitude, 7),
            'speed_kmh' => $speedKmh === null ? null : round(max(0.0, $speedKmh), 2),
            'heading' => $heading === null ? null : $this->normalizeHeading($heading),
            'recorded_at' => $recordedAt ?? now(),
        ]);

        // Sin viaje no hay ruta que actualizar ni avisos que mandar.
        if ($trip !== null) {
            $this->alerts->check($trip);
            $this->broadcaster->dispatch(new VehiclePositionUpdated($position, $trip));
        }

        return $position;
    }

    /**
     * Rumbo normalizado a 0-359 grados.
     */
    public function normalizeHeading(int $heading): int
    {
        return (($heading % 360) + 360) % 360;
    }

    private function ensureValidCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException('La latitud debe estar entre -90 y 90.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException('La longitud debe estar entre -180 y 180.');
        }
    }
}
