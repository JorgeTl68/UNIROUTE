<?php

namespace App\Policies;

use App\Models\ArrivalAlarm;
use App\Models\User;

class ArrivalAlarmPolicy
{
    /**
     * Cada quien quita solo sus propias alarmas.
     */
    public function delete(User $user, ArrivalAlarm $arrivalAlarm): bool
    {
        return $arrivalAlarm->user_id === $user->id;
    }
}
