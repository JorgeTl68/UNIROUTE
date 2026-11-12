<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDriverRequest;
use App\Models\DriverProfile;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', DriverProfile::class);
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;

        return Inertia::render('admin/fleet/drivers/index', [
            'drivers' => User::query()->where('role', UserRole::Driver)
                ->select(['id', 'name', 'email', 'campus_id', 'email_verified_at'])
                ->with(['driverProfile', 'campus:id,name', 'drivenTrips' => fn ($query) => $query->inProgress()->with(['vehicle', 'route'])])
                ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhereHas('driverProfile', fn ($profile) => $profile->where('license_number', 'like', '%'.$search.'%'))))
                ->orderBy('name')->orderBy('id')->paginate(10)->withQueryString(),
            'filters' => ['search' => $search],
            'stats' => [
                'total' => User::where('role', UserRole::Driver)->count(),
                'on_trip' => User::where('role', UserRole::Driver)->whereHas('drivenTrips', fn ($query) => $query->inProgress())->count(),
            ],
        ]);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $driver = DB::transaction(function () use ($data): User {
            $driver = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => $data['password'], 'campus_id' => $data['campus_id'] ?? null,
            ]);
            $driver->forceFill(['role' => UserRole::Driver])->save();
            $driver->driverProfile()->create([
                'license_number' => $data['license_number'], 'hired_at' => $data['hired_at'],
            ]);

            return $driver;
        });
        event(new Registered($driver));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Conductor registrado. Debe verificar su correo para ingresar.']);

        return to_route('admin.drivers.index');
    }
}
