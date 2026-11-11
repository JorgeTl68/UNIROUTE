<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Driver = 'driver';
    case Admin = 'admin';

    /**
     * Texto que se muestra en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Student => 'Estudiante',
            self::Driver => 'Conductor',
            self::Admin => 'Administrador',
        };
    }
}
