<?php

namespace tests\unit\helpers;

use app\helpers\FlightEventMarkers;
use tests\unit\BaseUnitTest;

class FlightEventMarkersTest extends BaseUnitTest
{
    private const BASE_TIME = '2026-07-12 12:00:00';

    /**
     * Builds an event at BASE_TIME + $offsetSeconds with a default position.
     */
    private function event(int $offsetSeconds, array $values, bool $withPosition = true): array
    {
        if ($withPosition) {
            $values += ['Latitude' => '41.5', 'Longitude' => '2.1', 'Heading' => '90', 'Altitude' => '1000'];
        }
        return [
            'timestamp' => date('Y-m-d H:i:s', strtotime(self::BASE_TIME) + $offsetSeconds),
            'values' => $values,
        ];
    }

    private function shorts(array $markers): array
    {
        return array_column($markers, 'short');
    }

    public function testInitialStateProducesNoMarkers()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '0', 'Gear' => 'Down', 'onGround' => 'True', 'AP' => 'Off']),
            $this->event(5, ['Flaps' => '0', 'Gear' => 'Down', 'onGround' => 'True', 'AP' => 'Off']),
        ]);

        $this->assertSame([], $markers);
    }

    public function testEveryFlapsSelectionIsAMarker()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '0']),
            $this->event(10, ['Flaps' => '22', 'Latitude' => '41.6']),
            $this->event(12, ['Flaps' => '34', 'Latitude' => '41.7']),
            $this->event(14, ['Flaps' => '49', 'Latitude' => '41.8']),
            $this->event(60, ['Flaps' => '49']),
        ]);

        // Quick successive selections are not merged: each one is shown
        $this->assertSame(['F22', 'F34', 'F49'], $this->shorts($markers));
        $this->assertSame([0, 22, 34], array_column($markers, 'from'));
        $this->assertSame([22, 34, 49], array_column($markers, 'to'));
        $this->assertSame([41.6, 41.7, 41.8], array_column($markers, 'lat'));
        $this->assertSame('2026-07-12 12:00:10', $markers[0]['timestamp']);
    }

    public function testFlapsRetractionSteps()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '49']),
            $this->event(10, ['Flaps' => '31']),
            $this->event(70, ['Flaps' => '22']),
            $this->event(72, ['Flaps' => '0']),
        ]);

        $this->assertSame(['F31', 'F22', 'F0'], $this->shorts($markers));
        $this->assertSame(22, $markers[2]['from']);
        $this->assertSame(0, $markers[2]['to']);
    }

    public function testFlapsBackAndForthProducesBothMarkers()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '10']),
            $this->event(10, ['Flaps' => '20']),
            $this->event(12, ['Flaps' => '10']),
        ]);

        $this->assertSame(['F20', 'F10'], $this->shorts($markers));
    }

    public function testGearLiftoffTouchdownAndAutopilot()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Gear' => 'Down', 'onGround' => 'True', 'AP' => 'Off']),
            $this->event(10, ['Gear' => 'Down', 'onGround' => 'False', 'AP' => 'Off']),
            $this->event(15, ['Gear' => 'Up', 'onGround' => 'False', 'AP' => 'Off']),
            $this->event(60, ['Gear' => 'Up', 'onGround' => 'False', 'AP' => 'On']),
            $this->event(600, ['Gear' => 'Down', 'onGround' => 'False', 'AP' => 'On']),
            $this->event(650, ['Gear' => 'Down', 'onGround' => 'False', 'AP' => 'Off']),
            $this->event(700, ['Gear' => 'Down', 'onGround' => 'True', 'AP' => 'Off', 'LandingVSFpm' => '-402']),
        ]);

        $this->assertSame(['LO', 'G↑', 'AP ON', 'G↓', 'AP OFF', 'TD'], $this->shorts($markers));
        $this->assertSame('liftoff', $markers[0]['type']);
        $this->assertSame('autopilot', $markers[2]['type']);
        $this->assertSame('On', $markers[2]['to']);
        $this->assertSame('Off', $markers[4]['to']);
        $this->assertSame('touchdown', $markers[5]['type']);
        $this->assertSame(-402, $markers[5]['extra']);
    }

    public function testTouchdownVerticalSpeedInLaterEventIsAttached()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['onGround' => 'False']),
            $this->event(10, ['onGround' => 'True']),
            $this->event(12, ['onGround' => 'True', 'LandingVSFpm' => '-150']),
        ]);

        $this->assertSame(['TD'], $this->shorts($markers));
        $this->assertSame(-150, $markers[0]['extra']);
    }

    public function testEventWithoutPositionUsesLastKnownPosition()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Gear' => 'Down', 'Latitude' => '40.0', 'Longitude' => '-3.0']),
            $this->event(10, ['Gear' => 'Up'], false),
        ]);

        $this->assertCount(1, $markers);
        $this->assertSame(40.0, $markers[0]['lat']);
        $this->assertSame(-3.0, $markers[0]['lon']);
        $this->assertSame(90.0, $markers[0]['heading']);
    }

    public function testMarkersAreChronological()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '0', 'Gear' => 'Up']),
            $this->event(10, ['Flaps' => '30', 'Gear' => 'Up']),
            $this->event(12, ['Flaps' => '40', 'Gear' => 'Down']),
            $this->event(14, ['Flaps' => '49', 'Gear' => 'Down']),
        ]);

        $this->assertSame(['F30', 'F40', 'G↓', 'F49'], $this->shorts($markers));
    }
}
