<?php

namespace App\Events;

use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Incidencia nueva (tráfico, desvío, falla o emergencia).
 *
 * Va por el canal del campus para que el Dashboard en Vivo y la consola de
 * flota muestren el aviso al momento. Contrato en transit.ts
 * (IncidentReportedEvent).
 */
class IncidentReported implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly Incident $incident,
        public readonly int $campusId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [new Channel('campus.'.$this->campusId)];

        if ($this->incident->route_id !== null) {
            $channels[] = new Channel('route.'.$this->incident->route_id);
        }

        return $channels;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'incident' => IncidentResource::make($this->incident)->resolve(),
        ];
    }
}
