<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stop;
use App\Models\TransitRoute;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Inertia\Inertia;
use Inertia\Response;

class TransitRouteController extends Controller
{
    public function index(): Response
    {
        $routes = TransitRoute::query()
            ->with(['campus:id,name', 'stops:id'])
            ->withCount('schedules')
            ->orderBy('campus_id')
            ->orderBy('number')
            ->orderBy('id')
            ->get()
            ->map(function (TransitRoute $route): array {
                /** @var Pivot|null $lastStopPivot */
                $lastStopPivot = $route->stops->last()?->getRelation('pivot');
                $duration = $lastStopPivot?->getAttribute('minutes_from_start');

                return [
                    'id' => $route->id,
                    'number' => $route->number,
                    'name' => $route->name,
                    'color' => $route->color,
                    'campus' => $route->campus->name,
                    'is_active' => $route->is_active,
                    'stops_count' => $route->stops->count(),
                    'schedules_count' => $route->getAttribute('schedules_count'),
                    'duration_minutes' => $duration === null ? null : (int) $duration,
                    'frequency_minutes' => $route->frequency_minutes,
                    'service_starts_at' => $route->service_starts_at,
                    'service_ends_at' => $route->service_ends_at,
                ];
            });

        $stops = Stop::query()
            ->with(['routes' => fn ($query) => $query
                ->select(['routes.id', 'routes.number', 'routes.name', 'routes.color'])
                ->orderBy('routes.number')
                ->orderBy('routes.id')])
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'has_shelter'])
            ->map(fn (Stop $stop): array => [
                'id' => $stop->id,
                'name' => $stop->name,
                'has_shelter' => $stop->has_shelter,
                'routes' => $stop->routes->unique()
                    ->map(fn (TransitRoute $route): array => $route->only(['id', 'number', 'name', 'color']))
                    ->values(),
            ]);

        return Inertia::render('admin/routes/index', ['routes' => $routes, 'stops' => $stops]);
    }
}
