<?php

namespace App\Models;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use Database\Factories\IncidentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Incidencia operativa: tráfico, desvío, falla o emergencia reportada por un
 * estudiante, un conductor o el área de transporte.
 *
 * @property int $id
 * @property IncidentType $type
 * @property IncidentSeverity $severity
 * @property int|null $route_id
 * @property int|null $vehicle_id
 * @property int $reported_by
 * @property string $message
 * @property float|null $latitude
 * @property float|null $longitude
 * @property Carbon|null $students_notified_at
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'type',
    'severity',
    'route_id',
    'vehicle_id',
    'reported_by',
    'message',
    'latitude',
    'longitude',
    'students_notified_at',
    'resolved_at',
])]
class Incident extends Model
{
    /** @use HasFactory<IncidentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IncidentType::class,
            'severity' => IncidentSeverity::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'students_notified_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TransitRoute, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(TransitRoute::class, 'route_id');
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * @param  Builder<Incident>  $query
     */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('resolved_at');
    }
}
