<?php

namespace App\Services\Tracking;

use App\Enums\IncidentType;
use App\Models\Incident;
use App\Models\RouteDetour;
use App\Models\Stop;
use App\Models\TransitRoute;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RouteDetourManager
{
    /** @param list<int> $skipped */
    public function apply(Incident $incident, array $skipped, int $actor): void
    {
        DB::transaction(function () use ($incident, $skipped, $actor): void {
            $locked = Incident::whereKey($incident->id)->lockForUpdate()->firstOrFail();
            if ($locked->resolved_at !== null || $locked->type !== IncidentType::Detour || $locked->route_id === null) {
                throw ValidationException::withMessages(['skipped_stop_ids' => 'Solo se puede desviar una ruta de una incidencia de desvío abierta.']);
            }
            $route = TransitRoute::whereKey($locked->route_id)->lockForUpdate()->with('stops')->firstOrFail();
            if (RouteDetour::where('route_id', $route->id)->whereNull('restored_at')->exists() || RouteDetour::where('incident_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['skipped_stop_ids' => 'La ruta ya tiene un desvío activo. Resuelve la incidencia antes de aplicar otro.']);
            }
            $stops = $route->stops;
            if (array_diff($skipped, $stops->modelKeys()) !== []) {
                throw ValidationException::withMessages(['skipped_stop_ids' => 'Selecciona únicamente paradas de esta ruta.']);
            }
            $remaining = $stops->reject(fn (Stop $stop) => in_array($stop->id, $skipped, true))->values();
            if ($remaining->count() < 2) {
                throw ValidationException::withMessages(['skipped_stop_ids' => 'El desvío debe conservar al menos dos paradas.']);
            }
            $coordinates = $remaining->map(fn (Stop $stop) => $stop->longitude.','.$stop->latitude)->implode(';');
            try {
                $response = Http::acceptJson()->timeout(8)->get(rtrim((string) config('services.osrm.url'), '/').'/route/v1/driving/'.$coordinates, ['overview' => 'full', 'geometries' => 'polyline']);
            } catch (ConnectionException) {
                throw ValidationException::withMessages(['skipped_stop_ids' => 'No se pudo contactar el servicio de rutas. No se aplicó ningún cambio.']);
            }
            $geometry = $response->json('routes.0.geometry');
            $legs = $response->json('routes.0.legs');
            if ($response->failed() || $response->json('code') !== 'Ok' || ! is_string($geometry) || preg_match('/^[\x3F-\x7E]+$/D', $geometry) !== 1 || ! is_array($legs) || count($legs) !== $remaining->count() - 1) {
                throw ValidationException::withMessages(['skipped_stop_ids' => 'No se obtuvo un desvío válido por calles. Inténtalo de nuevo.']);
            }
            foreach ($legs as $leg) {
                if (! is_array($leg) || ! isset($leg['duration']) || ! is_numeric($leg['duration']) || $leg['duration'] < 0) {
                    throw ValidationException::withMessages(['skipped_stop_ids' => 'El servicio devolvió tiempos de recorrido inválidos.']);
                }
            }
            RouteDetour::create([
                'incident_id' => $locked->id, 'route_id' => $route->id, 'created_by' => $actor,
                'skipped_stop_ids' => $skipped,
                'snapshot' => ['encoded_polyline' => $route->encoded_polyline, 'stops' => $stops->map(fn (Stop $stop) => ['id' => $stop->id, 'position' => (int) $stop->pivot->getAttribute('position'), 'minutes_from_start' => (int) $stop->pivot->getAttribute('minutes_from_start')])->all()],
            ]);
            $pivot = [];
            $minutes = 0;
            foreach ($remaining as $index => $stop) {
                if ($index > 0) {
                    $minutes += max(1, (int) ceil($legs[$index - 1]['duration'] / 60));
                }
                if ($minutes > 65535) {
                    throw ValidationException::withMessages(['skipped_stop_ids' => 'El recorrido excede el tiempo máximo permitido.']);
                }
                $pivot[$stop->id] = ['position' => $index + 1, 'minutes_from_start' => $minutes];
            }
            // Reinsert atomically to avoid collisions with the unique route/position index.
            $route->stops()->detach();
            $route->stops()->attach($pivot);
            $route->update(['encoded_polyline' => $geometry]);
        }, 3);
    }

    public function resolve(Incident $incident, int $actor): void
    {
        DB::transaction(function () use ($incident, $actor): void {
            $locked = Incident::whereKey($incident->id)->lockForUpdate()->firstOrFail();
            if ($locked->resolved_at !== null) {
                return;
            }
            $detour = RouteDetour::where('incident_id', $locked->id)->whereNull('restored_at')->first();
            if ($detour !== null) {
                $route = TransitRoute::whereKey($detour->route_id)->lockForUpdate()->firstOrFail();
                $snapshot = $detour->snapshot;
                $pivot = [];
                foreach ($snapshot['stops'] as $stop) {
                    $pivot[$stop['id']] = ['position' => $stop['position'], 'minutes_from_start' => $stop['minutes_from_start']];
                }
                $route->stops()->detach();
                $route->stops()->attach($pivot);
                $route->update(['encoded_polyline' => $snapshot['encoded_polyline']]);
                $detour->update(['restored_at' => now(), 'restored_by' => $actor]);
            }
            $locked->update(['resolved_at' => now()]);
        }, 3);
    }
}
