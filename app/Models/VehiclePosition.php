<?php

namespace App\Models;

use Database\Factories\VehiclePositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Posición puntual reportada por una unidad durante un viaje.
 *
 * La tabla no lleva created_at ni updated_at: la marca de tiempo es
 * recorded_at, que es la que usa el mapa en tiempo real.
 *
 * @property int $id
 * @property int $vehicle_id
 * @property int|null $trip_id
 * @property float $latitude
 * @property float $longitude
 * @property float|null $speed_kmh
 * @property int|null $heading
 * @property Carbon $recorded_at
 */
#[Fillable([
    'vehicle_id',
    'trip_id',
    'latitude',
    'longitude',
    'speed_kmh',
    'heading',
    'recorded_at',
])]
class VehiclePosition extends Model
{
    /** @use HasFactory<VehiclePositionFactory> */
    use HasFactory;

    /**
     * La tabla solo guarda recorded_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vehicle_id' => 'integer',
            'trip_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'speed_kmh' => 'float',
            'heading' => 'integer',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
