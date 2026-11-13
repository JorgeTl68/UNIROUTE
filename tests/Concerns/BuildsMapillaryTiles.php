<?php

namespace Tests\Concerns;

/**
 * Arma mosaicos vectoriales como los de Mapillary (capa "image", zoom 14) con
 * fotos en coordenadas conocidas, para probar la vista de calle sin red.
 */
trait BuildsMapillaryTiles
{
    private const int TILE_ZOOM = 14;

    private const int TILE_EXTENT = 4096;

    /**
     * @param  list<array{latitude: float, longitude: float, id: int, is_pano?: bool, captured_at?: int}>  $images
     */
    protected function mapillaryTile(float $latitude, float $longitude, array $images): string
    {
        [$tileX, $tileY] = $this->tileOf($latitude, $longitude);
        $keys = ['id', 'is_pano', 'captured_at'];
        $values = [];
        $features = '';

        foreach ($images as $image) {
            [$x, $y] = $this->pixelOf($image['latitude'], $image['longitude'], $tileX, $tileY);
            $tags = [];

            foreach ([
                0 => $this->field(4, 0).$this->varint($image['id']),
                1 => $this->field(7, 0).$this->varint(($image['is_pano'] ?? false) ? 1 : 0),
                2 => $this->field(4, 0).$this->varint($image['captured_at'] ?? 1_600_000_000_000),
            ] as $key => $value) {
                $values[] = $value;
                array_push($tags, $key, count($values) - 1);
            }

            $geometry = $this->varint(9).$this->varint($this->zigzag($x)).$this->varint($this->zigzag($y));
            $features .= $this->bytes(2,
                $this->bytes(2, implode('', array_map($this->varint(...), $tags)))
                .$this->field(3, 0).$this->varint(1)
                .$this->bytes(4, $geometry),
            );
        }

        $layer = $this->bytes(1, 'image')
            .$features
            .implode('', array_map(fn (string $key): string => $this->bytes(3, $key), $keys))
            .implode('', array_map(fn (string $value): string => $this->bytes(4, $value), $values))
            .$this->field(5, 0).$this->varint(self::TILE_EXTENT)
            .$this->field(15, 0).$this->varint(2);

        return $this->bytes(3, $layer);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function tileOf(float $latitude, float $longitude): array
    {
        [$x, $y] = $this->worldOf($latitude, $longitude);

        return [(int) floor($x), (int) floor($y)];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function pixelOf(float $latitude, float $longitude, int $tileX, int $tileY): array
    {
        [$x, $y] = $this->worldOf($latitude, $longitude);

        return [(int) round(($x - $tileX) * self::TILE_EXTENT), (int) round(($y - $tileY) * self::TILE_EXTENT)];
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function worldOf(float $latitude, float $longitude): array
    {
        $count = 2 ** self::TILE_ZOOM;
        $radians = deg2rad($latitude);

        return [
            ($longitude + 180) / 360 * $count,
            (1 - log(tan($radians) + 1 / cos($radians)) / M_PI) / 2 * $count,
        ];
    }

    private function varint(int $value): string
    {
        $out = '';

        while ($value > 0x7F) {
            $out .= chr(($value & 0x7F) | 0x80);
            $value >>= 7;
        }

        return $out.chr($value);
    }

    private function field(int $number, int $wire): string
    {
        return $this->varint(($number << 3) | $wire);
    }

    private function bytes(int $number, string $payload): string
    {
        return $this->field($number, 2).$this->varint(strlen($payload)).$payload;
    }

    private function zigzag(int $value): int
    {
        return ($value << 1) ^ ($value >> 63);
    }
}
