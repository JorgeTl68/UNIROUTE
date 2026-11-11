<?php

namespace App\Http\Resources;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo TypeScript: Campus (resources/js/types/transit.ts).
 *
 * @mixin Campus
 */
class CampusResource extends JsonResource
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
            'slug' => $this->slug,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
