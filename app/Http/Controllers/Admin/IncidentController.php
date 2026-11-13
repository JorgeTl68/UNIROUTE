<?php

namespace App\Http\Controllers\Admin;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Events\IncidentReported;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApplyDetourRequest;
use App\Http\Requests\Admin\StoreIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Models\TransitRoute;
use App\Models\User;
use App\Models\Vehicle;
use App\Notifications\TransitNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Realtime\SafeBroadcaster;
use App\Services\Tracking\RouteDetourManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Incidencias (S4-04): abiertas primero y por severidad; también llegan aquí
 * las emergencias de "Soporte en Ruta" (S4-06).
 */
class IncidentController extends Controller
{
    /**
     * Cuántas incidencias resueltas se muestran.
     */
    private const int RESOLVED_LIMIT = 20;

    public function index(): Response
    {
        Gate::authorize('viewAny', Incident::class);

        $open = Incident::query()
            ->open()
            ->with(['route', 'vehicle', 'reporter', 'detour'])
            ->latest()
            ->get()
            ->sortByDesc(fn (Incident $incident): int => $incident->severity->rank())
            ->values();

        return Inertia::render('admin/incidents/index', [
            'open' => IncidentResource::collection($open),
            'routeStops' => TransitRoute::with('stops')->get()->mapWithKeys(fn (TransitRoute $route) => [$route->id => $route->stops->map(fn ($stop) => ['id' => $stop->id, 'name' => $stop->name])->values()]),
            'resolved' => IncidentResource::collection(
                Incident::query()
                    ->whereNotNull('resolved_at')
                    ->with(['route', 'vehicle', 'reporter'])
                    ->latest('resolved_at')
                    ->limit(self::RESOLVED_LIMIT)
                    ->get(),
            ),
            'routes' => TransitRoute::query()->active()->orderBy('number')->get(['id', 'number', 'name', 'color']),
            'vehicles' => Vehicle::query()->orderBy('code')->get(['id', 'code']),
            'types' => collect(IncidentType::cases())
                ->reject(fn (IncidentType $type): bool => $type === IncidentType::Emergency)
                ->map(fn (IncidentType $type): array => ['value' => $type->value, 'label' => $type->label()])
                ->values(),
            'severities' => collect(IncidentSeverity::cases())
                ->map(fn (IncidentSeverity $severity): array => ['value' => $severity->value, 'label' => $severity->label()]),
        ]);
    }

    public function store(StoreIncidentRequest $request, SafeBroadcaster $broadcaster): RedirectResponse
    {
        $incident = Incident::create([
            ...$request->validated(),
            'reported_by' => $request->user()->id,
        ])->load('route');

        $broadcaster->dispatch(new IncidentReported($incident, $incident->route->campus_id));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Incidencia registrada.']);

        return back();
    }

    /**
     * "Avisar a estudiantes": a quienes tienen la ruta en favoritas o una
     * alarma de llegada en ella.
     */
    public function notify(Incident $incident, NotificationDispatcher $dispatcher): RedirectResponse
    {
        Gate::authorize('update', $incident);

        $route = $incident->route;

        if ($route === null) {
            return back();
        }

        $students = User::query()
            ->whereHas('favoriteRoutes', fn ($query) => $query->whereKey($route->id))
            ->orWhereHas('arrivalAlarms', fn ($query) => $query->where('route_id', $route->id))
            ->get();

        $sent = $dispatcher->sendMany($students, new TransitNotification(
            title: "{$incident->type->label()} en Ruta {$route->number}",
            body: $incident->message,
            type: TransitNotification::INCIDENT,
            routeId: $route->id,
            url: route('routes.show', $route, false),
        ));

        $incident->update(['students_notified_at' => now()]);
        Inertia::flash('toast', ['type' => 'success', 'message' => "Aviso enviado a {$sent} estudiantes."]);

        return back();
    }

    public function resolve(Request $request, Incident $incident, RouteDetourManager $detours, SafeBroadcaster $broadcaster): RedirectResponse
    {
        Gate::authorize('update', $incident);

        $detours->resolve($incident, $request->user()->id);
        $incident->refresh()->load('route');
        if ($incident->route !== null) {
            $broadcaster->dispatch(new IncidentReported($incident, $incident->route->campus_id));
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Incidencia marcada como resuelta.']);

        return back();
    }

    public function detour(ApplyDetourRequest $request, Incident $incident, RouteDetourManager $detours, SafeBroadcaster $broadcaster): RedirectResponse
    {
        $detours->apply($incident, array_values(array_map('intval', $request->validated('skipped_stop_ids'))), $request->user()->id);
        $incident->refresh()->load('route');
        $broadcaster->dispatch(new IncidentReported($incident, $incident->route->campus_id));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Desvío aplicado. Las paradas y el trazado se restaurarán al resolver la incidencia.']);

        return back();
    }
}
