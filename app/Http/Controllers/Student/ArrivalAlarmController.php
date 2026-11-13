<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ArrivalAlarm;
use App\Models\TransitRoute;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArrivalAlarmController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $alarms = $user->arrivalAlarms()
            ->with(['route', 'stop'])
            ->whereHas('route', function ($query) use ($user) {
                $query->forCampus($user->campus_id);
            })
            ->latest()
            ->get();

        $routes = collect();

        if ($user->campus_id) {
            $routes = TransitRoute::query()
                ->active()
                ->forCampus($user->campus_id)
                ->with('stops')
                ->orderBy('number')
                ->get();
        }

        return Inertia::render('arrival-alarms/index', [
            'alarms' => $alarms,
            'routes' => $routes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'route_id' => ['required', 'integer', 'exists:routes,id'],
            'stop_id' => ['required', 'integer', 'exists:stops,id'],
            'minutes_before' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $user = $request->user();

        if (!$user->campus_id) {
            return back()->withErrors([
                'route_id' => 'No tienes un campus asignado.',
            ]);
        }

        $route = TransitRoute::query()
            ->active()
            ->forCampus($user->campus_id)
            ->findOrFail($validated['route_id']);

        $stopBelongsToRoute = $route->stops()
            ->where('stops.id', $validated['stop_id'])
            ->exists();

        if (!$stopBelongsToRoute) {
            return back()->withErrors([
                'stop_id' => 'La parada seleccionada no pertenece a esta ruta.',
            ]);
        }

        $user->arrivalAlarms()->updateOrCreate(
            [
                'route_id' => $route->id,
                'stop_id' => $validated['stop_id'],
            ],
            [
                'minutes_before' => $validated['minutes_before'],
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Alarma de llegada guardada.');
    }

    public function update(Request $request, int $alarm)
    {
        $validated = $request->validate([
            'minutes_before' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $user = $request->user();

        $arrivalAlarm = $user->arrivalAlarms()
            ->whereHas('route', function ($query) use ($user) {
                $query->forCampus($user->campus_id);
            })
            ->findOrFail($alarm);

        $arrivalAlarm->update([
            'minutes_before' => $validated['minutes_before'],
        ]);

        return back()->with('success', 'Alarma actualizada.');
    }

    public function toggle(Request $request, int $alarm)
    {
        $user = $request->user();

        $arrivalAlarm = $user->arrivalAlarms()
            ->whereHas('route', function ($query) use ($user) {
                $query->forCampus($user->campus_id);
            })
            ->findOrFail($alarm);

        $arrivalAlarm->update([
            'is_active' => !$arrivalAlarm->is_active,
        ]);

        return back()->with('success', 'Estado de la alarma actualizado.');
    }

    public function destroy(Request $request, int $alarm)
    {
        $user = $request->user();

        $arrivalAlarm = $user->arrivalAlarms()
            ->whereHas('route', function ($query) use ($user) {
                $query->forCampus($user->campus_id);
            })
            ->findOrFail($alarm);

        $arrivalAlarm->delete();

        return back()->with('success', 'Alarma eliminada.');
    }
}
