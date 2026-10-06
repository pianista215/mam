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

    public function testFlapsSamplesWhileMovingAreMergedIntoOneMarker()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '0']),
            $this->event(10, ['Flaps' => '22', 'Latitude' => '41.6']),
            $this->event(12, ['Flaps' => '34', 'Latitude' => '41.7']),
            $this->event(14, ['Flaps' => '49', 'Latitude' => '41.8']),
            $this->event(60, ['Flaps' => '49']),
        ]);

        $this->assertCount(1, $markers);
        $this->assertSame('flaps', $markers[0]['type']);
        $this->assertSame('F49', $markers[0]['short']);
        $this->assertSame(0, $markers[0]['from']);
        $this->assertSame(49, $markers[0]['to']);
        // Positioned where the movement started
        $this->assertSame(41.6, $markers[0]['lat']);
        $this->assertSame('2026-07-12 12:00:10', $markers[0]['timestamp']);
    }

    public function testFlapsRetractionAfterPreviousSettingIsOneMarker()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '49']),
            $this->event(10, ['Flaps' => '31']),
            $this->event(70, ['Flaps' => '22']),
            $this->event(72, ['Flaps' => '0']),
        ]);

        $this->assertSame(['F31', 'F0'], $this->shorts($markers));
        $this->assertSame(31, $markers[1]['from']);
        $this->assertSame(0, $markers[1]['to']);
    }

    public function testFlapsBackAndForthToSameValueProducesNoMarker()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '10']),
            $this->event(10, ['Flaps' => '20']),
            $this->event(12, ['Flaps' => '10']),
        ]);

        $this->assertSame([], $markers);
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

    public function testMarkersAreChronologicalWhenFlapsGroupClosesLater()
    {
        $markers = FlightEventMarkers::extract([
            $this->event(0, ['Flaps' => '0', 'Gear' => 'Up']),
            $this->event(10, ['Flaps' => '30', 'Gear' => 'Up']),
            $this->event(12, ['Flaps' => '40', 'Gear' => 'Down']),
            $this->event(14, ['Flaps' => '49', 'Gear' => 'Down']),
        ]);

        $this->assertSame(['F49', 'G↓'], $this->shorts($markers));
    }
}
