<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TripStatus;
use App\Enums\VehicleAmenity;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Vehicle::class);

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(VehicleStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $validated['status'] ?? null;
        $search = $validated['search'] ?? null;

        $todayTrips = Trip::query()->onDate(now())->where('status', '!=', TripStatus::Cancelled)->with('vehicle')->get();
        $started = $todayTrips->filter(fn (Trip $trip) => $trip->started_at !== null);
        $punctual = $started->filter(fn (Trip $trip) => abs($trip->started_at->getTimestamp() - $trip->scheduled_at->getTimestamp()) <= 120)->count();
        $measured = $started->filter(fn (Trip $trip) => $trip->vehicle !== null && $trip->vehicle->capacity > 0);
        $capacity = $measured->sum(fn (Trip $trip) => $trip->vehicle->capacity);
        $occupancy = $measured->sum(fn (Trip $trip) => min($trip->occupancy, $trip->vehicle->capacity));
        $incident = Incident::query()->open()->with(['route', 'vehicle'])->latest()->orderByDesc('id')->get()
            ->sortByDesc(fn (Incident $incident) => $incident->severity->rank())->first();

        return Inertia::render('admin/fleet/index', [
            'alert' => $incident === null ? null : IncidentResource::make($incident),
            'operations' => [
                'departures' => Trip::query()->upcoming()->with(['driver', 'route', 'vehicle'])->limit(3)->get(),
                'hourly' => Trip::query()->onDate(now())->get(['scheduled_at', 'occupancy'])
                    ->groupBy(fn (Trip $trip) => $trip->scheduled_at->format('H:00'))
                    ->map(fn ($trips, $hour) => ['hour' => $hour, 'occupancy' => $trips->sum('occupancy')])->values(),
            ],
            'vehicles' => Vehicle::query()
                ->with(['trips' => fn ($query) => $query->inProgress()->with(['route', 'driver'])->orderByDesc('started_at')->orderByDesc('id')])
                ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('code', 'like', '%'.$search.'%')->orWhere('plate', 'like', '%'.$search.'%')->orWhere('model', 'like', '%'.$search.'%')))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderBy('code')->orderBy('id')
                ->paginate(5)->withQueryString(),
            'filters' => ['status' => $status, 'search' => $search],
            'statuses' => array_map(fn (VehicleStatus $state) => [
                'value' => $state->value,
                'label' => $state->label(),
            ], VehicleStatus::cases()),
            // Opciones del modal "Registrar unidad".
            'amenities' => array_map(fn (VehicleAmenity $amenity) => [
                'value' => $amenity->value,
                'label' => $amenity->label(),
            ], VehicleAmenity::cases()),
            'stats' => [
                'trips_today' => $todayTrips->count(),
                'started_trips' => $started->count(),
                'punctuality_percent' => $started->isEmpty() ? null : round($punctual / $started->count() * 100, 1),
                'average_occupancy_percent' => $capacity === 0 ? null : round($occupancy / $capacity * 100, 1),
                'total' => Vehicle::count(),
                'in_service' => Vehicle::where('status', VehicleStatus::InService)->count(),
                'standby' => Vehicle::where('status', VehicleStatus::Standby)->count(),
                'maintenance' => Vehicle::where('status', VehicleStatus::Maintenance)->count(),
            ],
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $vehicle = Vehicle::create($request->validated());
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Unidad {$vehicle->code} registrada correctamente.",
        ]);

        return to_route('admin.fleet.index');
    }
}
