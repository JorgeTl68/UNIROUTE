<?php

namespace Tests\Feature\Tracking;

use App\Models\VehiclePosition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneVehiclePositionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_old_positions_and_reports_the_count(): void
    {
        $old = VehiclePosition::factory()->create(['recorded_at' => now()->subDays(8)]);
        $recent = VehiclePosition::factory()->create(['recorded_at' => now()->subDays(2)]);

        $this->artisan('uniroute:limpiar-posiciones')
            ->assertSuccessful()
            ->expectsOutputToContain('Posiciones eliminadas: 1');

        $this->assertDatabaseMissing('vehicle_positions', ['id' => $old->id]);
        $this->assertDatabaseHas('vehicle_positions', ['id' => $recent->id]);
    }

    public function test_accepts_a_custom_number_of_days(): void
    {
        $old = VehiclePosition::factory()->create(['recorded_at' => now()->subDays(2)]);
        $recent = VehiclePosition::factory()->create(['recorded_at' => now()->subHours(12)]);

        $this->artisan('uniroute:limpiar-posiciones', ['--dias' => 1])
            ->assertSuccessful()
            ->expectsOutputToContain('Posiciones eliminadas: 1');

        $this->assertDatabaseMissing('vehicle_positions', ['id' => $old->id]);
        $this->assertDatabaseHas('vehicle_positions', ['id' => $recent->id]);
    }

    public function test_rejects_an_invalid_window(): void
    {
        $this->artisan('uniroute:limpiar-posiciones', ['--dias' => 0])
            ->assertFailed()
            ->expectsOutputToContain('Los días deben ser un entero mayor o igual a 1.');
    }
}
