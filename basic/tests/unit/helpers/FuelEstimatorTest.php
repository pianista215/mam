<?php

namespace tests\unit\helpers;

use app\helpers\FuelEstimator;
use app\models\AircraftConfiguration;
use tests\unit\BaseUnitTest;

class FuelEstimatorTest extends BaseUnitTest
{
    // --- calculateRegression tests ---

    public function testCalculateRegressionWithValidData()
    {
        // y = 100 + 3*x exactly; distances 800-1700 → ratios 3.09..3.125, all within 2σ
        $flights = [
            ['distance_nm' => 800,  'total_fuel_burn_kg' => 2500, 'flight_time_minutes' => 130],
            ['distance_nm' => 900,  'total_fuel_burn_kg' => 2800, 'flight_time_minutes' => 145],
            ['distance_nm' => 1000, 'total_fuel_burn_kg' => 3100, 'flight_time_minutes' => 160],
            ['distance_nm' => 1100, 'total_fuel_burn_kg' => 3400, 'flight_time_minutes' => 175],
            ['distance_nm' => 1200, 'total_fuel_burn_kg' => 3700, 'flight_time_minutes' => 190],
            ['distance_nm' => 1300, 'total_fuel_burn_kg' => 4000, 'flight_time_minutes' => 205],
            ['distance_nm' => 1400, 'total_fuel_burn_kg' => 4300, 'flight_time_minutes' => 220],
            ['distance_nm' => 1500, 'total_fuel_burn_kg' => 4600, 'flight_time_minutes' => 235],
            ['distance_nm' => 1600, 'total_fuel_burn_kg' => 4900, 'flight_time_minutes' => 250],
            ['distance_nm' => 1700, 'total_fuel_burn_kg' => 5200, 'flight_time_minutes' => 265],
        ];

        $result = FuelEstimator::calculateRegression($flights);

        $this->assertNotNull($result);
        $this->assertEquals(10, $result['n']);
        $this->assertEqualsWithDelta(3.0, $result['b'], 0.01);
        $this->assertEqualsWithDelta(100.0, $result['a'], 1.0);
        $this->assertGreaterThan(0, $result['avg_kg_per_min']);
    }

    public function testCalculateRegressionInsufficientData()
    {
        $flights = [
            ['distance_nm' => 500, 'total_fuel_burn_kg' => 2000, 'flight_time_minutes' => 90],
            ['distance_nm' => 600, 'total_fuel_burn_kg' => 2300, 'flight_time_minutes' => 100],
            ['distance_nm' => 700, 'total_fuel_burn_kg' => 2600, 'flight_time_minutes' => 110],
            ['distance_nm' => 800, 'total_fuel_burn_kg' => 2900, 'flight_time_minutes' => 120],
        ];

        $this->assertNull(FuelEstimator::calculateRegression($flights));
    }

    public function testCalculateRegressionFiltersHardFloor()
    {
        // 7 valid + 3 that fail hard floor (time < 20, fuel = 0, distance = 0)
        $flights = [
            ['distance_nm' => 500,  'total_fuel_burn_kg' => 2000, 'flight_time_minutes' => 90],
            ['distance_nm' => 600,  'total_fuel_burn_kg' => 2300, 'flight_time_minutes' => 100],
            ['distance_nm' => 700,  'total_fuel_burn_kg' => 2600, 'flight_time_minutes' => 110],
            ['distance_nm' => 800,  'total_fuel_burn_kg' => 2900, 'flight_time_minutes' => 120],
            ['distance_nm' => 900,  'total_fuel_burn_kg' => 3200, 'flight_time_minutes' => 130],
            ['distance_nm' => 1000, 'total_fuel_burn_kg' => 3500, 'flight_time_minutes' => 140],
            ['distance_nm' => 1100, 'total_fuel_burn_kg' => 3800, 'flight_time_minutes' => 150],
            ['distance_nm' => 400,  'total_fuel_burn_kg' => 1500, 'flight_time_minutes' => 15],  // time < 20
            ['distance_nm' => 400,  'total_fuel_burn_kg' => 0,    'flight_time_minutes' => 80],  // fuel = 0
            ['distance_nm' => 0,    'total_fuel_burn_kg' => 1500, 'flight_time_minutes' => 80],  // distance = 0
        ];

        $result = FuelEstimator::calculateRegression($flights);

        $this->assertNotNull($result);
        $this->assertEquals(7, $result['n']);
    }

