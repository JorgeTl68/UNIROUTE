<?php

namespace App\Http\Controllers\Transit;

use App\Http\Controllers\Controller;
use App\Http\Resources\IncidentResource;
use App\Models\TransitRoute;
use App\Services\Transit\CurrentCampus;
use App\Services\Transit\LiveNetwork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Detalle de Rutas y Paradas (S2-02) con la línea de tiempo en vivo (S3-03).
 */
class RouteController extends Controller
{
    /**
     * El menú abre la primera ruta activa del campus.
     */
    public function index(Request $request, CurrentCampus $currentCampus): RedirectResponse
    {
        $campus = $currentCampus->resolve($request);
        $route = $campus === null ? null : TransitRoute::query()
            ->active()
            ->forCampus($campus)
            ->orderBy('number')
            ->first();

        if ($route === null) {
            return to_route('dashboard');
        }

        return to_route('routes.show', $route);
    }

    public function show(Request $request, TransitRoute $transitRoute, LiveNetwork $network): Response
    {
        $user = $request->user();

        return Inertia::render('routes/show', [
            ...$network->routeDetail($transitRoute, $user),
            'routes' => TransitRoute::query()
                ->active()
                ->forCampus($transitRoute->campus_id)
                ->orderBy('number')
                ->get(['id', 'number', 'name', 'color']),
            'alarms' => $user->arrivalAlarms()
                ->where('route_id', $transitRoute->id)
                ->get(['id', 'stop_id', 'minutes_before', 'is_active']),
            'incidents' => IncidentResource::collection(
                $transitRoute->incidents()->open()->latest()->get(),
            ),
            'default_alarm_minutes' => $user->alarm_minutes_before,
        ]);
    }
}
