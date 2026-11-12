<?php

namespace App\Services\Notifications;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\TransitNotification;

/**
 * Envía avisos del dominio de transporte a uno o varios usuarios.
 *
 * Centraliza el envío para que las actividades que generan avisos (llegadas,
 * retrasos, incidencias y avisos de servicio) no dependan del canal.
 */
class NotificationDispatcher
{
    public function send(User $user, TransitNotification $notification): void
    {
        $user->notify($notification);
    }

    /**
     * @param  iterable<int, User>  $users
     */
    public function sendMany(iterable $users, TransitNotification $notification): int
    {
        $sent = 0;

        foreach ($users as $user) {
            $this->send($user, $notification);
            $sent++;
        }

        return $sent;
    }

    public function sendToRole(UserRole $role, TransitNotification $notification): int
    {
        $users = User::query()->where('role', $role)->get();

        return $this->sendMany($users, $notification);
    }

    public function sendToAdmins(TransitNotification $notification): int
    {
        return $this->sendToRole(UserRole::Admin, $notification);
    }
}
