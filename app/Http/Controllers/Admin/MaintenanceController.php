<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompleteMaintenanceRequest;
use App\Http\Requests\Admin\StoreMaintenanceRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Vehicle::class);
        $filters = $request->validate([
            'state' => ['nullable', Rule::in(['open', 'closed'])],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
        ]);

        return Inertia::render('admin/fleet/maintenance/index', [
            'records' => DB::table('vehicle_maintenance_records as records')
                ->join('vehicles', 'vehicles.id', '=', 'records.vehicle_id')
                ->when($filters['state'] ?? null, fn ($query, $state) => $state === 'open' ? $query->whereNull('records.completed_at') : $query->whereNotNull('records.completed_at'))
                ->when($filters['vehicle_id'] ?? null, fn ($query, $id) => $query->where('records.vehicle_id', $id))
                ->orderByDesc('records.id')->select(['records.*', 'vehicles.code', 'vehicles.plate'])->paginate(10)->withQueryString(),
            'filters' => ['state' => $filters['state'] ?? '', 'vehicle_id' => $filters['vehicle_id'] ?? ''],
            'vehicles' => Vehicle::orderBy('code')->get(['id', 'code']),
            'stats' => [
                'in_workshop' => Vehicle::where('status', VehicleStatus::Maintenance)->count(),
                'open' => DB::table('vehicle_maintenance_records')->whereNull('completed_at')->count(),
                'closed' => DB::table('vehicle_maintenance_records')->whereNotNull('completed_at')->count(),
            ],
            'without_report' => Vehicle::where('status', VehicleStatus::Maintenance)
                ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('vehicle_maintenance_records')->whereColumn('vehicle_id', 'vehicles.id')->whereNull('completed_at'))
                ->orderBy('code')->get(['id', 'code']),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Vehicle::class);
        $input = $request->validate(['vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id']]);

        return Inertia::render('admin/fleet/maintenance/create', [
            'vehicles' => Vehicle::query()->orderBy('code')->get(['id', 'code', 'plate', 'status']),
            'selectedVehicle' => $input['vehicle_id'] ?? null,
        ]);
    }

    public function store(StoreMaintenanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $id = DB::transaction(function () use ($request, $data): int {
            $vehicle = Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $vehicle);
            if ($vehicle->trips()->inProgress()->exists()) {
                throw ValidationException::withMessages(['vehicle_id' => 'La unidad tiene un viaje en curso. Reasígnalo a otra unidad antes de enviarla al taller.']);
            }
            if (DB::table('vehicle_maintenance_records')->where('vehicle_id', $vehicle->id)->whereNull('completed_at')->exists()) {
                throw ValidationException::withMessages(['vehicle_id' => 'Esta unidad ya tiene un reporte abierto. Completa el existente antes de abrir otro.']);
            }
            $id = DB::table('vehicle_maintenance_records')->insertGetId([
                'vehicle_id' => $vehicle->id, 'reported_by' => $request->user()->id,
                'severity' => $data['severity'], 'workshop' => $data['workshop'], 'description' => $data['description'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $vehicle->update(['status' => VehicleStatus::Maintenance]);

            return $id;
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Falla registrada. La unidad quedó en mantenimiento.']);

        return to_route('admin.maintenance.show', $id);
    }

    public function show(int $record): Response
    {
        Gate::authorize('viewAny', Vehicle::class);
        $entry = DB::table('vehicle_maintenance_records as records')
            ->join('vehicles', 'vehicles.id', '=', 'records.vehicle_id')
            ->leftJoin('users as reporter', 'reporter.id', '=', 'records.reported_by')
            ->leftJoin('users as closer', 'closer.id', '=', 'records.completed_by')
            ->where('records.id', $record)
            ->select(['records.*', 'vehicles.code', 'vehicles.plate', 'vehicles.model', 'reporter.name as reporter', 'closer.name as closer'])->first();
        abort_if($entry === null, 404);

        return Inertia::render('admin/fleet/maintenance/show', [
            'record' => $entry,
            'scheduledTrips' => Vehicle::query()->whereKey($entry->vehicle_id)->firstOrFail()->trips()->upcoming()->count(),
        ]);
    }

    public function complete(CompleteMaintenanceRequest $request, int $record): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $record, $data): void {
            $entry = DB::table('vehicle_maintenance_records')->where('id', $record)->first();
            abort_if($entry === null, 404);
            $vehicle = Vehicle::whereKey($entry->vehicle_id)->lockForUpdate()->firstOrFail();
            Gate::authorize('update', $vehicle);
            $entry = DB::table('vehicle_maintenance_records')->where('id', $record)->lockForUpdate()->first();
            if ($entry->completed_at !== null) {
                throw ValidationException::withMessages(['resolution' => 'Este mantenimiento ya fue cerrado. Su historial se conserva sin modificaciones.']);
            }
            if ($vehicle->trips()->inProgress()->exists()) {
                throw ValidationException::withMessages(['resolution' => 'La unidad tiene un viaje activo. Revisa su asignación antes de cerrar el mantenimiento.']);
            }
            DB::table('vehicle_maintenance_records')->where('id', $record)->update([
                'resolution' => $data['resolution'], 'cost' => $data['cost'] ?? null,
                'completed_by' => $request->user()->id, 'completed_at' => now(), 'updated_at' => now(),
            ]);
            $vehicle->update(['status' => VehicleStatus::Standby]);
        });
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mantenimiento cerrado. La unidad quedó en espera, disponible para asignación.']);

        return to_route('admin.maintenance.show', $record);
    }
}
