<?php

namespace app\helpers;

use app\models\AircraftConfiguration;

class FuelEstimator
{
    /**
     * Calculates linear regression parameters (fuel = a + b * distance) from historical flight data.
     *
     * @param array $flights Array of ['distance_nm' => float, 'total_fuel_burn_kg' => float, 'flight_time_minutes' => float]
     * @return array|null ['a', 'b', 'n', 'avg_kg_per_min'] or null if insufficient/invalid data
     */
    public static function calculateRegression(array $flights): ?array
    {
        // Filter 1: sanity hard floor
        $flights = array_values(array_filter($flights, function ($f) {
            return $f['flight_time_minutes'] >= 20
                && $f['total_fuel_burn_kg'] > 0
                && $f['distance_nm'] > 0;
        }));

        if (count($flights) < 5) {
            return null;
        }

        // Filter 2: statistical outlier removal by fuel/distance ratio
        $ratios = array_map(fn($f) => $f['total_fuel_burn_kg'] / $f['distance_nm'], $flights);
        $mean = array_sum($ratios) / count($ratios);

        $variance = array_sum(array_map(fn($r) => ($r - $mean) ** 2, $ratios)) / count($ratios);
        $stddev = sqrt($variance);

        $flights = array_values(array_filter($flights, function ($f, $i) use ($ratios, $mean, $stddev) {
            return abs($ratios[$i] - $mean) <= 2 * $stddev;
        }, ARRAY_FILTER_USE_BOTH));

        if (count($flights) < 5) {
            return null;
        }

        $n = count($flights);
        $distances = array_column($flights, 'distance_nm');
        $fuels = array_column($flights, 'total_fuel_burn_kg');
        $times = array_column($flights, 'flight_time_minutes');

        $xMean = array_sum($distances) / $n;
        $yMean = array_sum($fuels) / $n;

        $numerator = 0.0;
        $denominator = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dx = $distances[$i] - $xMean;
            $numerator += $dx * ($fuels[$i] - $yMean);
            $denominator += $dx * $dx;
        }

        if ($denominator == 0) {
            return null;
        }

        $b = $numerator / $denominator;
        $a = $yMean - $b * $xMean;

        if ($b <= 0 || $a < 0) {
            return null;
        }

        $avgKgPerMin = array_sum(array_map(
            fn($f, $t) => $f / $t,
            $fuels,
            $times
        )) / $n;

        return [
            'a' => $a,
            'b' => $b,
            'n' => $n,
            'avg_kg_per_min' => $avgKgPerMin,
        ];
    }

    /**
     * Estimates fuel breakdown for a new flight using the aircraft configuration's regression.
     *
     * Returns null when the configuration lacks a valid regression (n < 5 or a/b null),
     * signalling the caller to apply a static safe payload allocation instead.
     *
     * @param AircraftConfiguration $config
     * @param float $distanceNm Flight distance in nautical miles
     * @param float $alternateDistanceNm Destination-to-alternate distance in nautical miles
     * @param int $reserveMinutes Reserve fuel in minutes (default 30)
     * @return array|null ['trip', 'alternate', 'contingency', 'reserve', 'total'] in kg, or null
     */
    public static function estimate(
        AircraftConfiguration $config,
        float $distanceNm,
        float $alternateDistanceNm,
        int $reserveMinutes = 30
    ): ?array {
        if ($config->fuel_regression_n === null || $config->fuel_regression_n < 5
            || $config->fuel_regression_a === null || $config->fuel_regression_b === null) {
            return null;
        }

        $a = (float) $config->fuel_regression_a;
        $b = (float) $config->fuel_regression_b;
        $avgKgPerMin = (float) $config->fuel_avg_kg_per_min;

        $trip = $a + $b * $distanceNm;
        $alternate = $a + $b * $alternateDistanceNm;
        $contingency = $trip * 0.05;
        $reserve = $avgKgPerMin * $reserveMinutes;
        $total = $trip + $alternate + $contingency + $reserve;

        return [
            'trip' => $trip,
            'alternate' => $alternate,
            'contingency' => $contingency,
            'reserve' => $reserve,
            'total' => $total,
        ];
    }
}
