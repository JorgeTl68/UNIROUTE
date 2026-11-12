<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TripStatus;
use App\Http\Controllers\Controller;
use App\Models\TransitRoute;
use App\Models\Trip;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes de aforo (S4-05): ocupación que captura el conductor con − y +,
 * por horario de salida, con exportación a CSV.
 */
class OccupancyReportController extends Controller
{
    /**
     * Desde este porcentaje el viaje cuenta como saturado.
     */
    private const int SATURATED_PERCENT = 85;

    /**
     * Días que se muestran si no se elige un rango.
     */
    private const int DEFAULT_DAYS = 7;

    public function index(Request $request): Response
    {
        [$from, $to, $routeId] = $this->filters($request);
        $rows = $this->rows($from, $to, $routeId);
        $hourly = $rows
            ->groupBy('hour')
            ->map(fn (Collection $trips, string $hour): array => [
                'hour' => $hour,
                'percent' => (int) round($trips->avg('percent')),
                'trips' => $trips->count(),
            ])
            ->sortKeys()
            ->values();

        return Inertia::render('admin/reports/occupancy', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'route' => $routeId,
            ],
            'routes' => TransitRoute::query()->orderBy('number')->get(['id', 'number', 'name']),
            'summary' => [
                'trips' => $rows->count(),
                'average_percent' => $rows->isEmpty() ? null : (int) round($rows->avg('percent')),
                'saturated' => $rows->where('percent', '>=', self::SATURATED_PERCENT)->count(),
                'peak_hour' => $hourly->sortByDesc('percent')->first()['hour'] ?? null,
            ],
            'hourly' => $hourly,
            'rows' => $rows->sortByDesc('scheduled_at')->values()->take(100),
            'saturated_percent' => self::SATURATED_PERCENT,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $routeId] = $this->filters($request);
        $rows = $this->rows($from, $to, $routeId);
        $filename = "aforo-{$from->toDateString()}-{$to->toDateString()}.csv";

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                return;
            }

            // BOM para que Excel abra bien los acentos.
            fwrite($output, "\u{FEFF}");
            fputcsv($output, ['Fecha', 'Salida', 'Ruta', 'Unidad', 'Pasajeros', 'Capacidad', 'Ocupación (%)']);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['date'],
                    $row['hour'],
                    "Ruta {$row['route_number']} · {$row['route_name']}",
                    $row['vehicle_code'],
                    $row['occupancy'],
                    $row['capacity'],
                    $row['percent'],
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: int|null}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'route' => ['nullable', 'integer'],
        ]);
        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to']) : CarbonImmutable::today();
        $from = isset($validated['from'])
            ? CarbonImmutable::parse($validated['from'])
            : $to->subDays(self::DEFAULT_DAYS - 1);

        return [$from->startOfDay(), $to->endOfDay(), isset($validated['route']) ? (int) $validated['route'] : null];
    }

    /**
     * Viajes que ya salieron, con su ocupación en porcentaje.
     *
     * @return Collection<int, array{id: int, scheduled_at: string, date: string, hour: string, route_number: int, route_name: string, vehicle_code: string, occupancy: int, capacity: int, percent: int}>
     */
    private function rows(CarbonImmutable $from, CarbonImmutable $to, ?int $routeId): Collection
    {
        return Trip::query()
            ->whereIn('status', [TripStatus::InProgress, TripStatus::Completed])
            ->whereBetween('scheduled_at', [$from, $to])
            ->whereNotNull('vehicle_id')
            ->when($routeId, fn ($query) => $query->where('route_id', $routeId))
            ->with(['route:id,number,name', 'vehicle:id,code,capacity'])
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn (Trip $trip): array => [
                'id' => $trip->id,
                'scheduled_at' => $trip->scheduled_at->toIso8601String(),
                'date' => $trip->scheduled_at->format('Y-m-d'),
                'hour' => $trip->scheduled_at->format('H:00'),
                'route_number' => $trip->route->number,
                'route_name' => $trip->route->name,
                'vehicle_code' => $trip->vehicle->code,
                'occupancy' => $trip->occupancy,
                'capacity' => $trip->vehicle->capacity,
                'percent' => $trip->vehicle->capacity > 0
                    ? min(100, (int) round($trip->occupancy / $trip->vehicle->capacity * 100))
                    : 0,
            ]);
    }
}
