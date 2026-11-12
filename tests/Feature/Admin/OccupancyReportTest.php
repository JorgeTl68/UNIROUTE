<?php

namespace Tests\Feature\Admin;

use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OccupancyReportTest extends TestCase
{
    use RefreshDatabase;

    private function tripsWithOccupancy(): void
    {
        $vehicle = Vehicle::factory()->create(['capacity' => 40]);

        Trip::factory()->completed()->create([
            'vehicle_id' => $vehicle->id,
            'scheduled_at' => today()->setTime(7, 0),
            'occupancy' => 38,
        ]);
        Trip::factory()->completed()->create([
            'vehicle_id' => $vehicle->id,
            'scheduled_at' => today()->setTime(13, 0),
            'occupancy' => 10,
        ]);
        // Programado: todavía no tiene aforo.
        Trip::factory()->create(['vehicle_id' => $vehicle->id, 'scheduled_at' => today()->setTime(18, 0)]);
    }

    public function test_only_admins_see_the_report(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('admin.reports.occupancy'))
            ->assertForbidden();
    }

    public function test_the_report_summarizes_occupancy_by_hour(): void
    {
        $this->tripsWithOccupancy();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.occupancy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reports/occupancy')
                ->where('summary.trips', 2)
                ->where('summary.average_percent', 60)
                ->where('summary.saturated', 1)
                ->where('summary.peak_hour', '07:00')
                ->has('hourly', 2)
                ->has('rows', 2));
    }

    public function test_the_report_is_exported_as_csv(): void
    {
        $this->tripsWithOccupancy();

        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.occupancy.export'));

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Ocupación (%)', $csv);
        $this->assertStringContainsString('95', $csv);
        $this->assertSame(3, substr_count(trim($csv), "\n") + 1);
    }
}
