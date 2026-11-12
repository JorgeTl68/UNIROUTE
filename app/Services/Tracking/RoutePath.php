<?php

namespace App\Services\Tracking;

use App\Models\Stop;
use App\Models\TransitRoute;

/**
 * Trazado recorrible de una ruta: la encoded_polyline o, si falta, la unión
 * recta de sus paradas.
 *
 * Permite calcular distancias y la posición de una unidad a cierta distancia
 * del inicio. Lo usan el simulador (S1-09) y el cálculo de ETA (S2-07).
 */
final class RoutePath
{
    /**
     * @param  list<array{0: float, 1: float}>  $points
     * @param  list<float>  $cumulativeMeters
     */
    private function __construct(
        private readonly array $points,
        private readonly array $cumulativeMeters,
        private readonly float $lengthMeters,
    ) {}

    /**
     * Prefiere la polilínea de OSRM y cae a las paradas mientras no exista.
     *
     * TODO(S0-05/S0-08): las rutas aún no tienen encoded_polyline real; el
     * respaldo con paradas es provisional.
     */
    public static function fromRoute(TransitRoute $route): ?self
    {
        if (is_string($route->encoded_polyline) && $route->encoded_polyline !== '') {
            $path = self::fromPoints(Polyline::decode($route->encoded_polyline));

            if ($path !== null) {
                return $path;
            }
        }

        $stops = $route->relationLoaded('stops') ? $route->stops : $route->stops()->get();

        return self::fromPoints(
            $stops->map(fn (Stop $stop): array => [$stop->latitude, $stop->longitude])->all(),
        );
    }

    /**
     * @param  array<int, array{0: float|int, 1: float|int}>  $points
     */
    public static function fromPoints(array $points): ?self
    {
        $clean = [];

        foreach ($points as $point) {
            $latitude = (float) $point[0];
            $longitude = (float) $point[1];

            if (! is_finite($latitude) || ! is_finite($longitude)) {
                continue;
            }

            if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
                continue;
            }

            $last = $clean === [] ? null : $clean[count($clean) - 1];

            if ($last !== null && abs($last[0] - $latitude) < 1e-9 && abs($last[1] - $longitude) < 1e-9) {
                continue;
            }

            $clean[] = [$latitude, $longitude];
        }

        if (count($clean) < 2) {
            return null;
        }

        $cumulative = [0.0];
        $total = 0.0;

        for ($i = 1; $i < count($clean); $i++) {
            $total += self::distanceMeters(
                $clean[$i - 1][0],
                $clean[$i - 1][1],
                $clean[$i][0],
                $clean[$i][1],
            );
            $cumulative[] = $total;
        }

        if ($total <= 0.0) {
            return null;
        }

        return new self($clean, $cumulative, $total);
    }

    public function lengthMeters(): float
    {
        return $this->lengthMeters;
    }

    /**
     * Posición y rumbo a una distancia del inicio; la distancia se recorta al
     * recorrido disponible.
     *
     * @return array{latitude: float, longitude: float, heading: int}
     */
    public function positionAt(float $distance): array
    {
        $distance = max(0.0, min($distance, $this->lengthMeters));
        $segment = $this->segmentAt($distance);

        $start = $this->points[$segment];
        $end = $this->points[$segment + 1];
        $segmentStart = $this->cumulativeMeters[$segment];
        $segmentLength = $this->cumulativeMeters[$segment + 1] - $segmentStart;
        $ratio = $segmentLength > 0.0 ? ($distance - $segmentStart) / $segmentLength : 0.0;

        $latitude = $start[0] + ($end[0] - $start[0]) * $ratio;
        $longitude = $start[1] + ($end[1] - $start[1]) * $ratio;
        $heading = (int) round(self::bearingDegrees($start[0], $start[1], $end[0], $end[1]));

        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'heading' => (($heading % 360) + 360) % 360,
        ];
    }

    /**
     * Distancia recorrida (en metros) hasta el punto del trazado más cercano a
     * una coordenada. Sirve para ubicar paradas o unidades sobre la polilínea.
     */
    public function distanceAlongFor(float $latitude, float $longitude): float
    {
        $bestAlong = 0.0;
        $bestDistance = INF;
        $lastSegment = count($this->points) - 2;

        for ($i = 0; $i <= $lastSegment; $i++) {
            [$along, $distance] = $this->projectOnSegment($i, $latitude, $longitude);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestAlong = $along;
            }
        }

        return $bestAlong;
    }

    public static function distanceMeters(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB,
    ): float {
        $earthRadius = 6371000.0;
        $deltaLatitude = deg2rad($latitudeB - $latitudeA);
        $deltaLongitude = deg2rad($longitudeB - $longitudeA);

        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($deltaLongitude / 2) ** 2;

        return 2 * $earthRadius * asin(min(1.0, sqrt($a)));
    }

    public static function bearingDegrees(
        float $latitudeA,
        float $longitudeA,
        float $latitudeB,
        float $longitudeB,
    ): float {
        $deltaLongitude = deg2rad($longitudeB - $longitudeA);
        $y = sin($deltaLongitude) * cos(deg2rad($latitudeB));
        $x = cos(deg2rad($latitudeA)) * sin(deg2rad($latitudeB))
            - sin(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * cos($deltaLongitude);

        return fmod(rad2deg(atan2($y, $x)) + 360.0, 360.0);
    }

    /**
     * @return array{0: float, 1: float} Distancia recorrida y distancia a la coordenada.
     */
    private function projectOnSegment(int $index, float $latitude, float $longitude): array
    {
        $start = $this->points[$index];
        $end = $this->points[$index + 1];
        $earthRadius = 6371000.0;
        $cosLatitude = cos(deg2rad($latitude));

        $segmentX = deg2rad($end[1] - $start[1]) * $cosLatitude * $earthRadius;
        $segmentY = deg2rad($end[0] - $start[0]) * $earthRadius;
        $pointX = deg2rad($longitude - $start[1]) * $cosLatitude * $earthRadius;
        $pointY = deg2rad($latitude - $start[0]) * $earthRadius;

        $lengthSquared = $segmentX * $segmentX + $segmentY * $segmentY;
        $ratio = $lengthSquared > 0.0
            ? max(0.0, min(1.0, ($pointX * $segmentX + $pointY * $segmentY) / $lengthSquared))
            : 0.0;

        $projectedLatitude = $start[0] + ($end[0] - $start[0]) * $ratio;
        $projectedLongitude = $start[1] + ($end[1] - $start[1]) * $ratio;
        $distance = self::distanceMeters($latitude, $longitude, $projectedLatitude, $projectedLongitude);
        $segmentLength = $this->cumulativeMeters[$index + 1] - $this->cumulativeMeters[$index];

        return [$this->cumulativeMeters[$index] + $ratio * $segmentLength, $distance];
    }

    private function segmentAt(float $distance): int
    {
        $lastSegment = count($this->points) - 2;

        for ($i = 0; $i <= $lastSegment; $i++) {
            if ($distance <= $this->cumulativeMeters[$i + 1]) {
                return $i;
            }
        }

        return $lastSegment;
    }
}
