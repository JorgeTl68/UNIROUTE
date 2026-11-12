<?php

namespace Tests\Unit\Tracking;

use App\Services\Tracking\Polyline;
use Tests\TestCase;

class PolylineTest extends TestCase
{
    public function test_decodes_a_known_google_polyline(): void
    {
        $points = Polyline::decode('_p~iF~ps|U_ulLnnqC_mqNvxq`@');

        $this->assertCount(3, $points);
        $this->assertEqualsWithDelta(38.5, $points[0][0], 0.00001);
        $this->assertEqualsWithDelta(-120.2, $points[0][1], 0.00001);
        $this->assertEqualsWithDelta(40.7, $points[1][0], 0.00001);
        $this->assertEqualsWithDelta(-120.95, $points[1][1], 0.00001);
        $this->assertEqualsWithDelta(43.252, $points[2][0], 0.00001);
        $this->assertEqualsWithDelta(-126.453, $points[2][1], 0.00001);
    }

    public function test_round_trips_points(): void
    {
        $points = [
            [25.4262, -100.9925],
            [25.4335, -100.9810],
            [25.4180, -100.9700],
        ];

        $decoded = Polyline::decode(Polyline::encode($points));

        $this->assertCount(3, $decoded);

        foreach ($points as $index => [$latitude, $longitude]) {
            $this->assertEqualsWithDelta($latitude, $decoded[$index][0], 0.00001);
            $this->assertEqualsWithDelta($longitude, $decoded[$index][1], 0.00001);
        }
    }

    public function test_decodes_an_empty_string_to_no_points(): void
    {
        $this->assertSame([], Polyline::decode(''));
    }
}
