<?php

namespace Tests\Feature\Tracking;

use App\Enums\TripStatus;
use App\Events\TripStatusChanged;
use App\Models\Trip;
use App\Services\Tracking\TripLifecycle;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TripLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_starts_a_scheduled_trip(): void
    {
        $trip = Trip::factory()->create(['status' => TripStatus::Scheduled]);
        $at = CarbonImmutable::parse('2026-11-11 08:00:00');

        $started = app(TripLifecycle::class)->start($trip, $at);

        $this->assertSame(TripStatus::InProgress, $started->status);
        $this->assertNotNull($started->started_at);
        $this->assertTrue($started->started_at->equalTo($at));
        $this->assertNull($started->ended_at);
    }

    public function test_finishes_an_in_progress_trip(): void
    {
        $trip = Trip::factory()->inProgress()->create();
        $at = CarbonImmutable::parse('2026-11-11 09:00:00');

        $finished = app(TripLifecycle::class)->finish($trip, $at);

        $this->assertSame(TripStatus::Completed, $finished->status);
        $this->assertNotNull($finished->ended_at);
        $this->assertTrue($finished->ended_at->equalTo($at));
    }

    public function test_cannot_start_a_trip_that_is_not_scheduled(): void
    {
        $trip = Trip::factory()->inProgress()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Solo se puede iniciar un viaje programado.');

        app(TripLifecycle::class)->start($trip);
    }

    public function test_cannot_finish_a_trip_that_is_not_in_progress(): void
    {
        $trip = Trip::factory()->create(['status' => TripStatus::Scheduled]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Solo se puede terminar un viaje en curso.');

        app(TripLifecycle::class)->finish($trip);
    }

    public function test_commands_start_and_finish_a_trip(): void
    {
        $trip = Trip::factory()->create(['status' => TripStatus::Scheduled]);

        $this->artisan('uniroute:viaje:iniciar', ['trip' => $trip->id])
            ->assertSuccessful()
            ->expectsOutputToContain('iniciado');

        $this->assertSame(TripStatus::InProgress, $trip->fresh()?->status);

        $this->artisan('uniroute:viaje:terminar', ['trip' => $trip->id])
            ->assertSuccessful()
            ->expectsOutputToContain('terminado');

        $this->assertSame(TripStatus::Completed, $trip->fresh()?->status);
        $this->assertNotNull($trip->fresh()?->ended_at);
    }

    public function test_commands_fail_for_an_unknown_trip(): void
    {
        $this->artisan('uniroute:viaje:iniciar', ['trip' => 99999])
            ->assertFailed()
            ->expectsOutputToContain('No existe el viaje');
    }

    public function test_start_command_fails_when_the_trip_is_already_in_progress(): void
    {
        $trip = Trip::factory()->inProgress()->create();

        $this->artisan('uniroute:viaje:iniciar', ['trip' => $trip->id])
            ->assertFailed()
            ->expectsOutputToContain('Solo se puede iniciar un viaje programado.');
    }

    public function test_start_broadcasts_the_trip_status_changed_event(): void
    {
        Event::fake([TripStatusChanged::class]);

        $trip = Trip::factory()->create(['status' => TripStatus::Scheduled]);
        $at = CarbonImmutable::parse('2026-11-11 08:00:00');

        app(TripLifecycle::class)->start($trip, $at);

        Event::assertDispatched(
            TripStatusChanged::class,
            fn (TripStatusChanged $event): bool => $event->tripId === $trip->id
                && $event->routeId === $trip->route_id
                && $event->vehicleId === $trip->vehicle_id
                && $event->status === TripStatus::InProgress->value
                && $event->changedAt->equalTo($at),
        );
    }

    public function test_finish_broadcasts_the_trip_status_changed_event(): void
    {
        Event::fake([TripStatusChanged::class]);

        $trip = Trip::factory()->inProgress()->create();
        $at = CarbonImmutable::parse('2026-11-11 09:00:00');

        app(TripLifecycle::class)->finish($trip, $at);

        Event::assertDispatched(
            TripStatusChanged::class,
            fn (TripStatusChanged $event): bool => $event->tripId === $trip->id
                && $event->status === TripStatus::Completed->value
                && $event->changedAt->equalTo($at),
        );
    }
}
