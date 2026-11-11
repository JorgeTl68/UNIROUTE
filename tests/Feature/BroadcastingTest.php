<?php

namespace Tests\Feature;

use App\Events\TrackingTestPing;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class BroadcastingTest extends TestCase
{
    public function test_channels_file_register_default_user_channel(): void
    {
        $channels = Broadcast::getChannels();

        $this->assertTrue($channels->has('App.Models.User.{id}'));
    }

    public function test_ping_broadcasts_on_test_channel(): void
    {
        $event = new TrackingTestPing('hola');

        $channels = $event->broadcastOn();
        $payload = $event->broadcastWith();

        $this->assertCount(1, $channels);
        $this->assertSame('test.ping', $channels[0]->name);
        $this->assertSame('hola', $payload['message']);
        $this->assertArrayHasKey('sent_at', $payload);
    }

    public function test_broadcast_test_command_dispatches_ping_event(): void
    {
        Event::fake([TrackingTestPing::class]);

        $this->artisan('uniroute:broadcast-test', ['--mensaje' => 'hola'])
            ->assertSuccessful();

        Event::assertDispatchedTimes(TrackingTestPing::class, 1);
        Event::assertDispatched(
            fn (TrackingTestPing $event) => $event->message === 'hola',
        );
    }
}
