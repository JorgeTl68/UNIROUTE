<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTransitRouteRequest;
use App\Http\Requests\Admin\TraceTransitRouteRequest;
use App\Models\Campus;
use App\Models\Stop;
use App\Models\TransitRoute;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransitRouteController extends Controller
{
    private const string PREVIEW_SESSION_KEY = 'admin.transit_route_preview';

    public function index(): Response
    {
        $routes = TransitRoute::query()
            ->with(['campus:id,name', 'stops:id'])
            ->withCount('schedules')
            ->orderBy('campus_id')
            ->orderBy('number')
            ->orderBy('id')
            ->get()
            ->map(function (TransitRoute $route): array {
                /** @var Pivot|null $lastStopPivot */
                $lastStopPivot = $route->stops->last()?->getRelation('pivot');
                $duration = $lastStopPivot?->getAttribute('minutes_from_start');

                return [
                    'id' => $route->id,
                    'number' => $route->number,
                    'name' => $route->name,
                    'color' => $route->color,
                    'campus' => $route->campus->name,
                    'is_active' => $route->is_active,
                    'stops_count' => $route->stops->count(),
                    'schedules_count' => $route->getAttribute('schedules_count'),
                    'duration_minutes' => $duration === null ? null : (int) $duration,
                    'frequency_minutes' => $route->frequency_minutes,
                    'service_starts_at' => $route->service_starts_at,
                    'service_ends_at' => $route->service_ends_at,
                ];
            });

        $stops = Stop::query()
            ->with(['routes' => fn ($query) => $query
                ->select(['routes.id', 'routes.number', 'routes.name', 'routes.color'])
                ->orderBy('routes.number')
                ->orderBy('routes.id')])
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name', 'has_shelter'])
            ->map(fn (Stop $stop): array => [
                'id' => $stop->id,
                'name' => $stop->name,
                'has_shelter' => $stop->has_shelter,
                'routes' => $stop->routes->unique()
                    ->map(fn (TransitRoute $route): array => $route->only(['id', 'number', 'name', 'color']))
                    ->values(),
            ]);

        return Inertia::render('admin/routes/index', ['routes' => $routes, 'stops' => $stops]);
    }

    public function create(): Response
    {
        Gate::authorize('create', TransitRoute::class);

        $preview = session(self::PREVIEW_SESSION_KEY);

        return Inertia::render('admin/routes/create', [
            'campuses' => Campus::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'latitude', 'longitude']),
            'stops' => Stop::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'latitude', 'longitude']),
            'preview' => is_array($preview) ? [
                'token' => $preview['token'],
                'encoded_polyline' => $preview['encoded_polyline'],
                'stops' => $preview['stops'],
                'distance_meters' => $preview['distance_meters'],
                'duration_seconds' => $preview['duration_seconds'],
            ] : null,
        ]);
    }

    public function trace(TraceTransitRouteRequest $request): RedirectResponse
    {
        $orderedStops = $this->orderedStops($request->validated('stops'));
        $coordinates = $orderedStops
            ->map(fn (array $stop): string => "{$stop['longitude']},{$stop['latitude']}")
            ->implode(';');

        try {
            $response = Http::acceptJson()->timeout(8)->get(
                rtrim((string) config('services.osrm.url'), '/')."/route/v1/driving/{$coordinates}",
                ['overview' => 'full', 'geometries' => 'polyline'],
            );
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'trace' => 'No fue posible contactar el servicio de trazado. Vuelve a intentarlo.',
            ]);
        }

        $geometry = $response->json('routes.0.geometry');
        $distance = $response->json('routes.0.distance');
        $duration = $response->json('routes.0.duration');
        if ($response->failed()
            || $response->json('code') !== 'Ok'
            || ! is_string($geometry)
            || preg_match('/^[\x3F-\x7E]+$/D', $geometry) !== 1
            || ! is_numeric($distance)
            || ! is_numeric($duration)
            || (float) $distance < 0
            || (float) $duration < 0) {
            throw ValidationException::withMessages([
                'trace' => 'No fue posible calcular un recorrido válido por calles. Revisa las paradas y vuelve a intentarlo.',
            ]);
        }

        $token = (string) Str::uuid();
        $preview = [
            'token' => $token,
            'fingerprint' => $this->fingerprint($orderedStops),
            'encoded_polyline' => $geometry,
            'stops' => $orderedStops->map(fn (array $stop): array => [
                'id' => $stop['id'],
                'minutes_from_start' => $stop['minutes_from_start'],
            ])->all(),
            'distance_meters' => (float) $distance,
            'duration_seconds' => (float) $duration,
        ];
        $request->session()->put(self::PREVIEW_SESSION_KEY, $preview);

        return to_route('admin.routes.create');
    }

    public function store(StoreTransitRouteRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $orderedStops = $this->orderedStops($validated['stops']);
        $preview = $request->session()->get(self::PREVIEW_SESSION_KEY);

        if (! is_array($preview)
            || ! isset($preview['token'], $preview['fingerprint'], $preview['encoded_polyline'])
            || ! hash_equals((string) $preview['token'], $validated['preview_token'])
            || ! hash_equals((string) $preview['fingerprint'], $this->fingerprint($orderedStops))) {
            throw ValidationException::withMessages([
                'preview_token' => 'Las paradas cambiaron desde el último trazado. Vuelve a calcularlo antes de guardar.',
            ]);
        }

        DB::transaction(function () use ($validated, $orderedStops, $preview): void {
            $route = TransitRoute::create([
                'campus_id' => $validated['campus_id'],
                'number' => $validated['number'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'],
                'encoded_polyline' => $preview['encoded_polyline'],
                'frequency_minutes' => $validated['frequency_minutes'] ?? null,
                'service_starts_at' => $validated['service_starts_at'],
                'service_ends_at' => $validated['service_ends_at'],
                'is_active' => true,
            ]);

            $pivot = $orderedStops->mapWithKeys(fn (array $stop, int $index): array => [
                $stop['id'] => [
                    'position' => $index + 1,
                    'minutes_from_start' => $stop['minutes_from_start'],
                ],
            ])->all();
            $route->stops()->attach($pivot);
        });

        $request->session()->forget(self::PREVIEW_SESSION_KEY);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Ruta registrada correctamente.']);

        return to_route('admin.routes.index');
    }

    /**
     * @param  array<int, array{id: int|string, minutes_from_start: int|string}>  $payload
     * @return Collection<int, array{id: int, minutes_from_start: int, latitude: float, longitude: float}>
     */
    private function orderedStops(array $payload): Collection
    {
        $stops = Stop::query()->whereKey(collect($payload)->pluck('id'))->get()->keyBy('id');

        return collect($payload)->map(function (array $item) use ($stops): array {
            $stop = $stops->get((int) $item['id']);

            return [
                'id' => (int) $item['id'],
                'minutes_from_start' => (int) $item['minutes_from_start'],
                'latitude' => (float) $stop->latitude,
                'longitude' => (float) $stop->longitude,
            ];
        });
    }

    /**
     * @param  Collection<int, array{id: int, minutes_from_start: int, latitude: float, longitude: float}>  $stops
     */
    private function fingerprint(Collection $stops): string
    {
        return hash('sha256', json_encode(
            $stops->map(fn (array $stop): array => [
                'id' => $stop['id'],
                'latitude' => $stop['latitude'],
                'longitude' => $stop['longitude'],
            ])->values()->all(),
            JSON_THROW_ON_ERROR,
        ));
    }
}
