<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TripStatus;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTripAssignmentRequest;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TripAssignmentController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('create', Trip::class);

        return Inertia::render('admin/fleet/assignments/index', [
            'trips' => Trip::query()->whereIn('status', [TripStatus::Scheduled, TripStatus::InProgress])
                ->with(['driver:id,name', 'vehicle', 'route:id,name,number'])->orderBy('scheduled_at')->orderBy('id')->paginate(10),
        ]);
    }

    public function edit(Trip $trip): Response
    {
        Gate::authorize('create', Trip::class);
        abort_unless(in_array($trip->status, [TripStatus::Scheduled, TripStatus::InProgress], true), 403, 'Este viaje ya no admite cambios.');

        return Inertia::render('admin/fleet/assignments/edit', [
            'trip' => $trip->load(['driver:id,name', 'vehicle', 'route:id,name,number']),
            'drivers' => User::where('role', UserRole::Driver)->whereHas('driverProfile')->orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::where('status', '!=', VehicleStatus::Maintenance)->orderBy('code')->get(['id', 'code', 'capacity']),
            'routes' => TransitRoute::where('is_active', true)->orderBy('number')->get(['id', 'name', 'number']),
            'history' => DB::table('trip_assignments as changes')
                ->leftJoin('users as actor', 'actor.id', '=', 'changes.changed_by')
                ->leftJoin('users as old_driver', 'old_driver.id', '=', 'changes.previous_driver_id')
                ->leftJoin('users as new_driver', 'new_driver.id', '=', 'changes.driver_id')
                ->leftJoin('vehicles as old_vehicle', 'old_vehicle.id', '=', 'changes.previous_vehicle_id')
                ->leftJoin('vehicles as new_vehicle', 'new_vehicle.id', '=', 'changes.vehicle_id')
                ->leftJoin('routes as old_route', 'old_route.id', '=', 'changes.previous_route_id')
                ->leftJoin('routes as new_route', 'new_route.id', '=', 'changes.route_id')
                ->where('changes.trip_id', $trip->id)->orderByDesc('changes.id')->limit(20)
                ->get(['changes.id', 'changes.created_at', 'changes.reason', 'actor.name as actor', 'old_driver.name as previous_driver', 'new_driver.name as driver', 'old_vehicle.code as previous_vehicle', 'new_vehicle.code as vehicle', 'old_route.name as previous_route', 'new_route.name as route'])->map(function ($change) {
                    $change->created_at = CarbonImmutable::parse($change->created_at, (string) config('app.timezone'))->toIso8601String();

                    return $change;
                }),
        ]);
    }

    public function update(UpdateTripAssignmentRequest $request, Trip $trip): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($data, $trip, $request): void {
            $lockedTrip = Trip::whereKey($trip->id)->lockForUpdate()->firstOrFail();
            $driver = User::whereKey($data['driver_id'])->lockForUpdate()->firstOrFail();
            $vehicle = Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
            $route = TransitRoute::whereKey($data['route_id'])->lockForUpdate()->firstOrFail();
            $errors = [];
            if (! in_array($lockedTrip->status, [TripStatus::Scheduled, TripStatus::InProgress], true)) {
                $errors['reason'] = 'Solo puedes asignar viajes programados o en curso.';
            }
            if (! $driver->isDriver() || ! $driver->driverProfile()->exists()) {
                $errors['driver_id'] = 'Selecciona un conductor con perfil registrado.';
            }
            if ($vehicle->status === VehicleStatus::Maintenance) {
                $errors['vehicle_id'] = 'La unidad está en mantenimiento.';
            }
            if ($vehicle->capacity < $lockedTrip->occupancy) {
                $errors['vehicle_id'] = 'La capacidad de la unidad es menor que la ocupación del viaje.';
            }
            if (! $route->is_active && $route->id !== $lockedTrip->route_id) {
                $errors['route_id'] = 'La ruta seleccionada no está activa.';
            }
            if ($lockedTrip->status === TripStatus::InProgress && $route->id !== $lockedTrip->route_id) {
                $errors['route_id'] = 'Un relevo conserva la ruta del viaje en curso.';
            }
            if ($lockedTrip->schedule_id !== null && $route->id !== $lockedTrip->route_id) {
                $errors['route_id'] = 'Este viaje pertenece a un horario. Conserva su ruta para no alterar la programación.';
            }
            foreach (['driver_id' => 'El conductor', 'vehicle_id' => 'La unidad'] as $field => $label) {
                $busy = Trip::where('id', '!=', $lockedTrip->id)->where($field, $data[$field])
                    ->where(fn ($query) => $query->where('status', TripStatus::InProgress)
                        ->orWhere(fn ($query) => $query->where('status', TripStatus::Scheduled)->where('scheduled_at', $lockedTrip->scheduled_at)))->exists();
                if ($busy) {
                    $errors[$field] = $label.' ya tiene un viaje en curso o una salida en el mismo horario.';
                }
            }
            if ($lockedTrip->driver_id === (int) $data['driver_id'] && $lockedTrip->vehicle_id === (int) $data['vehicle_id'] && $lockedTrip->route_id === (int) $data['route_id']) {
                $errors['reason'] = 'Cambia el conductor, la unidad o la ruta antes de guardar.';
            }
            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }
            DB::table('trip_assignments')->insert([
                'trip_id' => $lockedTrip->id, 'changed_by' => $request->user()->id,
                'previous_driver_id' => $lockedTrip->driver_id, 'driver_id' => $driver->id,
                'previous_vehicle_id' => $lockedTrip->vehicle_id, 'vehicle_id' => $vehicle->id,
                'previous_route_id' => $lockedTrip->route_id, 'route_id' => $route->id,
                'reason' => $data['reason'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            $lockedTrip->update(['driver_id' => $driver->id, 'vehicle_id' => $vehicle->id, 'route_id' => $route->id]);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Asignación actualizada. El cambio quedó guardado en el historial.']);

        return to_route('admin.assignments.edit', $trip);
    }
}
