<?php

namespace tests\unit\helpers;

use app\helpers\PayloadEstimator;
use app\models\AircraftConfiguration;
use DateTime;
use tests\unit\BaseUnitTest;

class PayloadEstimatorTest extends BaseUnitTest
{
    private function makeConfig(array $attrs): AircraftConfiguration
    {
        $config = new AircraftConfiguration();
        $config->crew           = $attrs['crew']           ?? 2;
        $config->mtow           = $attrs['mtow']           ?? 70000;
        $config->oew            = $attrs['oew']            ?? 40000;
        $config->pax_capacity   = $attrs['pax_capacity']   ?? 150;
        $config->cargo_capacity = $attrs['cargo_capacity'] ?? 15000;
        return $config;
    }

    private function generate(AircraftConfiguration $config, ?float $fuel, string $date = '2026-06-20'): array
    {
        return PayloadEstimator::generate($config, $fuel, 84, 35, 13, new DateTime($date));
    }

    // --- Cargo-only aircraft ---

    public function testCargoOnlyAircraftHasNoPax()
    {
        $config = $this->makeConfig(['pax_capacity' => 0, 'cargo_capacity' => 20000]);

        $result = $this->generate($config, 5000.0);

        $this->assertEquals(0, $result['pax_adults']);
        $this->assertEquals(0, $result['pax_children']);
        $this->assertEquals(0, $result['cargo_bags']);
        $this->assertGreaterThanOrEqual(0, $result['cargo_paid_kg']);
    }

    public function testCargoOnlyFillsInRange()
    {
        $config = $this->makeConfig(['pax_capacity' => 0, 'cargo_capacity' => 10000]);
        // Available payload = 70000 - 40000 - 5000 - 2*84 = 24832 → capped at cargo_capacity=10000
        $result = $this->generate($config, 5000.0);

        $this->assertGreaterThanOrEqual((int) round(10000 * 0.60), $result['cargo_paid_kg']);
        $this->assertLessThanOrEqual(10000, $result['cargo_paid_kg']);
    }

    // --- Pax aircraft with fuel regression ---

    public function testPaxAircraftRespectsPaxCapacity()
    {
        $config = $this->makeConfig(['pax_capacity' => 150]);

        $result = $this->generate($config, 8000.0);

        $this->assertGreaterThanOrEqual(0, $result['pax_adults']);
        $this->assertGreaterThanOrEqual(0, $result['pax_children']);
        $this->assertLessThanOrEqual(150, $result['pax_adults'] + $result['pax_children']);
    }

    public function testPaxAircraftRespectsCargoCapacity()
    {
        $config = $this->makeConfig(['pax_capacity' => 150, 'cargo_capacity' => 5000]);

        $result = $this->generate($config, 8000.0);

        $totalCargo = $result['cargo_bags'] * 13 + $result['cargo_paid_kg'];
        $this->assertLessThanOrEqual(5000, $totalCargo);
    }

    public function testChildrenNeverExceedTenPercentOfPax()
    {
        $config = $this->makeConfig(['pax_capacity' => 200]);

        for ($i = 0; $i < 30; $i++) {
            $result = $this->generate($config, 8000.0);
            $paxTotal = $result['pax_adults'] + $result['pax_children'];
            if ($paxTotal > 0) {
                // Allow 1 child rounding tolerance above 10%
                $this->assertLessThanOrEqual((int) round($paxTotal * 0.10) + 1, $result['pax_children']);
            }
        }
    }

    public function testBagsAreInRange()
    {
        $config = $this->makeConfig(['pax_capacity' => 180]);

        for ($i = 0; $i < 30; $i++) {
            $result = $this->generate($config, 8000.0);
            $paxTotal = $result['pax_adults'] + $result['pax_children'];
            if ($paxTotal > 0) {
                $this->assertGreaterThanOrEqual((int) round($paxTotal * 0.20) - 1, $result['cargo_bags']);
                $this->assertLessThanOrEqual((int) round($paxTotal * 0.35) + 1, $result['cargo_bags']);
            }
        }
    }

