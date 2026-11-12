<?php

namespace App\Models;

use Database\Factories\ArrivalAlarmFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * "Avisarme al llegar": avisa al estudiante cuando la unidad de una ruta está a
 * cierta cantidad de minutos de su parada.
 *
 * @property int $id
 * @property int $user_id
 * @property int $route_id
 * @property int $stop_id
 * @property int $minutes_before
 * @property bool $is_active
 * @property int|null $last_trip_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'route_id', 'stop_id', 'minutes_before', 'is_active', 'last_trip_id'])]
class ArrivalAlarm extends Model
{
    /** @use HasFactory<ArrivalAlarmFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minutes_before' => 'integer',
            'is_active' => 'boolean',
            'last_trip_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<TransitRoute, $this>
     */
    public function route(): BelongsTo
    {
        return $this->belongsTo(TransitRoute::class, 'route_id');
    }

    /**
     * @return BelongsTo<Stop, $this>
     */
    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }
}
