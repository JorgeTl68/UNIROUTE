<?php

namespace App\Services\Transit;

use App\Models\Stop;
use App\Services\Tracking\RoutePath;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Vista de calle de las paradas con fotos de Mapillary (abiertas y gratis).
 *
 * Busca la foto más cercana a la parada y la guarda una semana en caché, así
 * cada parada consulta a Mapillary pocas veces. Ver docs/mapas.md.
 */
class StreetView
{
    /**
     * Distancia máxima entre la parada y la foto para mostrarla.
     */
    public const int MAX_DISTANCE_METERS = 100;

    /**
     * Medio lado del recuadro de búsqueda, en grados (~110 m).
     */
    private const float SEARCH_DEGREES = 0.001;

    private const string API_URL = 'https://graph.mapillary.com/images';

    /**
     * Foto más cercana a la parada, o null si no hay ninguna a menos de
     * MAX_DISTANCE_METERS o no está configurado MAPILLARY_TOKEN.
     *
     * @return array{id: string, is_pano: bool, captured_at: string|null, distance_meters: int}|null
     */
    public function nearestTo(Stop $stop): ?array
    {
        $token = config('services.mapillary.token');

        if (! is_string($token) || $token === '') {
            return null;
        }

        // false: no hay foto cerca y se recuerda. null: Mapillary no respondió;
        // la caché no guarda null, así que se vuelve a intentar la próxima vez.
        $image = Cache::remember(
            "street-view:stop:{$stop->id}",
            now()->addWeek(),
            function () use ($stop, $token): array|false|null {
                $images = $this->imagesAround($stop, $token);

                return $images === null ? null : ($this->nearest($stop, $images) ?? false);
            },
        );

        return is_array($image) ? $image : null;
    }

    /**
     * Fotos dentro del recuadro de la parada, o null si la consulta falla.
     *
     * @return list<array<string, mixed>>|null
     */
    private function imagesAround(Stop $stop, string $token): ?array
    {
        $bbox = implode(',', [
            $stop->longitude - self::SEARCH_DEGREES,
            $stop->latitude - self::SEARCH_DEGREES,
            $stop->longitude + self::SEARCH_DEGREES,
            $stop->latitude + self::SEARCH_DEGREES,
        ]);

        try {
            $response = Http::acceptJson()
                ->withHeaders(['Authorization' => "OAuth {$token}"])
                ->timeout(6)
                ->get(self::API_URL, [
                    'fields' => 'id,computed_geometry,geometry,is_pano,captured_at',
                    'bbox' => $bbox,
                    'limit' => 100,
                ]);
        } catch (ConnectionException) {
            return null;
        }

        $data = $response->json('data');

        if ($response->failed() || ! is_array($data)) {
            return null;
        }

        return array_values(array_filter($data, is_array(...)));
    }

    /**
     * @param  list<array<string, mixed>>  $images
     * @return array{id: string, is_pano: bool, captured_at: string|null, distance_meters: int}|null
     */
    private function nearest(Stop $stop, array $images): ?array
    {
        $best = null;

        foreach ($images as $image) {
            $point = $this->coordinates($image);
            $id = $image['id'] ?? null;

            if ($point === null || ! is_string($id)) {
                continue;
            }

            $distance = RoutePath::distanceMeters($stop->latitude, $stop->longitude, $point[1], $point[0]);

            if ($distance <= self::MAX_DISTANCE_METERS && ($best === null || $distance < $best['distance'])) {
                $best = ['image' => $image, 'id' => $id, 'distance' => $distance];
            }
        }

        if ($best === null) {
            return null;
        }

        $capturedAt = $best['image']['captured_at'] ?? null;

        return [
            'id' => $best['id'],
            'is_pano' => ($best['image']['is_pano'] ?? false) === true,
            // Mapillary da la fecha en milisegundos.
            'captured_at' => is_int($capturedAt)
                ? CarbonImmutable::createFromTimestampMs($capturedAt)->toIso8601String()
                : null,
            'distance_meters' => (int) round($best['distance']),
        ];
    }

    /**
     * [longitud, latitud] de la foto; prefiere la posición corregida.
     *
     * @param  array<string, mixed>  $image
     * @return array{0: float, 1: float}|null
     */
    private function coordinates(array $image): ?array
    {
        foreach (['computed_geometry', 'geometry'] as $field) {
            $coordinates = $image[$field]['coordinates'] ?? null;

            if (is_array($coordinates) && is_numeric($coordinates[0] ?? null) && is_numeric($coordinates[1] ?? null)) {
                return [(float) $coordinates[0], (float) $coordinates[1]];
            }
        }

        return null;
    }
}
