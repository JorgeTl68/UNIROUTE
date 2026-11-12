<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\TransitNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_the_notification_for_the_user(): void
    {
        $user = User::factory()->create();
        $notification = new TransitNotification(
            'Tu unidad llega en 4 min',
            'BUS-UTC-01 de la Ruta 1 está por llegar a Santa Fe.',
            TransitNotification::ARRIVAL,
            routeId: 1,
            stopId: 2,
            tripId: 3,
            url: '/routes/1',
        );

        $user->notify($notification);

        $stored = $user->notifications()->sole();

        $this->assertSame(TransitNotification::class, $stored->type);
        $this->assertNull($stored->read_at);
        $this->assertSame('Tu unidad llega en 4 min', $stored->data['title']);
        $this->assertSame(TransitNotification::ARRIVAL, $stored->data['type']);
        $this->assertSame(1, $stored->data['route_id']);
        $this->assertSame(2, $stored->data['stop_id']);
        $this->assertSame(3, $stored->data['trip_id']);
        $this->assertSame('/routes/1', $stored->data['url']);
    }

    public function test_can_mark_a_notification_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new TransitNotification(
            'Aviso de servicio',
            'El último viaje sale a las 18:00.',
            TransitNotification::SERVICE,
        ));

        $stored = $user->notifications()->sole();
        $stored->markAsRead();

        $this->assertNotNull($stored->fresh()?->read_at);
    }
}
