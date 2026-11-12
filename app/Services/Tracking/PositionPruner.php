<?php

namespace App\Services\Tracking;

use App\Models\VehiclePosition;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Poda el historial de posiciones para que vehicle_positions no crezca sin
 * límite. Por convención se conservan los últimos 7 días (S5-04).
 */
class PositionPruner
{
    public function prune(int $days = 7, ?CarbonInterface $now = null): int
    {
        if ($days < 1) {
            throw new InvalidArgumentException('Los días de historial deben ser al menos 1.');
        }

        $until = $now === null ? CarbonImmutable::now() : CarbonImmutable::instance($now);
        $cutoff = $until->subDays($days);

        return VehiclePosition::query()
            ->where('recorded_at', '<', $cutoff)
            ->delete();
    }
}
