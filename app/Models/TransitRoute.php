<?php

namespace App\Models;

use Database\Factories\TransitRouteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Ruta de transporte. Se llama TransitRoute para no confundirla con las rutas
 * web de Laravel; la tabla es "routes".
 *
 * @property int $id
 * @property int $campus_id
 * @property int $number
 * @property string $name
 * @property string|null $description
 * @property string $color
 * @property string|null $encoded_polyline
 * @property int $frequency_minutes
 * @property string $service_starts_at
 * @property string $service_ends_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Table('routes')]
#[Fillable([
    'campus_id',
    'number',
    'name',
    'description',
    'color',
    'encoded_polyline',
    'frequency_minutes',
    'service_starts_at',
    'service_ends_at',
    'is_active',
])]
class TransitRoute extends Model
{
    /** @use HasFactory<TransitRouteFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'frequency_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Campus, $this>
     */
    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Paradas en el orden en que las recorre la ruta.
     *
     * @return BelongsToMany<Stop, $this>
     */
    public function stops(): BelongsToMany
    {
        return $this->belongsToMany(Stop::class, 'route_stop', 'route_id', 'stop_id')
            ->withPivot(['position', 'minutes_from_start'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * @return HasMany<Schedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'route_id');
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'route_id');
    }

    /**
     * @param  Builder<TransitRoute>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<TransitRoute>  $query
     */
    public function scopeForCampus(Builder $query, Campus|int $campus): void
    {
        $query->where('campus_id', $campus instanceof Campus ? $campus->id : $campus);
    }
}
