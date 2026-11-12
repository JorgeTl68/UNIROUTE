<?php

namespace App\Models;

use Database\Factories\StopFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property float $latitude
 * @property float $longitude
 * @property bool $has_shelter
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Pivot|null $pivot Datos de route_stop cuando la parada viene de $route->stops.
 */
#[Fillable(['name', 'latitude', 'longitude', 'has_shelter', 'notes'])]
class Stop extends Model
{
    /** @use HasFactory<StopFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'has_shelter' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<TransitRoute, $this>
     */
    public function routes(): BelongsToMany
    {
        return $this->belongsToMany(TransitRoute::class, 'route_stop', 'stop_id', 'route_id')
            ->withPivot(['position', 'minutes_from_start'])
            ->withTimestamps();
    }
}
