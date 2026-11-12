<?php

namespace App\Services\Notifications;

use App\Models\ArrivalAlarm;
use App\Models\Trip;
use App\Notifications\TransitNotification;
use App\Services\Tracking\EtaCalculator;

/**
 * Revisa un viaje en curso y manda los avisos que correspondan (S4-01):
 *
 * - "Avisarme al llegar": cuando la unidad está a los minutos que pidió el
 *   estudiante de su parada. Una vez por viaje.
 * - Retraso: cuando la siguiente parada va con el retraso de
 *   EtaCalculator::DELAY_THRESHOLD_MINUTES o más, a quienes tienen la ruta en
 *   favoritas. Una vez por viaje.
 */
class TripAlerts
{
    public function __construct(
        private readonly EtaCalculator $etaCalculator,
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function check(Trip $trip): void
    {
        $alarms = ArrivalAlarm::query()
            ->where('route_id', $trip->route_id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('last_trip_id')->orWhere('last_trip_id', '!=', $trip->id))
            ->with('user')
            ->get();
        $checkDelay = $trip->delay_notified_at === null;

        if ($alarms->isEmpty() && ! $checkDelay) {
            return;
        }

        $etas = collect($this->etaCalculator->forTrip($trip))->keyBy('stop_id');

        if ($etas->isEmpty()) {
            return;
        }

        $route = $trip->route;
        $routeUrl = route('routes.show', $route, false);

        foreach ($alarms as $alarm) {
            $eta = $etas->get($alarm->stop_id);

            if ($eta === null || $eta['eta_minutes'] > $alarm->minutes_before) {
                continue;
            }

            $alarm->update(['last_trip_id' => $trip->id]);
            $this->dispatcher->send($alarm->user, new TransitNotification(
                title: $eta['eta_minutes'] === 0
                    ? 'Tu unidad está llegando'
                    : "Tu unidad llega en {$eta['eta_minutes']} min",
                body: "Ruta {$route->number} · Parada {$eta['stop_name']}",
                type: TransitNotification::ARRIVAL,
                routeId: $route->id,
                stopId: $alarm->stop_id,
                tripId: $trip->id,
                url: $routeUrl,
            ));
        }

        $delay = $etas->first()['delay_minutes'];

        if ($checkDelay && $delay >= EtaCalculator::DELAY_THRESHOLD_MINUTES) {
            $trip->update(['delay_notified_at' => now()]);
            $this->dispatcher->sendMany($route->favoritedBy()->get(), new TransitNotification(
                title: "Retraso de {$delay} min en Ruta {$route->number}",
                body: "{$route->name}: la unidad va más lenta de lo programado.",
                type: TransitNotification::DELAY,
                routeId: $route->id,
                tripId: $trip->id,
                url: $routeUrl,
            ));
        }
    }
}
