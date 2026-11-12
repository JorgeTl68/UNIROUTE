<?php

namespace Tests\Feature\Transit;

use App\Models\Campus;
use App\Models\Stop;
use App\Models\TransitRoute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TransitRouteCreateTest extends TestCase
{
    use RefreshDatabase;

    private Campus $campus;

    private Stop $firstStop;

    private Stop $secondStop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campus = Campus::factory()->create(['name' => 'Campus Central']);
        $this->firstStop = Stop::factory()->create([
            'name' => 'Puerta principal',
            'latitude' => 25.4,
            'longitude' => -100.9,
        ]);
        $this->secondStop = Stop::factory()->create([
            'name' => 'Terminal',
            'latitude' => 25.5,
            'longitude' => -100.8,
        ]);
    }

    /** @return array<int, array{id: int, minutes_from_start: int}> */
    private function stops(): array
    {
        return [
            ['id' => $this->firstStop->id, 'minutes_from_start' => 0],
            ['id' => $this->secondStop->id, 'minutes_from_start' => 12],
        ];
    }

    /** @return array<string, mixed> */
    private function payload(string $token, array $overrides = []): array
    {
        return array_replace([
            'campus_id' => $this->campus->id,
            'number' => 5,
            'name' => 'Campus Central – Terminal',
            'description' => 'Recorrido de prueba.',
            'color' => '#91db2a',
            'frequency_minutes' => 15,
            'service_starts_at' => '06:00',
            'service_ends_at' => '22:00',
            'stops' => $this->stops(),
            'preview_token' => $token,
        ], $overrides);
    }

    private function fakeSuccessfulOsrm(): void
    {
        Http::fake([
            '*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'geometry' => '????',
                    'distance' => 9400.5,
                    'duration' => 1800.2,
                ]],
            ]),
        ]);
    }

    private function traceAndGetToken(): string
    {
        $this->fakeSuccessfulOsrm();
        $this->post('/admin/routes/trace', ['stops' => $this->stops()])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.routes.create'));

        return (string) $this->app['session']->get('admin.transit_route_preview.token');
    }

    public function test_only_verified_administrators_can_use_the_create_flow(): void
    {
        $this->get('/admin/routes/create')->assertRedirect(route('login'));
        $this->post('/admin/routes/trace', [])->assertRedirect(route('login'));
        $this->post('/admin/routes', [])->assertRedirect(route('login'));

        foreach ([User::factory()->student()->create(), User::factory()->driver()->create()] as $user) {
            $this->actingAs($user)->get('/admin/routes/create')->assertForbidden();
            $this->post('/admin/routes/trace', [])->assertForbidden();
            $this->post('/admin/routes', [])->assertForbidden();
        }

        $this->actingAs(User::factory()->admin()->unverified()->create())
            ->get('/admin/routes/create')
            ->assertRedirect(route('verification.notice'));

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/routes/create')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/routes/create')
                ->has('campuses', 1)
                ->has('stops', 2)
                ->where('preview', null));
    }

    public function test_administrator_calculates_a_trace_with_stops_in_order(): void
    {
        $this->fakeSuccessfulOsrm();

        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/routes/trace', ['stops' => $this->stops()])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.routes.create'))
            ->assertSessionHas('admin.transit_route_preview.encoded_polyline', '????');

        Http::assertSent(function (Request $request): bool {
            return str_contains(
                $request->url(),
                '/route/v1/driving/-100.9,25.4;-100.8,25.5',
            )
                && $request['overview'] === 'full'
                && $request['geometries'] === 'polyline';
        });

        $this->get('/admin/routes/create')
            ->assertInertia(fn (Assert $page) => $page
                ->where('preview.stops.0.id', $this->firstStop->id)
                ->where('preview.stops.1.id', $this->secondStop->id)
                ->where('preview.distance_meters', 9400.5)
                ->where('preview.duration_seconds', 1800.2)
                ->where('preview.token', fn ($token) => is_string($token) && $token !== ''));
    }

    public function test_osrm_failure_returns_a_retryable_error_without_a_preview(): void
    {
        Http::fake(['*' => Http::response(['code' => 'NoRoute'], 200)]);

        $this->actingAs(User::factory()->admin()->create())
            ->from('/admin/routes/create')
            ->post('/admin/routes/trace', ['stops' => $this->stops()])
            ->assertRedirect('/admin/routes/create')
            ->assertSessionHasErrors('trace')
            ->assertSessionMissing('admin.transit_route_preview');
    }

    public function test_route_and_ordered_stops_are_saved_atomically_from_the_preview(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $token = $this->traceAndGetToken();

        $this->post('/admin/routes', $this->payload($token))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.routes.index'));

        $route = TransitRoute::sole();
        $this->assertTrue($route->is_active);
        $this->assertSame('????', $route->encoded_polyline);
        $this->assertDatabaseHas('route_stop', [
            'route_id' => $route->id,
            'stop_id' => $this->firstStop->id,
            'position' => 1,
            'minutes_from_start' => 0,
        ]);
        $this->assertDatabaseHas('route_stop', [
            'route_id' => $route->id,
            'stop_id' => $this->secondStop->id,
            'position' => 2,
            'minutes_from_start' => 12,
        ]);
        $this->assertNull($this->app['session']->get('admin.transit_route_preview'));
        Http::assertSentCount(1);
    }

    public function test_stale_preview_is_rejected_when_the_stop_order_changes(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $token = $this->traceAndGetToken();
        $reorderedStops = [
            ['id' => $this->secondStop->id, 'minutes_from_start' => 0],
            ['id' => $this->firstStop->id, 'minutes_from_start' => 12],
        ];

        $this->post('/admin/routes', $this->payload($token, ['stops' => $reorderedStops]))
            ->assertSessionHasErrors('preview_token');

        $this->assertDatabaseCount('routes', 0);
        Http::assertSentCount(1);
    }

    public function test_duplicate_route_number_is_rejected_within_the_same_campus(): void
    {
        TransitRoute::factory()->for($this->campus)->create(['number' => 5]);
        $this->actingAs(User::factory()->admin()->create());
        $token = $this->traceAndGetToken();

        $this->post('/admin/routes', $this->payload($token))
            ->assertSessionHasErrors('number');

        $this->assertDatabaseCount('routes', 1);
    }
}
