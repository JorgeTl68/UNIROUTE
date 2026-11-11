<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case InService = 'in_service';
    case Standby = 'standby';
    case Maintenance = 'maintenance';

    /**
     * Texto que se muestra en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::InService => 'En Ruta',
            self::Standby => 'En Espera',
            self::Maintenance => 'Mantenimiento',
        };
    }
}
