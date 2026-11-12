<?php

namespace App\Http\Controllers\Transit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transit\StoreArrivalAlarmRequest;
use App\Models\ArrivalAlarm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * "Avisarme al llegar" (S4-02). Una alarma por ruta y parada; si ya existe,
 * se actualizan los minutos y se vuelve a activar.
 */
class ArrivalAlarmController extends Controller
{
    public function store(StoreArrivalAlarmRequest $request): RedirectResponse
    {
        $request->user()->arrivalAlarms()->updateOrCreate(
            $request->safe()->only(['route_id', 'stop_id']),
            [...$request->safe()->only(['minutes_before']), 'is_active' => true, 'last_trip_id' => null],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Te avisaremos {$request->integer('minutes_before')} minutos antes.",
        ]);

        return back();
    }

    public function destroy(ArrivalAlarm $arrivalAlarm): RedirectResponse
    {
        Gate::authorize('delete', $arrivalAlarm);

        $arrivalAlarm->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Alarma eliminada.']);

        return back();
    }
}
