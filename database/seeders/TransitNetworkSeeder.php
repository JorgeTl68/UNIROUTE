<?php

namespace Database\Seeders;

use App\Models\Campus;
use App\Models\Schedule;
use App\Models\Stop;
use App\Models\TransitRoute;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Rutas, paradas y horarios de salida.
 *
 * PROVISIONAL: nombres tomados de los mockups y coordenadas aproximadas.
 * Se reemplazan con los datos reales que levanta Enrique en S0-05.
 */
class TransitNetworkSeeder extends Seeder
{
    /**
     * @var array<string, array{latitude: float, longitude: float, has_shelter: bool}>
     */
    private const array STOPS = [
        'Campus Central - Puerta Principal' => ['latitude' => 25.4262, 'longitude' => -100.9925, 'has_shelter' => true],
        'Blanca Esthela' => ['latitude' => 25.4335, 'longitude' => -100.9810, 'has_shelter' => false],
        'Manantiales del Valle' => ['latitude' => 25.4180, 'longitude' => -100.9700, 'has_shelter' => true],
        'Fraccionamiento Arborea' => ['latitude' => 25.4075, 'longitude' => -100.9620, 'has_shelter' => false],
        'Santa Fe' => ['latitude' => 25.3990, 'longitude' => -100.9550, 'has_shelter' => true],
        'El Cortijo de Abajo - Terminal Sur' => ['latitude' => 25.3900, 'longitude' => -100.9480, 'has_shelter' => true],
        'Plaza de Armas' => ['latitude' => 25.4232, 'longitude' => -101.0005, 'has_shelter' => true],
        'Saltillo 2000' => ['latitude' => 25.4440, 'longitude' => -101.0180, 'has_shelter' => false],
        'Zona Industrial Ramos Arizpe' => ['latitude' => 25.5250, 'longitude' => -100.9600, 'has_shelter' => false],
        'Campus Ramos Arizpe - Acceso' => ['latitude' => 25.5400, 'longitude' => -100.9470, 'has_shelter' => true],
    ];

    /**
     * Cada parada de una ruta: [nombre, minutos desde el inicio].
     *
     * @var list<array{campus: string, number: int, name: string, description: string, color: string, frequency_minutes: int, service_starts_at: string, service_ends_at: string, stops: list<array{0: string, 1: int}>}>
     */
    private const array ROUTES = [
        [
            'campus' => CampusSeeder::CENTRAL,
            'number' => 1,
            'name' => 'Campus Central – Santa Fe',
            'description' => 'Vía Manantiales del Valle hasta la Terminal Sur.',
            'color' => '#91db2a',
            'frequency_minutes' => 12,
            'service_starts_at' => '06:00:00',
            'service_ends_at' => '22:00:00',
            'stops' => [
                ['Campus Central - Puerta Principal', 0],
                ['Blanca Esthela', 7],
                ['Manantiales del Valle', 13],
                ['Fraccionamiento Arborea', 18],
                ['Santa Fe', 22],
                ['El Cortijo de Abajo - Terminal Sur', 30],
            ],
        ],
        [
            'campus' => CampusSeeder::RAMOS_ARIZPE,
            'number' => 2,
            'name' => 'Ramos Arizpe – Campus Central',
            'description' => 'Conexión entre la Zona Industrial y el centro de Saltillo.',
            'color' => '#e0c297',
            'frequency_minutes' => 20,
            'service_starts_at' => '06:30:00',
            'service_ends_at' => '21:30:00',
            'stops' => [
                ['Campus Ramos Arizpe - Acceso', 0],
                ['Zona Industrial Ramos Arizpe', 10],
                ['Plaza de Armas', 25],
                ['Campus Central - Puerta Principal', 35],
            ],
        ],
        [
            'campus' => CampusSeeder::CENTRAL,
            'number' => 3,
            'name' => 'Fracc. Arborea – UTC Central',
            'description' => 'Línea directa de estudiantes.',
            'color' => '#7e8ba6',
            'frequency_minutes' => 15,
            'service_starts_at' => '06:00:00',
            'service_ends_at' => '22:00:00',
            'stops' => [
                ['Fraccionamiento Arborea', 0],
                ['Manantiales del Valle', 6],
                ['Campus Central - Puerta Principal', 14],
            ],
        ],
        [
            'campus' => CampusSeeder::CENTRAL,
            'number' => 4,
            'name' => 'Saltillo 2000 – Campus Central',
            'description' => 'Ruta vespertina especial.',
            'color' => '#715b37',
            'frequency_minutes' => 30,
            'service_starts_at' => '15:00:00',
            'service_ends_at' => '21:00:00',
            'stops' => [
                ['Saltillo 2000', 0],
                ['Plaza de Armas', 12],
                ['Campus Central - Puerta Principal', 20],
            ],
        ],
    ];

    /**
     * Días con servicio: lunes (1) a viernes (5).
     *
     * @var list<int>
     */
    private const array SERVICE_WEEKDAYS = [1, 2, 3, 4, 5];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stopIds = [];

        foreach (self::STOPS as $name => $attributes) {
            $stopIds[$name] = Stop::updateOrCreate(['name' => $name], $attributes)->id;
        }

        $campusIds = Campus::pluck('id', 'slug');

        foreach (self::ROUTES as $data) {
            $route = TransitRoute::updateOrCreate(
                ['campus_id' => $campusIds[$data['campus']], 'number' => $data['number']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'color' => $data['color'],
                    'frequency_minutes' => $data['frequency_minutes'],
                    'service_starts_at' => $data['service_starts_at'],
                    'service_ends_at' => $data['service_ends_at'],
                    'is_active' => true,
                ],
            );

            $pivot = [];
            foreach ($data['stops'] as $position => [$stopName, $minutes]) {
                $pivot[$stopIds[$stopName]] = ['position' => $position + 1, 'minutes_from_start' => $minutes];
            }
            $route->stops()->sync($pivot);

            $this->seedSchedules($route);
        }
    }

    /**
     * Una salida cada frequency_minutes entre el inicio y el fin del servicio.
     */
    private function seedSchedules(TransitRoute $route): void
    {
        $route->schedules()->delete();

        $start = CarbonImmutable::createFromFormat('H:i:s', $route->service_starts_at);
        $end = CarbonImmutable::createFromFormat('H:i:s', $route->service_ends_at);
        $now = now();
        $rows = [];

        foreach (self::SERVICE_WEEKDAYS as $weekday) {
            for ($time = $start; $time->lessThanOrEqualTo($end); $time = $time->addMinutes($route->frequency_minutes)) {
                $rows[] = [
                    'route_id' => $route->id,
                    'weekday' => $weekday,
                    'departs_at' => $time->format('H:i:s'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Schedule::insert($chunk);
        }
    }
}
