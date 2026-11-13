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
 * Las fotos se leen de los mosaicos de cobertura de Mapillary, que traen cada
 * foto como un punto con su id; la búsqueda por zona de su API regresa vacío.
 * La foto más cercana a cada parada se guarda en caché y el comando
 * uniroute:vista-calle la renueva cada semana. Ver docs/mapas.md.
 */
class StreetView
{
    /**
     * Distancia máxima entre la parada y la foto para mostrarla.
     */
    public const int MAX_DISTANCE_METERS = 100;

    /**
     * Zoom de los mosaicos que traen las fotos como puntos (capa "image").
     */
    private const int ZOOM = 14;

    /**
     * Medio lado del recuadro de búsqueda, en grados (~110 m).
     */
    private const float SEARCH_DEGREES = 0.001;

    private const string TILE_URL = 'https://tiles.mapillary.com/maps/vtp/mly1_public/2/%d/%d/%d';

    /**
     * Días en caché: uno más que la renovación semanal, para no dejar huecos.
     */
    private const int CACHE_DAYS = 8;

    /**
     * Foto más cercana a la parada, o null si no hay ninguna a menos de
     * MAX_DISTANCE_METERS o no está configurado MAPILLARY_TOKEN.
     *
     * @return array{id: string, is_pano: bool, captured_at: string|null, distance_meters: int}|null
     */
    public function nearestTo(Stop $stop): ?array
    {
        $token = $this->token();

        if ($token === null) {
            return null;
        }

        // false: no hay foto cerca y se recuerda. null: Mapillary no respondió;
        // la caché no guarda null, así que se vuelve a intentar la próxima vez.
        $image = Cache::remember(
            $this->cacheKey($stop),
            now()->addDays(self::CACHE_DAYS),
            fn (): array|false|null => $this->lookup($stop, $token),
        );

        return is_array($image) ? $image : null;
    }

    /**
     * Vuelve a buscar la foto de la parada y la guarda. Si Mapillary no
     * responde, se conserva lo que ya había en caché.
     *
     * @return array{id: string, is_pano: bool, captured_at: string|null, distance_meters: int}|null
     */
    public function refresh(Stop $stop): ?array
    {
        $token = $this->token();

        if ($token === null) {
            return null;
        }

        $image = $this->lookup($stop, $token);

        if ($image !== null) {
            Cache::put($this->cacheKey($stop), $image, now()->addDays(self::CACHE_DAYS));
        }

        return is_array($image) ? $image : null;
    }

    public function isConfigured(): bool
    {
        return $this->token() !== null;
    }

    private function token(): ?string
    {
        $token = config('services.mapillary.token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function cacheKey(Stop $stop): string
    {
        return "street-view:stop:{$stop->id}";
    }

    /**
     * @return array{id: string, is_pano: bool, captured_at: string|null, distance_meters: int}|false|null
     */
    private function lookup(Stop $stop, string $token): array|false|null
    {
        $best = null;

        foreach ($this->tilesAround($stop) as [$x, $y]) {
            $images = $this->imagesInTile($x, $y, $token);

            if ($images === null) {
                return null;
            }

            foreach ($images as $image) {
                if (abs($image['latitude'] - $stop->latitude) > self::SEARCH_DEGREES
                    || abs($image['longitude'] - $stop->longitude) > self::SEARCH_DEGREES) {
                    continue;
                }

                $distance = RoutePath::distanceMeters($stop->latitude, $stop->longitude, $image['latitude'], $image['longitude']);
                $id = $image['properties']['id'] ?? null;

                if ((is_int($id) || is_string($id))
                    && $distance <= self::MAX_DISTANCE_METERS
                    && ($best === null || $distance < $best['distance'])) {
                    $best = ['id' => (string) $id, 'distance' => $distance, 'properties' => $image['properties']];
                }
            }
        }

        if ($best === null) {
            return false;
        }

        $capturedAt = $best['properties']['captured_at'] ?? null;

        return [
            'id' => $best['id'],
            'is_pano' => ($best['properties']['is_pano'] ?? false) === true,
            // Mapillary da la fecha en milisegundos.
            'captured_at' => is_int($capturedAt)
                ? CarbonImmutable::createFromTimestampMs($capturedAt)->toIso8601String()
                : null,
            'distance_meters' => (int) round($best['distance']),
        ];
    }

    /**
     * Mosaicos que cubren el recuadro de búsqueda: uno, o hasta cuatro si la
     * parada queda cerca de una orilla.
     *
     * @return list<array{0: int, 1: int}>
     */
    private function tilesAround(Stop $stop): array
    {
        $tiles = [];

        foreach ([-1, 1] as $latitudeSign) {
            foreach ([-1, 1] as $longitudeSign) {
                $tile = $this->tileFor(
                    $stop->latitude + $latitudeSign * self::SEARCH_DEGREES,
                    $stop->longitude + $longitudeSign * self::SEARCH_DEGREES,
                );
                $tiles["{$tile[0]}/{$tile[1]}"] = $tile;
            }
        }

        return array_values($tiles);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function tileFor(float $latitude, float $longitude): array
    {
        $count = 2 ** self::ZOOM;
        $radians = deg2rad($latitude);

        return [
            (int) floor(($longitude + 180) / 360 * $count),
            (int) floor((1 - log(tan($radians) + 1 / cos($radians)) / M_PI) / 2 * $count),
        ];
    }

    /**
     * Fotos de un mosaico con su posición, o null si la consulta falla.
     *
     * @return list<array{latitude: float, longitude: float, properties: array<string, string|int|float|bool>}>|null
     */
    private function imagesInTile(int $x, int $y, string $token): ?array
    {
        try {
            $response = Http::timeout(15)->get(sprintf(self::TILE_URL, self::ZOOM, $x, $y), ['access_token' => $token]);
        } catch (ConnectionException) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $layer = VectorTile::layerPoints($response->body(), 'image');

        if ($layer === null) {
            return [];
        }

        $count = 2 ** self::ZOOM;

        return array_map(fn (array $point): array => [
            'latitude' => rad2deg(atan(sinh(M_PI * (1 - 2 * ($y + $point['y'] / $layer['extent']) / $count)))),
            'longitude' => ($x + $point['x'] / $layer['extent']) / $count * 360 - 180,
            'properties' => $point['properties'],
        ], $layer['points']);
    }
}
