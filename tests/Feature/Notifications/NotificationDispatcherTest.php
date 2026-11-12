<?php

namespace Tests\Feature\Notifications;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\TransitNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_a_notification_to_a_user(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $notification = $this->notification();

        app(NotificationDispatcher::class)->send($user, $notification);

        Notification::assertSentTo($user, TransitNotification::class);
    }

    public function test_sends_many_notifications_and_returns_the_count(): void
    {
        Notification::fake();

        $users = User::factory()->count(3)->create();
        $sent = app(NotificationDispatcher::class)->sendMany($users, $this->notification());

        $this->assertSame(3, $sent);

        foreach ($users as $user) {
            Notification::assertSentTo($user, TransitNotification::class);
        }
    }

    public function test_sends_only_to_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $driver = User::factory()->driver()->create();

        $sent = app(NotificationDispatcher::class)->sendToAdmins($this->notification());

        $this->assertSame(1, $sent);
        Notification::assertSentTo($admin, TransitNotification::class);
        Notification::assertNotSentTo($student, TransitNotification::class);
        Notification::assertNotSentTo($driver, TransitNotification::class);
    }

    public function test_sends_to_every_user_with_the_given_role(): void
    {
        Notification::fake();

        $drivers = User::factory()->driver()->count(2)->create();
        $student = User::factory()->student()->create();

        $sent = app(NotificationDispatcher::class)->sendToRole(
            UserRole::Driver,
            $this->notification(),
        );

        $this->assertSame(2, $sent);

        foreach ($drivers as $driver) {
            Notification::assertSentTo($driver, TransitNotification::class);
        }

        Notification::assertNotSentTo($student, TransitNotification::class);
    }

    private function notification(): TransitNotification
    {
        return new TransitNotification(
            'Tu unidad llega en 4 min',
            'BUS-UTC-01 de la Ruta 1 está por llegar a Santa Fe.',
            TransitNotification::ARRIVAL,
            routeId: 1,
            stopId: 2,
            tripId: 3,
        );
    }
}
