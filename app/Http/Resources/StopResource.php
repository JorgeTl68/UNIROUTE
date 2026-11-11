<?php

namespace App\Http\Resources;

use App\Models\Stop;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo TypeScript: Stop, o RouteStop cuando viene de $route->stops.
 *
 * @mixin Stop
 */
class StopResource extends JsonResource
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
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'has_shelter' => $this->has_shelter,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'pivot' => $this->whenPivotLoaded('route_stop', fn (): array => $this->pivotData()),
        ];
    }

    /**
     * @return array{route_id: int, stop_id: int, position: int, minutes_from_start: int}
     */
    private function pivotData(): array
    {
        /** @var Pivot $pivot */
        $pivot = $this->resource->getRelation('pivot');

        return [
            'route_id' => (int) $pivot->getAttribute('route_id'),
            'stop_id' => (int) $pivot->getAttribute('stop_id'),
            'position' => (int) $pivot->getAttribute('position'),
            'minutes_from_start' => (int) $pivot->getAttribute('minutes_from_start'),
        ];
    }
}
