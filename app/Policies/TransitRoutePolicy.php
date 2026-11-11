<?php

namespace App\Policies;

use App\Models\TransitRoute;
use App\Models\User;

class TransitRoutePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * Las rutas inactivas solo las ven los administradores.
     */
    public function view(User $user, TransitRoute $transitRoute): bool
    {
        return $transitRoute->is_active || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TransitRoute $transitRoute): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TransitRoute $transitRoute): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TransitRoute $transitRoute): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TransitRoute $transitRoute): bool
    {
        return $user->isAdmin();
    }
}
