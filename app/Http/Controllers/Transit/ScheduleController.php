<?php

namespace App\Http\Controllers\Transit;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Services\Transit\CurrentCampus;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Horarios e Itinerarios (S3-04): salidas por ruta y día del campus elegido.
 * La llegada a cada parada es la salida más minutes_from_start.
 */
class ScheduleController extends Controller
{
    public function __invoke(Request $request, CurrentCampus $currentCampus): Response
    {
        $now = CarbonImmutable::now();
        $campus = $currentCampus->resolve($request);
        $weekday = $request->integer('day', $now->dayOfWeekIso);
        $weekday = $weekday >= 1 && $weekday <= 7 ? $weekday : $now->dayOfWeekIso;

        $routes = $campus === null ? collect() : TransitRoute::query()
            ->active()
            ->forCampus($campus)
            ->with([
                'stops',
                'schedules' => fn ($query) => $query->where('weekday', $weekday)->orderBy('departs_at'),
            ])
            ->orderBy('number')
            ->get();

        return Inertia::render('schedules/index', [
            'campus' => $campus?->only(['id', 'name', 'slug']),
            'weekday' => $weekday,
            'today' => $now->dayOfWeekIso,
            'now' => $now->format('H:i'),
            'routes' => $routes->map(fn (TransitRoute $route): array => [
                'id' => $route->id,
                'number' => $route->number,
                'name' => $route->name,
                'description' => $route->description,
                'color' => $route->color,
                'frequency_minutes' => $route->frequency_minutes,
                'stops' => $route->stops->map(fn (Stop $stop): array => [
                    'id' => $stop->id,
                    'name' => $stop->name,
                    'minutes_from_start' => (int) $stop->pivot->getAttribute('minutes_from_start'),
                ])->values(),
                'departures' => $route->schedules
                    ->map(fn (Schedule $schedule): string => substr($schedule->departs_at, 0, 5))
                    ->values(),
            ]),
        ]);
    }
}
