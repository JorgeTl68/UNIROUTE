<?php

namespace Tests\Feature\Notifications;

use App\Models\ArrivalAlarm;
use App\Models\User;
use App\Notifications\TransitNotification;
use App\Services\Tracking\PositionRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsLiveRoutes;
use Tests\TestCase;

class TripAlertsTest extends TestCase
{
    use BuildsLiveRoutes, RefreshDatabase;

    public function test_an_arrival_alarm_fires_once_per_trip(): void
    {
        Notification::fake();
        ['route' => $route, 'stops' => $stops, 'trip' => $trip] = $this->liveRoute();
        // "Media" queda a ~500 m: con 30 km/h son 1 o 2 minutos.
        $alarm = ArrivalAlarm::factory()->create([
            'route_id' => $route->id,
            'stop_id' => $stops[1]->id,
            'minutes_before' => 5,
        ]);
        $recorder = app(PositionRecorder::class);

        $recorder->record($trip->vehicle, 25.40, -100.994, 30.0, 90, $trip);
        $recorder->record($trip->vehicle, 25.40, -100.993, 30.0, 90, $trip);

        Notification::assertSentToTimes($alarm->user, TransitNotification::class, 1);
        $this->assertSame($trip->id, $alarm->fresh()->last_trip_id);
    }

    public function test_an_alarm_far_from_the_unit_does_not_fire_yet(): void
    {
        Notification::fake();
        ['route' => $route, 'stops' => $stops, 'trip' => $trip] = $this->liveRoute();
        $alarm = ArrivalAlarm::factory()->create([
            'route_id' => $route->id,
            'stop_id' => $stops[2]->id,
            'minutes_before' => 1,
        ]);

        app(PositionRecorder::class)->record($trip->vehicle, 25.40, -100.994, 30.0, 90, $trip);

        Notification::assertNothingSentTo($alarm->user);
    }

    public function test_a_delay_is_announced_once_to_students_with_the_route_in_favorites(): void
    {
        Notification::fake();
        ['route' => $route, 'trip' => $trip] = $this->liveRoute();
        // El viaje salió hace una hora: va muy retrasado.
        $trip->update(['scheduled_at' => now()->subHour()]);
        $student = User::factory()->student()->create();
        $student->favoriteRoutes()->attach($route->id);
        $recorder = app(PositionRecorder::class);

        $recorder->record($trip->vehicle, 25.40, -100.994, 30.0, 90, $trip);
        $recorder->record($trip->vehicle, 25.40, -100.993, 30.0, 90, $trip);

        Notification::assertSentToTimes($student, TransitNotification::class, 1);
        Notification::assertSentTo($student, TransitNotification::class, fn (TransitNotification $notification): bool => $notification->type === TransitNotification::DELAY);
        $this->assertNotNull($trip->fresh()->delay_notified_at);
    }
}
