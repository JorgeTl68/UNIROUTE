<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $incident_id
 * @property int $route_id
 * @property array{encoded_polyline: string|null, stops: list<array{id: int, position: int, minutes_from_start: int}>} $snapshot
 * @property list<int> $skipped_stop_ids
 * @property CarbonInterface|null $restored_at
 */
#[Fillable(['incident_id', 'route_id', 'created_by', 'restored_by', 'snapshot', 'skipped_stop_ids', 'restored_at'])]
class RouteDetour extends Model
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['snapshot' => 'array', 'skipped_stop_ids' => 'array', 'restored_at' => 'immutable_datetime'];
    }
}
