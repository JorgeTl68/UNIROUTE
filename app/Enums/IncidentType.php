<?php

namespace App\Enums;

enum IncidentType: string
{
    case Traffic = 'traffic';
    case Detour = 'detour';
    case Breakdown = 'breakdown';
    case Emergency = 'emergency';

    /**
     * Texto que se muestra en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Traffic => 'Tráfico',
            self::Detour => 'Desvío',
            self::Breakdown => 'Falla mecánica',
            self::Emergency => 'Emergencia',
        };
    }
}
