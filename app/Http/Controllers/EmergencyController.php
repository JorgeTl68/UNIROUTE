<?php

namespace App\Http\Controllers;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentType;
use App\Events\IncidentReported;
use App\Http\Requests\StoreEmergencyRequest;
use App\Models\Campus;
use App\Models\Incident;
use App\Notifications\TransitNotification;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Realtime\SafeBroadcaster;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Reportar Emergencia (S4-06), desde el modal de "Soporte en Ruta". Se guarda
 * como incidencia crítica, avisa al instante a administración y aparece en la
 * lista de Incidencias del panel.
 */
class EmergencyController extends Controller
{
    public function store(
        StoreEmergencyRequest $request,
        NotificationDispatcher $dispatcher,
        SafeBroadcaster $broadcaster,
    ): RedirectResponse {
        $driverTrip = $request->user()->currentTrip();
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

        return back();
    }
}
