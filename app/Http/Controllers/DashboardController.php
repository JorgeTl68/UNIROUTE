<?php

namespace App\Http\Controllers;

use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Services\Transit\CurrentCampus;
use App\Services\Transit\LiveNetwork;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard en Vivo (S2-01): rutas del campus con sus unidades en viaje y el
 * mapa. La página se actualiza con Echo y, de respaldo, con usePoll.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, CurrentCampus $currentCampus, LiveNetwork $network): Response|RedirectResponse
    {
        // El conductor trabaja en "Modo conductor"; es su pantalla de inicio.
        if ($request->user()->isDriver()) {
            return to_route('driver.trip');
        }

        $campus = $currentCampus->resolve($request);

        return Inertia::render('dashboard', [
            'campus' => $campus?->only(['id', 'name', 'slug', 'latitude', 'longitude']),
            'routes' => fn (): array => $campus === null ? [] : $network->routesFor($campus, $request->user()),
            'incidents' => fn () => $campus === null ? [] : IncidentResource::collection(
                Incident::query()
                    ->open()
                    ->whereHas('route', fn ($query) => $query->where('campus_id', $campus->id))
                    ->with('route')
                    ->latest()
                    ->get(),
            ),
        ]);
    }
}
