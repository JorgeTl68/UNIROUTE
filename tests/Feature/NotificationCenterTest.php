<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TransitNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function notify(User $user, string $url = '/routes/1'): void
    {
        $user->notify(new TransitNotification(
            title: 'Tu unidad llega en 4 min',
            body: 'Ruta 1 · Parada Santa Fe',
            type: TransitNotification::ARRIVAL,
            url: $url,
        ));
    }

    public function test_the_page_lists_the_user_notifications_only(): void
    {
        $student = User::factory()->student()->create();
        $this->notify($student);
        $this->notify(User::factory()->student()->create());

        $this->actingAs($student)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('notifications/index')
                ->has('items', 1)
                ->where('items.0.type', 'arrival')
                ->where('items.0.read_at', null)
                ->where('notifications.unread', 1));
    }

    public function test_opening_a_notification_marks_it_read_and_follows_its_link(): void
    {
        $student = User::factory()->student()->create();
        $this->notify($student, '/routes/7');
        $notification = $student->notifications()->first();

        $this->actingAs($student)
            ->get(route('notifications.open', $notification->id))
            ->assertRedirect('/routes/7');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_user_cannot_open_someone_else_notification(): void
    {
        $owner = User::factory()->student()->create();
        $this->notify($owner);

        $this->actingAs(User::factory()->student()->create())
            ->get(route('notifications.open', $owner->notifications()->first()->id))
            ->assertNotFound();
    }

    public function test_all_notifications_can_be_marked_as_read(): void
    {
        $student = User::factory()->student()->create();
        $this->notify($student);
        $this->notify($student);

        $this->actingAs($student)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $student->unreadNotifications()->count());
    }
}