    // --- No fuel regression fallback ---

    public function testFallbackWhenNoFuel()
    {
        $config = $this->makeConfig(['pax_capacity' => 100]);

        $result = $this->generate($config, null);

        $this->assertArrayHasKey('pax_adults', $result);
        $this->assertArrayHasKey('pax_children', $result);
        $this->assertArrayHasKey('cargo_bags', $result);
        $this->assertArrayHasKey('cargo_paid_kg', $result);
        $this->assertLessThanOrEqual(100, $result['pax_adults'] + $result['pax_children']);
        $this->assertGreaterThanOrEqual(0, $result['cargo_paid_kg']);
    }

    public function testFallbackCapsTotalWeightAtFiftyPercent()
    {
        $mtow   = 100000;
        $oew    = 50000;
        $crew   = 2;
        $adultW = 84;
        $config = $this->makeConfig([
            'mtow' => $mtow, 'oew' => $oew, 'crew' => $crew,
            'pax_capacity' => 150, 'cargo_capacity' => 20000,
        ]);
        // With null fuel: availablePayload = (MTOW - OEW - crew*adultW) * 0.5
        $maxPayload = ($mtow - $oew - $crew * $adultW) * 0.5;

        for ($i = 0; $i < 20; $i++) {
            $result      = $this->generate($config, null);
            $paxWeight   = $result['pax_adults'] * $adultW + $result['pax_children'] * 35;
            $bagsWeight  = $result['cargo_bags'] * 13;
            $totalWeight = $paxWeight + $bagsWeight + $result['cargo_paid_kg'];

            $this->assertLessThanOrEqual($maxPayload, $totalWeight);
        }
    }

    // --- Zero available payload edge case ---

    public function testZeroAvailablePayloadReturnsAllZeros()
    {
        // fuel = MTOW - OEW - crew_weight so nothing is left
        $config = $this->makeConfig(['crew' => 2, 'mtow' => 70000, 'oew' => 40000, 'pax_capacity' => 150]);
        // fuel = 70000 - 40000 - 168 = 29832 → availablePayload = 0
        $fuel = 70000 - 40000 - (2 * 84);

        $result = $this->generate($config, (float) $fuel);

        $this->assertEquals(0, $result['pax_adults']);
        $this->assertEquals(0, $result['pax_children']);
        $this->assertEquals(0, $result['cargo_bags']);
        $this->assertEquals(0, $result['cargo_paid_kg']);
    }

    // --- Weekday effect ---

    public function testHighTrafficDaysYieldHigherAverageOccupancy()
    {
        $config = $this->makeConfig(['pax_capacity' => 200, 'crew' => 2, 'mtow' => 100000, 'oew' => 50000]);
        $iterations = 100;
        $fuel = 10000.0;

        $fridayTotal = 0;
        $tuesdayTotal = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $fri = $this->generate($config, $fuel, '2026-06-19'); // Friday
            $tue = $this->generate($config, $fuel, '2026-06-16'); // Tuesday
            $fridayTotal   += $fri['pax_adults'] + $fri['pax_children'];
            $tuesdayTotal  += $tue['pax_adults'] + $tue['pax_children'];
        }

        // Friday average must be at least as high as Tuesday (given high-season 50-85 vs 40-80)
        $this->assertGreaterThanOrEqual($tuesdayTotal / $iterations, $fridayTotal / $iterations);
    }

    // --- All output values are non-negative ---

    public function testAllValuesAreNonNegative()
    {
        $config = $this->makeConfig([]);

        for ($i = 0; $i < 20; $i++) {
            $result = $this->generate($config, 8000.0);
            $this->assertGreaterThanOrEqual(0, $result['pax_adults']);
            $this->assertGreaterThanOrEqual(0, $result['pax_children']);
            $this->assertGreaterThanOrEqual(0, $result['cargo_bags']);
            $this->assertGreaterThanOrEqual(0, $result['cargo_paid_kg']);
        }
    }
}
