<?php

namespace App\Http\Resources;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo TypeScript: Trip. route, vehicle y driver solo si se cargaron.
 *
 * @mixin Trip
 */
class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'route_id' => $this->route_id,
            'schedule_id' => $this->schedule_id,
            'vehicle_id' => $this->vehicle_id,
            'driver_id' => $this->driver_id,
            'scheduled_at' => $this->scheduled_at,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'status' => $this->status,
            'occupancy' => $this->occupancy,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'route' => TransitRouteResource::make($this->whenLoaded('route')),
            'vehicle' => VehicleResource::make($this->whenLoaded('vehicle')),
            'driver' => DriverResource::make($this->whenLoaded('driver')),
        ];
    }
}
