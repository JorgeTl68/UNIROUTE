<?php

namespace App\Events;

use App\Models\Trip;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Cambio de estado de un viaje (iniciado, terminado o cancelado).
 *
 * Se transmite por los canales públicos de la ruta y del campus para que el
 * mapa y las pantallas del conductor actualicen la unidad sin recargar.
 */
class TripStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tripId,
        public readonly int $routeId,
        public readonly ?int $vehicleId,
        public readonly string $status,
        public readonly CarbonInterface $changedAt,
        public readonly ?int $campusId = null,
    ) {}

    public static function fromTrip(Trip $trip, ?CarbonInterface $changedAt = null): self
    {
        return new self(
            $trip->id,
            $trip->route_id,
            $trip->vehicle_id,
            $trip->status->value,
            $changedAt ?? now(),
            $trip->route?->campus_id,
        );
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [new Channel('route.'.$this->routeId)];

        if ($this->campusId !== null) {
            $channels[] = new Channel('campus.'.$this->campusId);
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'trip_id' => $this->tripId,
            'route_id' => $this->routeId,
            'vehicle_id' => $this->vehicleId,
            'status' => $this->status,
            'changed_at' => $this->changedAt->toIso8601String(),
        ];
    }
}
