<?php

namespace App\Http\Controllers;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Events\IncidentReported;
use App\Http\Requests\StoreEmergencyRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Campus;
use App\Models\Incident;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Notifications\TransitNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Realtime\SafeBroadcaster;
use App\Services\Transit\CurrentCampus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Reportar Emergencia (S4-06): la puede usar cualquier rol. Se guarda como
 * incidencia crítica, avisa al instante a administración y aparece en la
 * lista de Incidencias del panel.
 */
class EmergencyController extends Controller
{
    public function create(Request $request, CurrentCampus $currentCampus): Response
    {
        $campus = $currentCampus->resolve($request);
        $driverTrip = $this->driverTrip($request);

        return Inertia::render('emergency/create', [
            'routes' => $campus === null ? [] : TransitRoute::query()
                ->active()
                ->forCampus($campus)
                ->orderBy('number')
                ->get(['id', 'number', 'name', 'color']),
            'selected_route_id' => $driverTrip->route_id ?? ($request->integer('route') ?: null),
            'categories' => collect(StoreEmergencyRequest::CATEGORIES)
                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values(),
            'reports' => IncidentResource::collection(
                Incident::query()
                    ->where('reported_by', $request->user()->id)
                    ->with('route')
                    ->latest()
                    ->limit(5)
                    ->get(),
            ),
        ]);
    }

    public function store(
        StoreEmergencyRequest $request,
        NotificationDispatcher $dispatcher,
        SafeBroadcaster $broadcaster,
    ): RedirectResponse {
        $driverTrip = $this->driverTrip($request);
        $category = StoreEmergencyRequest::CATEGORIES[$request->validated('category')];
        $incident = Incident::create([
            ...$request->safe()->except('category'),
            'message' => "{$category}: {$request->validated('message')}",
            'route_id' => $request->validated('route_id') ?? $driverTrip?->route_id,
            'vehicle_id' => $driverTrip?->vehicle_id,
            'type' => IncidentType::Emergency,
            'severity' => IncidentSeverity::Critical,
            'reported_by' => $request->user()->id,
        ])->load(['route', 'reporter']);

        $dispatcher->sendToAdmins(new TransitNotification(
            title: 'Emergencia reportada',
            body: $incident->route === null
                ? "{$incident->reporter->name}: {$incident->message}"
                : "Ruta {$incident->route->number} · {$incident->message}",
            type: TransitNotification::INCIDENT,
            routeId: $incident->route_id,
            url: route('admin.incidents.index', absolute: false),
        ));

        $campusId = $incident->route->campus_id
            ?? $request->user()->campus_id
            ?? Campus::query()->value('id');

        if ($campusId !== null) {
            $broadcaster->dispatch(new IncidentReported($incident, $campusId));
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Recibimos tu reporte. El área de transporte ya fue avisada.',
        ]);

        return to_route('dashboard');
    }

    /**
     * El viaje en curso del conductor, para llenar la ruta y la unidad solas.
     */
    private function driverTrip(Request $request): ?Trip
    {
        return $request->user()->isDriver()
            ? $request->user()->drivenTrips()->inProgress()->first()
            : null;
    }
}
