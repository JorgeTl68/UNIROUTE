<?php

namespace App\Http\Controllers\Driver;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\UpdateOccupancyRequest;
use App\Models\Trip;
use App\Services\Tracking\EtaCalculator;
use App\Services\Tracking\TripLifecycle;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pantalla del conductor (S3-07): su viaje actual o el siguiente de hoy,
 * iniciar y terminar el viaje y capturar la ocupación con − y +.
 */
class TripController extends Controller
{
    public function show(Request $request, EtaCalculator $etaCalculator): Response
    {
        $trips = Trip::query()
            ->where('driver_id', $request->user()->id)
            ->whereDate('scheduled_at', today())
            ->with(['route.stops', 'vehicle'])
            ->orderBy('scheduled_at')
            ->get();

        $current = $trips->firstWhere('status', TripStatus::InProgress)
            ?? $trips->first(fn (Trip $trip): bool => $trip->status === TripStatus::Scheduled);
        $next = $current?->status === TripStatus::InProgress
            ? ($etaCalculator->forTrip($current)[0] ?? null)
            : null;

        return Inertia::render('driver/trip', [
            'trip' => $current === null ? null : [
                'id' => $current->id,
                'status' => $current->status,
                'scheduled_at' => $current->scheduled_at->toIso8601String(),
                'started_at' => $current->started_at?->toIso8601String(),
                'occupancy' => $current->occupancy,
                'route' => [
                    ...$current->route->only(['id', 'number', 'name', 'color']),
                    'first_stop' => $current->route->stops->first()?->name,
                ],
                'vehicle' => $current->vehicle?->only(['id', 'code', 'plate', 'capacity']),
                'next_stop' => $next === null ? null : [
                    'name' => $next['stop_name'],
                    'eta_minutes' => $next['eta_minutes'],
                    'is_delayed' => $next['delay_minutes'] >= EtaCalculator::DELAY_THRESHOLD_MINUTES,
                ],
            ],
            'trips' => $trips->map(fn (Trip $trip): array => [
                'id' => $trip->id,
                'scheduled_at' => $trip->scheduled_at->toIso8601String(),
                'status' => $trip->status,
                'route_number' => $trip->route->number,
            ]),
        ]);
    }

    public function start(Trip $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        Gate::authorize('update', $trip);

        if ($trip->vehicle_id === null) {
            throw ValidationException::withMessages([
                'trip' => 'El viaje no tiene unidad asignada. Avisa al área de transporte.',
            ]);
        }

        $this->transition(fn () => $lifecycle->start($trip));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Viaje iniciado. Tu ubicación se está enviando.']);

        return back();
    }

    public function finish(Trip $trip, TripLifecycle $lifecycle): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $this->transition(fn () => $lifecycle->finish($trip));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Viaje terminado.']);

        return back();
    }

    public function occupancy(UpdateOccupancyRequest $request, Trip $trip): RedirectResponse
    {
        $trip->update($request->validated());

        return back();
    }

    /**
     * Las reglas de estado viven en TripLifecycle; aquí se vuelven un error de
     * formulario para mostrarlo en pantalla.
     */
    private function transition(callable $change): void
    {
        try {
            $change();
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['trip' => $exception->getMessage()]);
        }
    }
}
