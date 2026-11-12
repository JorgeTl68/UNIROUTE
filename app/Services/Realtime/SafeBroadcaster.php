<?php

namespace App\Services\Realtime;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;

/**
 * Transmite un evento sin romper la operación si Reverb no está disponible.
 *
 * Guardar una posición, iniciar un viaje o reportar una emergencia debe
 * funcionar aunque el servidor de tiempo real esté caído: el mapa se pone al
 * día con su respaldo de consulta periódica.
 */
class SafeBroadcaster
{
    public function dispatch(object $event): void
    {
        try {
            event($event);
        } catch (BroadcastException $exception) {
            Log::warning('No se pudo transmitir el evento en tiempo real.', [
                'event' => $event::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
