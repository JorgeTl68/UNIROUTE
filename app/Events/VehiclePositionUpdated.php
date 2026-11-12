<?php

namespace App\Events;

use App\Models\Trip;
use App\Models\VehiclePosition;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Nueva posición de una unidad en viaje.
 *
 * Se transmite por los canales públicos de la ruta y del campus para que el
 * mapa mueva la unidad sin recargar. El contrato está en transit.ts
 * (VehiclePositionUpdatedEvent).
 */
class VehiclePositionUpdated implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly VehiclePosition $position,
        public readonly Trip $trip,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('route.'.$this->trip->route_id),
            new Channel('campus.'.$this->trip->route->campus_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'vehicle_id' => $this->position->vehicle_id,
            'trip_id' => $this->trip->id,
            'route_id' => $this->trip->route_id,
            'position' => [
                'latitude' => $this->position->latitude,
                'longitude' => $this->position->longitude,
                'speed_kmh' => $this->position->speed_kmh,
                'heading' => $this->position->heading,
                'recorded_at' => $this->position->recorded_at->toIso8601String(),
            ],
            'occupancy' => $this->trip->occupancy,
        ];
    }
}
