<?php

namespace App\Models;

use App\Enums\TripStatus;
use Carbon\CarbonInterface;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Un recorrido concreto de una unidad en una ruta.
 *
 * @property int $id
 * @property int $route_id
 * @property int|null $schedule_id
 * @property int|null $vehicle_id
 * @property int|null $driver_id
 * @property Carbon $scheduled_at
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $delay_notified_at
 * @property TripStatus $status
 * @property int $occupancy
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'route_id',
    'schedule_id',
    'vehicle_id',
    'driver_id',
    'scheduled_at',
    'started_at',
    'ended_at',
    'delay_notified_at',
    'status',
    'occupancy',
])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'delay_notified_at' => 'datetime',
            'status' => TripStatus::class,
            'occupancy' => 'integer',
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
     * @return BelongsTo<Schedule, $this>
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
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
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /**
     * Última posición reportada durante este viaje.
     *
     * @return HasOne<VehiclePosition, $this>
     */
    public function latestPosition(): HasOne
    {
        return $this->hasOne(VehiclePosition::class)->latestOfMany('recorded_at');
    }

    /**
     * @param  Builder<Trip>  $query
     */
    public function scopeInProgress(Builder $query): void
    {
        $query->where('status', TripStatus::InProgress);
    }

    /**
     * Salidas programadas desde ahora, de la más próxima a la más lejana.
     *
     * @param  Builder<Trip>  $query
     */
    public function scopeUpcoming(Builder $query, ?CarbonInterface $from = null): void
    {
        $query->where('status', TripStatus::Scheduled)
            ->where('scheduled_at', '>=', $from ?? now())
            ->orderBy('scheduled_at');
    }

    /**
     * @param  Builder<Trip>  $query
     */
    public function scopeOnDate(Builder $query, CarbonInterface $date): void
    {
        $query->whereDate('scheduled_at', $date->toDateString());
    }
}
