<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;
use App\Http\Resources\TransitRouteResource;
use App\Models\TransitRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Día actual: 1 = lunes, 7 = domingo.
        $today = Carbon::now(config('app.timezone'))->dayOfWeekIso;

        $day = (int) $request->query('day', $today);

        if ($day < 1 || $day > 7) {
            $day = $today;
        }

        $routes = collect();
        $favoriteRouteIds = collect();
        $arrivalAlarms = collect();

        if ($user->campus_id) {
            // Consultar únicamente rutas activas del campus.
            $routes = TransitRoute::query()
                ->active()
                ->forCampus($user->campus_id)
                ->with([
                    'stops',
                    'schedules' => function ($query) use ($day) {
                        $query->where('weekday', $day)
                            ->orderBy('departs_at');
                    },
                ])
                ->orderBy('number')
                ->get();

            // Identificadores de las rutas favoritas del estudiante.
            $favoriteRouteIds = $user->favoriteRoutes()
                ->whereHas('route', function ($query) use ($user) {
                    $query->forCampus($user->campus_id);
                })
                ->pluck('route_id')
                ->map(fn ($id) => (int) $id)
                ->values();

            // Alarmas pertenecientes al estudiante y a su campus.
            $arrivalAlarms = $user->arrivalAlarms()
                ->with(['route', 'stop'])
                ->whereHas('route', function ($query) use ($user) {
                    $query->forCampus($user->campus_id);
                })
                ->get()
                ->map(function ($alarm) {
                    return [
                        'id' => $alarm->id,
                        'route_id' => $alarm->route_id,
                        'stop_id' => $alarm->stop_id,
                        'minutes_before' => $alarm->minutes_before,
                        'is_active' => $alarm->is_active,
                    ];
                })
                ->values();
        }

        // Preparar las rutas y calcular la llegada estimada de cada parada.
        $routesData = $routes->map(function ($route) use ($request) {
            $routeData = TransitRouteResource::make($route)
                ->resolve($request);

            $routeData['schedules'] = $route->schedules
                ->map(function ($schedule) use ($route, $request) {
                    $scheduleData = ScheduleResource::make($schedule)
                        ->resolve($request);

                    $departureTime = Carbon::parse($schedule->departs_at);

                    $scheduleData['estimated_arrivals'] = $route->stops
                        ->map(function ($stop) use ($departureTime) {
                            $arrivalTime = $departureTime->copy()
                                ->addMinutes(
                                    (int) $stop->pivot->minutes_from_start
                                );

                            return [
                                'stop_id' => (int) $stop->id,
                                'position' => (int) $stop->pivot->position,
                                'arrival_time' => $arrivalTime->format('H:i'),
                            ];
                        })
                        ->values()
                        ->all();

                    return $scheduleData;
                })
                ->values()
                ->all();

            return $routeData;
        })->values();

        return Inertia::render('schedules/index', [
            'routes' => $routesData,
            'selectedDay' => $day,
            'favoriteRouteIds' => $favoriteRouteIds,
            'arrivalAlarms' => $arrivalAlarms,
        ]);
    }

    /**
     * Mostrar el detalle de una ruta.
     */
    public function show(Request $request, int $route)
    {
        $user = $request->user();

        // Buscar únicamente una ruta activa del campus del estudiante.
        $transitRoute = TransitRoute::query()
            ->active()
            ->forCampus($user->campus_id ?? 0)
            ->with([
                'campus',
                'stops',
                'schedules' => function ($query) {
                    $query->orderBy('weekday')
                        ->orderBy('departs_at');
                },
            ])
            ->findOrFail($route);

        // Calcular las llegadas estimadas de cada horario.
        $routeData = TransitRouteResource::make($transitRoute)
            ->resolve($request);

        $routeData['schedules'] = $transitRoute->schedules
            ->map(function ($schedule) use ($transitRoute, $request) {
                $scheduleData = ScheduleResource::make($schedule)
                    ->resolve($request);

                $departureTime = Carbon::parse($schedule->departs_at);

                $scheduleData['estimated_arrivals'] = $transitRoute->stops
                    ->map(function ($stop) use ($departureTime) {
                        $arrivalTime = $departureTime->copy()
                            ->addMinutes(
                                (int) $stop->pivot->minutes_from_start
                            );

                        return [
                            'stop_id' => (int) $stop->id,
                            'position' => (int) $stop->pivot->position,
                            'arrival_time' => $arrivalTime->format('H:i'),
                        ];
                    })
                    ->values()
                    ->all();

                return $scheduleData;
            })
            ->values()
            ->all();

        return Inertia::render('routes/show', [
            'route' => $routeData,
        ]);
    }
}
