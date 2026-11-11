<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleAmenity;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVehicleRequest;
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

        return Inertia::render('admin/fleet/index', [
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
            'stats' => [
                'total' => Vehicle::count(),
                'in_service' => Vehicle::where('status', VehicleStatus::InService)->count(),
                'standby' => Vehicle::where('status', VehicleStatus::Standby)->count(),
                'maintenance' => Vehicle::where('status', VehicleStatus::Maintenance)->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Vehicle::class);

        return Inertia::render('admin/fleet/create', [
            'statuses' => array_map(fn (VehicleStatus $status) => [
                'value' => $status->value, 'label' => $status->label(),
            ], VehicleStatus::cases()),
            'amenities' => array_map(fn (VehicleAmenity $amenity) => [
                'value' => $amenity->value, 'label' => $amenity->label(),
            ], VehicleAmenity::cases()),
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
