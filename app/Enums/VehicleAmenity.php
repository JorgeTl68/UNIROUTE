<?php

namespace App\Enums;

enum VehicleAmenity: string
{
    case AirConditioning = 'air_conditioning';
    case Wifi = 'wifi';
    case AccessibleRamp = 'accessible_ramp';

    /**
     * Texto que se muestra en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::AirConditioning => 'Climatizado',
            self::Wifi => 'WiFi Alumnos',
            self::AccessibleRamp => 'Rampa Accesible',
        };
    }
}
