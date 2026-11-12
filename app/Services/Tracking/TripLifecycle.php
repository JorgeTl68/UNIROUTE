<?php

namespace App\Services\Tracking;

use App\Enums\TripStatus;
use App\Events\TripStatusChanged;
use App\Models\Trip;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;

/**
 * Cambios de estado de un viaje: iniciar y terminar.
 *
 * Las pantallas del conductor y el simulador deben pasar por aquí para que
 * las reglas de estado queden en un solo lugar.
 */
class TripLifecycle
{
    public function start(Trip $trip, ?CarbonInterface $at = null): Trip
    {
        if ($trip->status !== TripStatus::Scheduled) {
            throw new DomainException('Solo se puede iniciar un viaje programado.');
        }

        $trip->update([
            'status' => TripStatus::InProgress,
            'started_at' => $at ?? now(),
            'ended_at' => null,
        ]);

        $trip = $trip->refresh();
        $this->broadcastStatus($trip, $trip->started_at ?? $at);

        return $trip;
    }

    public function finish(Trip $trip, ?CarbonInterface $at = null): Trip
    {
        if ($trip->status !== TripStatus::InProgress) {
            throw new DomainException('Solo se puede terminar un viaje en curso.');
        }

        $trip->update([
            'status' => TripStatus::Completed,
            'ended_at' => $at ?? now(),
        ]);

        $trip = $trip->refresh();
        $this->broadcastStatus($trip, $trip->ended_at ?? $at);

        return $trip;
    }

    private function broadcastStatus(Trip $trip, ?CarbonInterface $changedAt): void
    {
        try {
            event(TripStatusChanged::fromTrip($trip, $changedAt));
        } catch (BroadcastException $exception) {
            Log::warning('No se pudo transmitir el cambio de estado del viaje.', [
                'trip_id' => $trip->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
