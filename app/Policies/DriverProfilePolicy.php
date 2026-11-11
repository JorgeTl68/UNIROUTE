<?php

namespace App\Policies;

use App\Models\DriverProfile;
use App\Models\User;

class DriverProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     *
     * El conductor puede ver su propio perfil.
     */
    public function view(User $user, DriverProfile $driverProfile): bool
    {
        return $user->isAdmin() || $driverProfile->user_id === $user->id;
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
    public function update(User $user, DriverProfile $driverProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DriverProfile $driverProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DriverProfile $driverProfile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DriverProfile $driverProfile): bool
    {
        return $user->isAdmin();
    }
}
