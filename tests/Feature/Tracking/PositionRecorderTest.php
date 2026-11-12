<?php

namespace Tests\Feature\Tracking;

use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use App\Services\Tracking\PositionRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class PositionRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_a_position_for_a_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();
        $recordedAt = Carbon::parse('2026-11-11 08:30:00');

        $position = app(PositionRecorder::class)->record(
            $vehicle,
            25.4262123,
            -100.9925123,
            42.456,
            90,
            null,
            $recordedAt,
        );

        $this->assertDatabaseHas('vehicle_positions', [
            'id' => $position->id,
            'vehicle_id' => $vehicle->id,
            'trip_id' => null,
            'heading' => 90,
        ]);

        $this->assertSame($vehicle->id, $position->vehicle_id);
        $this->assertNull($position->trip_id);
        $this->assertEqualsWithDelta(25.4262123, $position->latitude, 0.0000001);
        $this->assertEqualsWithDelta(-100.9925123, $position->longitude, 0.0000001);
        $this->assertEqualsWithDelta(42.46, $position->speed_kmh, 0.0001);
        $this->assertTrue($position->recorded_at->equalTo($recordedAt));
        $this->assertFalse($position->usesTimestamps());
    }

    public function test_records_a_position_attached_to_a_trip(): void
    {
        $trip = Trip::factory()->inProgress()->create();

        $position = app(PositionRecorder::class)->record(
            $trip->vehicle,
            25.43,
            -100.99,
            trip: $trip,
        );

        $this->assertSame($trip->id, $position->trip_id);
        $this->assertSame($trip->vehicle_id, $position->vehicle_id);
        $this->assertTrue($position->trip->is($trip));
        $this->assertTrue($position->vehicle->is($trip->vehicle));
    }

    public function test_defaults_recorded_at_to_now(): void
    {
        $vehicle = Vehicle::factory()->create();

        $position = app(PositionRecorder::class)->record($vehicle, 25.42, -101.0);

        $this->assertTrue($position->recorded_at->between(
            now()->subSeconds(5),
            now()->addSeconds(5),
        ));
        $this->assertNull($position->speed_kmh);
        $this->assertNull($position->heading);
    }

    public function test_normalizes_heading_and_clamps_negative_speed(): void
    {
        $vehicle = Vehicle::factory()->create();
        $recorder = app(PositionRecorder::class);

        $wrapped = $recorder->record($vehicle, 25.42, -101.0, -12.5, 370);
        $negative = $recorder->record($vehicle, 25.42, -101.0, 10, -15);

        $this->assertSame(10, $wrapped->heading);
        $this->assertSame(0.0, $wrapped->speed_kmh);
        $this->assertSame(345, $negative->heading);
    }

    public function test_rejects_an_out_of_range_latitude(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La latitud debe estar entre -90 y 90.');

        app(PositionRecorder::class)->record($vehicle, 91, -101.0);
    }

    public function test_rejects_an_out_of_range_longitude(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('La longitud debe estar entre -180 y 180.');

        app(PositionRecorder::class)->record($vehicle, 25.42, -181.0);
    }

    public function test_rejects_a_trip_from_another_vehicle(): void
    {
        $vehicle = Vehicle::factory()->create();
        $trip = Trip::factory()->inProgress()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El viaje indicado no pertenece a la unidad.');

        app(PositionRecorder::class)->record($vehicle, 25.42, -101.0, trip: $trip);
    }

    public function test_factory_creates_positions_linked_to_vehicle_and_trip(): void
    {
        $trip = Trip::factory()->inProgress()->create();

        $position = VehiclePosition::factory()->forTrip($trip)->create();
        $stopped = VehiclePosition::factory()->stopped()->create();

        $this->assertSame($trip->id, $position->trip_id);
        $this->assertSame($trip->vehicle_id, $position->vehicle_id);
        $this->assertTrue($position->trip->is($trip));
        $this->assertTrue($position->vehicle->is($trip->vehicle));
        $this->assertSame(0.0, $stopped->speed_kmh);
        $this->assertNull($stopped->heading);
    }
}
