<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo TypeScript: Driver. Solo datos públicos del conductor, nunca su correo.
 *
 * @mixin User
 */
class DriverResource extends JsonResource
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
            'driver_profile' => DriverProfileResource::make($this->whenLoaded('driverProfile')),
        ];
    }
}
