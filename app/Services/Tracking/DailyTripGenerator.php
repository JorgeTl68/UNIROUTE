<?php

namespace App\Services\Tracking;

use App\Enums\TripStatus;
use App\Models\Schedule;
use App\Models\Trip;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Genera los viajes de un día a partir de los horarios (schedules) activos.
 *
 * Es idempotente: si el viaje ya existe para ese horario y fecha, no lo repite.
 */
class DailyTripGenerator
{
    /**
     * @return array{created: int, skipped: int}
     */
    public function generate(CarbonInterface $date): array
    {
        $timezone = (string) config('app.timezone', 'UTC');

        $schedules = Schedule::query()
            ->where('weekday', $date->dayOfWeekIso)
            ->whereHas('route', fn (Builder $query) => $query->where('is_active', true))
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            $scheduledAt = CarbonImmutable::parse(
                $date->format('Y-m-d').' '.$schedule->departs_at,
                $timezone,
            );

            $trip = Trip::firstOrCreate(
                [
                    'schedule_id' => $schedule->id,
                    'scheduled_at' => $scheduledAt,
                ],
                [
                    'route_id' => $schedule->route_id,
                    'status' => TripStatus::Scheduled,
                    'occupancy' => 0,
                ],
            );

            $trip->wasRecentlyCreated ? $created++ : $skipped++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
