<?php

namespace App\Enums;

enum TripStatus: string
{
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Texto que se muestra en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programado',
            self::InProgress => 'En curso',
            self::Completed => 'Completado',
            self::Cancelled => 'Cancelado',
        };
    }
}
