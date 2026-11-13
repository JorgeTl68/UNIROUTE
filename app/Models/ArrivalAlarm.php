<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'route_id',
    'stop_id',
    'minutes_before',
    'is_active',
])]
class ArrivalAlarm extends Model
{
    protected function casts(): array
    {
        return [
            'minutes_before' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransitRoute::class, 'route_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(Stop::class);
    }
}
