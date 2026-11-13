<?php

namespace Tests\Feature\Transit;

use App\Models\Stop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\BuildsMapillaryTiles;
use Tests\TestCase;

class StreetViewTest extends TestCase
{
    use BuildsMapillaryTiles, RefreshDatabase;

    /**
     * Blvd. Coss y V. Carranza: queda en medio de su mosaico, así que se
     * consulta uno solo.
     */
    private const float LATITUDE = 25.4328363;

    private const float LONGITUDE = -100.996023;

    private Stop $stop;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mapillary.token' => 'MLY|prueba']);
        $this->stop = Stop::factory()->create(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->actingAs(User::factory()->student()->create());
    }

    /**
     * Una foto a ~20 m de la parada y otra a ~80 m (360°).
     */
    private function fakeMapillary(): void
    {
        Http::fake([
            'tiles.mapillary.com/*' => Http::response($this->mapillaryTile(self::LATITUDE, self::LONGITUDE, [
                ['latitude' => self::LATITUDE + 0.00072, 'longitude' => self::LONGITUDE, 'id' => 222, 'is_pano' => true],
                ['latitude' => self::LATITUDE + 0.00018, 'longitude' => self::LONGITUDE, 'id' => 111, 'captured_at' => 1_603_733_055_799],
            ])),
        ]);
    }

    public function test_it_returns_the_photo_closest_to_the_stop(): void
    {
        $this->fakeMapillary();

        $this->getJson(route('stops.street-view', $this->stop))
            ->assertOk()
            ->assertJsonPath('image.id', '111')
            ->assertJsonPath('image.is_pano', false)
            ->assertJsonPath('image.distance_meters', 20)
            ->assertJsonPath('image.captured_at', '2020-10-26T17:24:15+00:00')
            ->assertJsonPath('access_token', 'MLY|prueba');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/14/3595/6994')
            && str_contains($request->url(), 'access_token='));
    }

    public function test_the_answer_is_cached_so_mapillary_is_asked_once(): void
    {
        $this->fakeMapillary();

        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image.id', '111');
        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image.id', '111');

        Http::assertSentCount(1);
    }

    public function test_a_stop_without_nearby_photos_has_no_street_view(): void
    {
        Http::fake(['tiles.mapillary.com/*' => Http::response($this->mapillaryTile(self::LATITUDE, self::LONGITUDE, [
            ['latitude' => self::LATITUDE + 0.002, 'longitude' => self::LONGITUDE, 'id' => 333],
        ]))]);

        $this->getJson(route('stops.street-view', $this->stop))
            ->assertOk()
            ->assertJsonPath('image', null)
            ->assertJsonPath('access_token', null);

        // "Sin foto" también se recuerda.
        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image', null);
        Http::assertSentCount(1);
    }

    public function test_a_tile_without_the_image_layer_means_no_photo(): void
    {
        Http::fake(['tiles.mapillary.com/*' => Http::response('')]);

        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image', null);
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
        Http::fakeSequence('tiles.mapillary.com/*')
            ->pushStatus(500)
            ->push($this->mapillaryTile(self::LATITUDE, self::LONGITUDE, [
                ['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE, 'id' => 444],
            ]));

        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image', null);
        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image.id', '444');
    }

    public function test_the_weekly_command_refreshes_every_stop(): void
    {
        $this->fakeMapillary();

        $this->artisan('uniroute:vista-calle')
            ->expectsOutputToContain('Paradas con foto: 1 de 1.')
            ->assertSuccessful();

        $this->getJson(route('stops.street-view', $this->stop))->assertJsonPath('image.id', '111');
        Http::assertSentCount(1);
    }

    public function test_guests_cannot_ask_for_street_views(): void
    {
        auth()->logout();

        $this->getJson(route('stops.street-view', $this->stop))->assertUnauthorized();
    }
}
