<?php

namespace App\Services\Transit;

/**
 * Lector mínimo de mosaicos vectoriales (Mapbox Vector Tile, en protobuf).
 *
 * Solo lee lo que necesita la vista de calle: los puntos de una capa con sus
 * propiedades. Especificación: https://github.com/mapbox/vector-tile-spec.
 */
final class VectorTile
{
    private const int WIRE_VARINT = 0;

    private const int WIRE_FIXED64 = 1;

    private const int WIRE_BYTES = 2;

    private const int WIRE_FIXED32 = 5;

    private int $offset = 0;

    private function __construct(private readonly string $data) {}

    /**
     * Puntos de una capa en coordenadas del mosaico (de 0 a extent), o null si
     * el mosaico no trae esa capa.
     *
     * @return array{extent: int, points: list<array{x: int, y: int, properties: array<string, string|int|float|bool>}>}|null
     */
    public static function layerPoints(string $data, string $layer): ?array
    {
        $reader = new self($data);
        $end = strlen($data);

        while ($reader->offset < $end) {
            [$field, $wire] = $reader->key();

            if ($field === 3 && $wire === self::WIRE_BYTES) {
                $layerEnd = $reader->varint() + $reader->offset;
                $points = $reader->layer($layerEnd, $layer);
                $reader->offset = $layerEnd;

                if ($points !== null) {
                    return $points;
                }

                continue;
            }

            $reader->skip($wire);
        }

        return null;
    }

    /**
     * @return array{extent: int, points: list<array{x: int, y: int, properties: array<string, string|int|float|bool>}>}|null
     */
    private function layer(int $end, string $wanted): ?array
    {
        $name = null;
        $extent = 4096;
        $features = [];
        $keys = [];
        $values = [];

        while ($this->offset < $end) {
            [$field, $wire] = $this->key();

            match (true) {
                $field === 1 && $wire === self::WIRE_BYTES => $name = $this->bytes(),
                $field === 2 && $wire === self::WIRE_BYTES => $features[] = $this->range(),
                $field === 3 && $wire === self::WIRE_BYTES => $keys[] = $this->bytes(),
                $field === 4 && $wire === self::WIRE_BYTES => $values[] = $this->value(),
                $field === 5 && $wire === self::WIRE_VARINT => $extent = $this->varint(),
                default => $this->skip($wire),
            };
        }

        if ($name !== $wanted) {
            return null;
        }

        $points = [];

        foreach ($features as [$start, $featureEnd]) {
            $this->offset = $start;
            $point = $this->point($featureEnd, $keys, $values);

            if ($point !== null) {
                $points[] = $point;
            }
        }

        return ['extent' => $extent, 'points' => $points];
    }

    /**
     * Primer punto de la geometría de un feature con sus propiedades.
     *
     * @param  list<string>  $keys
     * @param  list<string|int|float|bool|null>  $values
     * @return array{x: int, y: int, properties: array<string, string|int|float|bool>}|null
     */
    private function point(int $end, array $keys, array $values): ?array
    {
        $tags = [];
        $geometry = [];

        while ($this->offset < $end) {
            [$field, $wire] = $this->key();

            if (($field === 2 || $field === 4) && $wire === self::WIRE_BYTES) {
                $packedEnd = $this->varint() + $this->offset;
                $list = [];
                while ($this->offset < $packedEnd) {
                    $list[] = $this->varint();
                }
                $field === 2 ? $tags = $list : $geometry = $list;

                continue;
            }

            $this->skip($wire);
        }

        // MoveTo (comando 1) seguido de dx y dy en zigzag.
        if (count($geometry) < 3 || ($geometry[0] & 0x7) !== 1) {
            return null;
        }

        $properties = [];
        for ($i = 0; $i + 1 < count($tags); $i += 2) {
            $key = $keys[$tags[$i]] ?? null;
            $value = $values[$tags[$i + 1]] ?? null;

            if ($key !== null && $value !== null) {
                $properties[$key] = $value;
            }
        }

        return [
            'x' => self::zigzag($geometry[1]),
            'y' => self::zigzag($geometry[2]),
            'properties' => $properties,
        ];
    }

    private function value(): string|int|float|bool|null
    {
        $end = $this->varint() + $this->offset;
        $value = null;

        while ($this->offset < $end) {
            [$field, $wire] = $this->key();

            $value = match ($field) {
                1 => $this->bytes(),
                2 => $this->fixed32Float(),
                3 => $this->fixed64Float(),
                4, 5 => $this->varint(),
                6 => self::zigzag($this->varint()),
                7 => $this->varint() !== 0,
                default => $this->skipAndNull($wire),
            };
        }

        return $value;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function key(): array
    {
        $key = $this->varint();

        return [$key >> 3, $key & 0x7];
    }

    private function varint(): int
    {
        $result = 0;
        $shift = 0;

        do {
            $byte = ord($this->data[$this->offset++]);

            if ($shift < 63) {
                $result |= ($byte & 0x7F) << $shift;
            }
            $shift += 7;
        } while ($byte & 0x80);

        return $result;
    }

    private function bytes(): string
    {
        $length = $this->varint();
        $bytes = substr($this->data, $this->offset, $length);
        $this->offset += $length;

        return $bytes;
    }

    /**
     * Inicio y fin de un mensaje anidado, para leerlo después.
     *
     * @return array{0: int, 1: int}
     */
    private function range(): array
    {
        $length = $this->varint();
        $start = $this->offset;
        $this->offset += $length;

        return [$start, $start + $length];
    }

    private function fixed32Float(): float
    {
        $value = unpack('g', substr($this->data, $this->offset, 4));
        $this->offset += 4;

        return is_array($value) && is_float($value[1]) ? $value[1] : 0.0;
    }

    private function fixed64Float(): float
    {
        $value = unpack('e', substr($this->data, $this->offset, 8));
        $this->offset += 8;

        return is_array($value) && is_float($value[1]) ? $value[1] : 0.0;
    }

    private function skip(int $wire): void
    {
        $length = match ($wire) {
            self::WIRE_VARINT => $this->varint() * 0,
            self::WIRE_FIXED64 => 8,
            self::WIRE_BYTES => $this->varint(),
            self::WIRE_FIXED32 => 4,
            // Tipo desconocido: no se puede seguir leyendo con seguridad.
            default => strlen($this->data),
        };

        $this->offset += $length;
    }

    private function skipAndNull(int $wire): null
    {
        $this->skip($wire);

        return null;
    }

    private static function zigzag(int $value): int
    {
        return ($value >> 1) ^ -($value & 1);
    }
}
