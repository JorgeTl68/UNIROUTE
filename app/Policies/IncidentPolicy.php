<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    /**
     * Solo el área de transporte ve la lista de incidencias.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Cualquier persona con sesión puede reportar una emergencia.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Resolver la incidencia o avisar a los estudiantes.
     */
    public function update(User $user, Incident $incident): bool
    {
        return $user->isAdmin();
    }
}
