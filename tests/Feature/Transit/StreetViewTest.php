<?php

namespace Tests\Feature\Transit;

use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StreetViewTest extends TestCase
{
    use RefreshDatabase;

    private Stop $stop;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mapillary.token' => 'MLY|prueba']);
        $this->stop = Stop::factory()->create(['latitude' => 25.4328363, 'longitude' => -100.996023]);
        $this->actingAs(User::factory()->student()->create());
    }

    /**
     * Respuesta de Mapillary con una foto a ~20 m de la parada y otra a ~80 m.
     */
    private function fakeMapillary(): void
    {
        Http::fake([
            'graph.mapillary.com/*' => Http::response(['data' => [
                [
                    'id' => 'lejana',
                    'computed_geometry' => ['type' => 'Point', 'coordinates' => [-100.996023, 25.4335563]],
                    'is_pano' => true,
                    'captured_at' => 1_700_000_000_000,
                ],
                [
                    'id' => 'cercana',
                    'computed_geometry' => ['type' => 'Point', 'coordinates' => [-100.996023, 25.4330163]],
                    'is_pano' => false,
                    'captured_at' => 1_600_000_000_000,
                ],
            ]]),
        ]);
    }

    public function test_it_returns_the_photo_closest_to_the_stop(): void
    {
        $this->fakeMapillary();

        $this->getJson(route('stops.street-view', $this->stop))
            ->assertOk()
            ->assertJsonPath('image.id', 'cercana')
            ->assertJsonPath('image.is_pano', false)
            ->assertJsonPath('image.distance_meters', 20)
            ->assertJsonPath('access_token', 'MLY|prueba');

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'OAuth MLY|prueba')
            && str_contains($request->url(), 'bbox='));
    }

    public function test_the_answer_is_cached_so_mapillary_is_asked_once(): void
    {
        $this->fakeMapillary();

        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image.id', 'cercana');
        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image.id', 'cercana');

        Http::assertSentCount(1);
    }

    public function test_a_stop_without_nearby_photos_has_no_street_view(): void
    {
        Http::fake(['graph.mapillary.com/*' => Http::response(['data' => [
            [
                'id' => 'muy-lejana',
                'computed_geometry' => ['type' => 'Point', 'coordinates' => [-100.996023, 25.4345]],
            ],
        ]])]);

        $this->getJson(route('stops.street-view', $this->stop))
            ->assertOk()
            ->assertJsonPath('image', null)
            ->assertJsonPath('access_token', null);
    }

    public function test_without_a_token_mapillary_is_not_called(): void
    {
        config(['services.mapillary.token' => null]);
        Http::fake();

        $this->getJson(route('stops.street-view', $this->stop))
            ->assertOk()
            ->assertJsonPath('image', null);

        Http::assertNothingSent();
    }

    public function test_a_mapillary_failure_is_not_cached(): void
    {
        Http::fakeSequence('graph.mapillary.com/*')
            ->push(['error' => 'temporal'], 500)
            ->push(['data' => []]);

        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image', null);
        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image', null);

        Http::assertSentCount(2);
    }

    public function test_guests_cannot_ask_for_street_views(): void
    {
        auth()->logout();

        $this->getJson(route('stops.street-view', $this->stop))->assertUnauthorized();
    }
}
