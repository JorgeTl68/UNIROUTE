<?php

namespace App\Services\Tracking;

/**
 * Codifica y decodifica polilíneas de Google (formato que devuelve OSRM).
 *
 * Las rutas guardan el trazado en routes.encoded_polyline con precisión 5.
 */
final class Polyline
{
    /**
     * Decodifica una polilínea en pares [latitud, longitud].
     *
     * @return list<array{0: float, 1: float}>
     */
    public static function decode(string $encoded, int $precision = 5): array
    {
        $factor = 10 ** $precision;
        $points = [];
        $index = 0;
        $latitude = 0;
        $longitude = 0;
        $length = strlen($encoded);

        while ($index < $length) {
            $latitude += self::decodeValue($encoded, $index);
            $longitude += self::decodeValue($encoded, $index);

            $points[] = [$latitude / $factor, $longitude / $factor];
        }

        return $points;
    }

    /**
     * Codifica pares [latitud, longitud] en una polilínea.
     *
     * @param  list<array{0: float|int, 1: float|int}>  $points
     */
    public static function encode(array $points, int $precision = 5): string
    {
        $factor = 10 ** $precision;
        $encoded = '';
        $previousLatitude = 0;
        $previousLongitude = 0;

        foreach ($points as $point) {
            $latitude = (int) round(((float) $point[0]) * $factor);
            $longitude = (int) round(((float) $point[1]) * $factor);

            $encoded .= self::encodeValue($latitude - $previousLatitude);
            $encoded .= self::encodeValue($longitude - $previousLongitude);

            $previousLatitude = $latitude;
            $previousLongitude = $longitude;
        }

        return $encoded;
    }

    private static function decodeValue(string $encoded, int &$index): int
    {
        $result = 0;
        $shift = 0;
        $length = strlen($encoded);

        do {
            if ($index >= $length) {
                return 0;
            }

            $byte = ord($encoded[$index++]) - 63;
            $result |= ($byte & 0x1F) << $shift;
            $shift += 5;
        } while ($byte >= 0x20);

        return ($result & 1) !== 0 ? ~($result >> 1) : ($result >> 1);
    }

    private static function encodeValue(int $value): string
    {
        $value = $value < 0 ? ~($value << 1) : ($value << 1);
        $encoded = '';

        while ($value >= 0x20) {
            $encoded .= chr((0x20 | ($value & 0x1F)) + 63);
            $value >>= 5;
        }

        return $encoded.chr($value + 63);
    }
}
