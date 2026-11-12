<?php

namespace Tests\Feature\Tracking;

use App\Models\VehiclePosition;
use App\Services\Tracking\PositionPruner;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PositionPrunerTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_positions_older_than_seven_days(): void
    {
        $now = CarbonImmutable::parse('2026-11-11 12:00:00');
        $old = VehiclePosition::factory()->create(['recorded_at' => $now->subDays(8)]);
        $edge = VehiclePosition::factory()->create(['recorded_at' => $now->subDays(7)]);
        $recent = VehiclePosition::factory()->create(['recorded_at' => $now->subDays(6)]);

        $deleted = app(PositionPruner::class)->prune(7, $now);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('vehicle_positions', ['id' => $old->id]);
        $this->assertDatabaseHas('vehicle_positions', ['id' => $edge->id]);
        $this->assertDatabaseHas('vehicle_positions', ['id' => $recent->id]);
    }

    public function test_accepts_a_custom_retention_window(): void
    {
        $now = CarbonImmutable::parse('2026-11-11 12:00:00');
        $old = VehiclePosition::factory()->create(['recorded_at' => $now->subDays(2)]);
        $recent = VehiclePosition::factory()->create(['recorded_at' => $now->subHours(23)]);

        $deleted = app(PositionPruner::class)->prune(1, $now);

        $this->assertSame(1, $deleted);
        $this->assertDatabaseMissing('vehicle_positions', ['id' => $old->id]);
        $this->assertDatabaseHas('vehicle_positions', ['id' => $recent->id]);
    }

    public function test_rejects_a_non_positive_window(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Los días de historial deben ser al menos 1.');

        app(PositionPruner::class)->prune(0);
    }
}
