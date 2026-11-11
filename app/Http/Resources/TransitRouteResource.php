<?php

namespace App\Http\Resources;

use App\Models\TransitRoute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo TypeScript: TransitRoute. Incluye campus, stops (ordenadas) y
 * stops_count solo si se cargaron con load()/withCount().
 *
 * @mixin TransitRoute
 */
class TransitRouteResource extends JsonResource
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
            'campus_id' => $this->campus_id,
            'number' => $this->number,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'encoded_polyline' => $this->encoded_polyline,
            'frequency_minutes' => $this->frequency_minutes,
            'service_starts_at' => $this->service_starts_at,
            'service_ends_at' => $this->service_ends_at,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'campus' => CampusResource::make($this->whenLoaded('campus')),
            'stops' => StopResource::collection($this->whenLoaded('stops')),
            'stops_count' => $this->whenCounted('stops'),
        ];
    }
}
