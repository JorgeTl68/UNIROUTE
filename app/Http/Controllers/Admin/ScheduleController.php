<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateSchedulesRequest;
use App\Http\Requests\Admin\SaveScheduleRequest;
use App\Models\Schedule;
use App\Models\TransitRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(TransitRoute $transitRoute): Response
    {
        Gate::authorize('viewAny', Schedule::class);
        $transitRoute->load('campus:id,name');

        return Inertia::render('admin/schedules/index', [
            'route' => [
                'id' => $transitRoute->id,
                'number' => $transitRoute->number,
                'name' => $transitRoute->name,
                'color' => $transitRoute->color,
                'campus' => $transitRoute->campus->name,
                'frequency_minutes' => $transitRoute->frequency_minutes,
                'service_starts_at' => substr($transitRoute->service_starts_at, 0, 5),
                'service_ends_at' => substr($transitRoute->service_ends_at, 0, 5),
            ],
            'schedules' => $transitRoute->schedules()
                ->orderBy('weekday')
                ->orderBy('departs_at')
                ->orderBy('id')
                ->get(['id', 'weekday', 'departs_at'])
                ->map(fn (Schedule $schedule): array => [
                    'id' => $schedule->id,
                    'weekday' => $schedule->weekday,
                    'departs_at' => substr($schedule->departs_at, 0, 5),
                ]),
        ]);
    }

    public function store(SaveScheduleRequest $request, TransitRoute $transitRoute): RedirectResponse
    {
        $transitRoute->schedules()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario agregado correctamente.']);

        return to_route('admin.routes.schedules.index', $transitRoute);
    }

    public function update(
        SaveScheduleRequest $request,
        TransitRoute $transitRoute,
        Schedule $schedule,
    ): RedirectResponse {
        abort_unless($schedule->route_id === $transitRoute->id, 404);
        $schedule->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Horario actualizado correctamente.']);

        return to_route('admin.routes.schedules.index', $transitRoute);
    }

    public function generate(
        GenerateSchedulesRequest $request,
        TransitRoute $transitRoute,
    ): RedirectResponse {
        $data = $request->validated();
        [$startHour, $startMinute] = array_map('intval', explode(':', $data['starts_at']));
        [$endHour, $endMinute] = array_map('intval', explode(':', $data['ends_at']));
        $start = ($startHour * 60) + $startMinute;
        $end = ($endHour * 60) + $endMinute;
        $times = [];

        for ($minutes = $start; $minutes <= $end; $minutes += $data['interval_minutes']) {
            $times[] = sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
        }

        $now = now();
        $rows = [];
        foreach ($data['weekdays'] as $weekday) {
            foreach ($times as $time) {
                $rows[] = [
                    'route_id' => $transitRoute->id,
                    'weekday' => $weekday,
                    'departs_at' => $time,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $created = DB::transaction(function () use ($rows): int {
            $created = 0;
            foreach (array_chunk($rows, 500) as $chunk) {
                $created += Schedule::query()->insertOrIgnore($chunk);
            }

            return $created;
        });
        $existing = count($rows) - $created;
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Generación terminada. Se crearon {$created} y ya existían {$existing}.",
        ]);

        return to_route('admin.routes.schedules.index', $transitRoute);
    }
}
