<?php

namespace App\Http\Resources;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo TypeScript: Vehicle.
 *
 * @mixin Vehicle
 */
class VehicleResource extends JsonResource
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
            'code' => $this->code,
            'plate' => $this->plate,
            'model' => $this->model,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'amenities' => $this->amenities,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
