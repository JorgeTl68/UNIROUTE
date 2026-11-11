<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tipo TypeScript: Schedule.
 *
 * @mixin Schedule
 */
class ScheduleResource extends JsonResource
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
            'weekday' => $this->weekday,
            'departs_at' => $this->departs_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
