<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\TransitNotification;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use InvalidArgumentException;
use Tests\TestCase;

class TransitNotificationTest extends TestCase
{
    public function test_uses_database_and_broadcast_channels(): void
    {
        $user = User::factory()->make();

        $this->assertSame(
            ['database', 'broadcast'],
            $this->notification()->via($user),
        );
    }

    public function test_broadcasts_on_the_private_user_channel(): void
    {
        $user = User::factory()->make(['id' => 42]);
        $notification = $this->notification();
        $event = new BroadcastNotificationCreated(
            $user,
            $notification,
            $notification->toArray($user),
        );

        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertSame('private-App.Models.User.42', $channels[0]->name);
    }

    public function test_serializes_the_frontend_payload(): void
    {
        $user = User::factory()->make();
        $sentAt = CarbonImmutable::parse('2026-11-11 16:41:00');
        $notification = new TransitNotification(
            'Tu unidad llega en 4 min',
            'BUS-UTC-01 de la Ruta 1 está por llegar a Santa Fe.',
            TransitNotification::ARRIVAL,
            routeId: 1,
            stopId: 2,
            tripId: 3,
            url: '/routes/1',
            sentAt: $sentAt,
        );

        $this->assertSame([
            'title' => 'Tu unidad llega en 4 min',
            'body' => 'BUS-UTC-01 de la Ruta 1 está por llegar a Santa Fe.',
            'type' => 'arrival',
            'route_id' => 1,
            'stop_id' => 2,
            'trip_id' => 3,
            'url' => '/routes/1',
            'sent_at' => $sentAt->toIso8601String(),
        ], $notification->toArray($user));
    }

    public function test_rejects_an_unknown_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El tipo de notificación no es válido.');

        new TransitNotification('Título', 'Cuerpo', 'unknown');
    }

    private function notification(): TransitNotification
    {
        return new TransitNotification(
            'Retraso de 9 min en Ruta 2',
            'Tráfico denso en Blvd. Plan de Guadalupe.',
            TransitNotification::DELAY,
            routeId: 2,
        );
    }
}
