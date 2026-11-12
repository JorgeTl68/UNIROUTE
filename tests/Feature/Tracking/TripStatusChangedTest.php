<?php

namespace Tests\Feature\Tracking;

use App\Enums\TripStatus;
use App\Events\TripStatusChanged;
use App\Models\Trip;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripStatusChangedTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcasts_on_route_and_campus_channels(): void
    {
        $trip = Trip::factory()->create(['status' => TripStatus::Scheduled]);
        $event = TripStatusChanged::fromTrip($trip);

        $names = array_map(
            fn (Channel $channel): string => $channel->name,
            $event->broadcastOn(),
        );

        $this->assertSame([
            'route.'.$trip->route_id,
            'campus.'.$trip->route->campus_id,
        ], $names);
    }

    public function test_payload_matches_the_frontend_contract(): void
    {
        $at = CarbonImmutable::parse('2026-11-11 08:00:00');
        $trip = Trip::factory()->create(['status' => TripStatus::Scheduled]);
        $event = TripStatusChanged::fromTrip($trip, $at);

        $this->assertSame([
            'trip_id' => $trip->id,
            'route_id' => $trip->route_id,
            'vehicle_id' => $trip->vehicle_id,
            'status' => 'scheduled',
            'changed_at' => $at->toIso8601String(),
        ], $event->broadcastWith());
    }

    public function test_uses_the_trip_status_enum_value(): void
    {
        $trip = Trip::factory()->inProgress()->create();
        $event = TripStatusChanged::fromTrip($trip);

        $this->assertSame(TripStatus::InProgress->value, $event->status);
        $this->assertSame($trip->id, $event->tripId);
    }
}