    public function testCalculateRegressionFiltersOutliers()
    {
        // 8 normal flights at ratio ≈ 3.0 Kg/NM + 1 clear outlier at ratio ≈ 33 Kg/NM.
        // With 1 outlier out of 9 total, the z-score method reliably detects it:
        // sqrt((n-k)/k) = sqrt(8/1) ≈ 2.83 > 2, so the outlier's deviation exceeds 2σ.
        $flights = [
            ['distance_nm' => 500,  'total_fuel_burn_kg' => 1800, 'flight_time_minutes' => 90],
            ['distance_nm' => 600,  'total_fuel_burn_kg' => 2100, 'flight_time_minutes' => 100],
            ['distance_nm' => 700,  'total_fuel_burn_kg' => 2400, 'flight_time_minutes' => 110],
            ['distance_nm' => 800,  'total_fuel_burn_kg' => 2700, 'flight_time_minutes' => 120],
            ['distance_nm' => 900,  'total_fuel_burn_kg' => 3000, 'flight_time_minutes' => 130],
            ['distance_nm' => 1000, 'total_fuel_burn_kg' => 3300, 'flight_time_minutes' => 140],
            ['distance_nm' => 1100, 'total_fuel_burn_kg' => 3600, 'flight_time_minutes' => 150],
            ['distance_nm' => 1200, 'total_fuel_burn_kg' => 3900, 'flight_time_minutes' => 160],
            ['distance_nm' => 300,  'total_fuel_burn_kg' => 10000, 'flight_time_minutes' => 60], // outlier: ratio=33
        ];

        $result = FuelEstimator::calculateRegression($flights);

        $this->assertNotNull($result);
        $this->assertEquals(8, $result['n']);
    }

    public function testCalculateRegressionBadSlope()
    {
        // All flights with the same distance → denominator = 0
        $flights = [];
        for ($i = 0; $i < 8; $i++) {
            $flights[] = ['distance_nm' => 500, 'total_fuel_burn_kg' => 2000 + $i * 100, 'flight_time_minutes' => 90 + $i];
        }

        $this->assertNull(FuelEstimator::calculateRegression($flights));
    }

    // --- estimate tests ---

    public function testEstimateWithRegression()
    {
        $config = new AircraftConfiguration();
        $config->fuel_regression_a = 500.0;
        $config->fuel_regression_b = 3.0;
        $config->fuel_regression_n = 10;
        $config->fuel_avg_kg_per_min = 50.0;

        $result = FuelEstimator::estimate($config, 1000, 200, 30);

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(3500.0, $result['trip'], 0.01);        // 500 + 3*1000
        $this->assertEqualsWithDelta(1100.0, $result['alternate'], 0.01);   // 500 + 3*200
        $this->assertEqualsWithDelta(175.0, $result['contingency'], 0.01);  // 3500 * 0.05
        $this->assertEqualsWithDelta(1500.0, $result['reserve'], 0.01);     // 50 * 30
        $this->assertEqualsWithDelta(6275.0, $result['total'], 0.01);       // 3500+1100+175+1500
    }

    public function testEstimateReturnsNullWhenNoRegression()
    {
        $config = new AircraftConfiguration();
        $config->fuel_regression_n = null;
        $config->fuel_regression_a = null;
        $config->fuel_regression_b = null;

        $this->assertNull(FuelEstimator::estimate($config, 1000, 200));
    }

    public function testEstimateReturnsNullWhenInsufficientData()
    {
        $config = new AircraftConfiguration();
        $config->fuel_regression_n = 3;
        $config->fuel_regression_a = 500.0;
        $config->fuel_regression_b = 3.0;

        $this->assertNull(FuelEstimator::estimate($config, 1000, 200));
    }
}
