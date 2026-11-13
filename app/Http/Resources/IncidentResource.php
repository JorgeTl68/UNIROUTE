<?php

namespace App\Http\Resources;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo TypeScript: Incident (con route y reporter cuando vienen cargados).
 *
 * @mixin Incident
 */
class IncidentResource extends JsonResource
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
            'detour' => $this->whenLoaded('detour', fn (): ?array => $this->detour === null ? null : ['active' => $this->detour->restored_at === null, 'skipped_stop_ids' => $this->detour->skipped_stop_ids, 'restored_at' => $this->detour->restored_at]),
            'type' => $this->type,
            'type_label' => $this->type->label(),
            'severity' => $this->severity,
            'severity_label' => $this->severity->label(),
            'route_id' => $this->route_id,
            'vehicle_id' => $this->vehicle_id,
            'reported_by' => $this->reported_by,
            'message' => $this->message,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'students_notified_at' => $this->students_notified_at,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'route' => $this->whenLoaded('route', fn (): ?array => $this->route === null ? null : [
                'id' => $this->route->id,
                'number' => $this->route->number,
                'name' => $this->route->name,
                'color' => $this->route->color,
            ]),
            'vehicle' => $this->whenLoaded('vehicle', fn (): ?array => $this->vehicle === null ? null : [
                'id' => $this->vehicle->id,
                'code' => $this->vehicle->code,
            ]),
            'reporter' => $this->whenLoaded('reporter', fn (): array => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
                'role' => $this->reporter->role,
            ]),
        ];
    }
}
