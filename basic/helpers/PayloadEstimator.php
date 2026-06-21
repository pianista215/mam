<?php

namespace app\helpers;

use app\models\AircraftConfiguration;

class PayloadEstimator
{
    /**
     * Generates a randomised but realistic passenger and cargo load for a flight.
     *
     * @param AircraftConfiguration $config
     * @param float|null $estimatedFuelKg Total fuel in kg from FuelEstimator, or null when no regression is available
     * @param float $adultWeightKg Weight per adult passenger in kg
     * @param float $childWeightKg Weight per child passenger in kg
     * @param float $baggageWeightKg Weight per checked bag in kg
     * @param \DateTimeInterface $flightDate Used to vary occupancy by weekday
     * @return array ['pax_adults', 'pax_children', 'cargo_bags', 'cargo_paid_kg']
     */
    public static function generate(
        AircraftConfiguration $config,
        ?float $estimatedFuelKg,
        float $adultWeightKg,
        float $childWeightKg,
        float $baggageWeightKg,
        \DateTimeInterface $flightDate
    ): array {
        $crewWeight = $config->crew * $adultWeightKg;

        if ($estimatedFuelKg === null) {
            // Conservative fallback when no fuel regression is available
            $availablePayload = max(0.0, ($config->mtow - $config->oew - $crewWeight) * 0.5);
        } else {
            $availablePayload = max(0.0, $config->mtow - $config->oew - $estimatedFuelKg - $crewWeight);
        }

        if ($config->pax_capacity == 0) {
            return self::cargoOnly($availablePayload, $config->cargo_capacity);
        }

        return self::paxAndCargo($config, $availablePayload, $adultWeightKg, $childWeightKg, $baggageWeightKg, $flightDate);
    }

    private static function cargoOnly(float $availablePayload, int $cargoCapacity): array
    {
        $maxCargo  = min($availablePayload, $cargoCapacity);
        $fillPct   = mt_rand(60, 90) / 100;
        return [
            'pax_adults'    => 0,
            'pax_children'  => 0,
            'cargo_bags'    => 0,
            'cargo_paid_kg' => (int) round($maxCargo * $fillPct),
        ];
    }

    private static function paxAndCargo(
        AircraftConfiguration $config,
        float $availablePayload,
        float $adultWeightKg,
        float $childWeightKg,
        float $baggageWeightKg,
        \DateTimeInterface $flightDate
    ): array {
        // Weekday occupancy: Mon(1), Fri(5), Sat(6), Sun(7) are high-traffic days
        $weekday     = (int) $flightDate->format('N');
        $highTraffic = in_array($weekday, [1, 5, 6, 7], true);
        $minOcc      = $highTraffic ? 50 : 40;
        $maxOcc      = $highTraffic ? 85 : 80;
        $occupancy   = mt_rand($minOcc, $maxOcc) / 100;
        $paxTotal    = min((int) round($config->pax_capacity * $occupancy), $config->pax_capacity);

        $childFactor = mt_rand(0, 10) / 100;
        $children    = (int) round($paxTotal * $childFactor);
        $adults      = $paxTotal - $children;
        $paxWeight   = $adults * $adultWeightKg + $children * $childWeightKg;

        // Safety guard: scale down pax if payload budget is too tight
        if ($paxWeight > $availablePayload && $config->pax_capacity > 0) {
            $scale    = $availablePayload / ($config->pax_capacity * $adultWeightKg);
            $paxTotal = max(0, (int) round($config->pax_capacity * $scale * $occupancy));
            $children = (int) round($paxTotal * $childFactor);
            $adults   = $paxTotal - $children;
            $paxWeight = $adults * $adultWeightKg + $children * $childWeightKg;
        }

        $bags            = (int) round($paxTotal * mt_rand(20, 35) / 100);
        $bagsWeight      = $bags * $baggageWeightKg;
        $remainingCargo  = max(0.0, $availablePayload - $paxWeight);
        $cargoLimit      = min($remainingCargo, (float) $config->cargo_capacity);
        $paidCargoSpace  = max(0.0, $cargoLimit - $bagsWeight);
        // Never fill paid cargo entirely to the limit (0–90%)
        $paidCargo       = (int) round($paidCargoSpace * mt_rand(0, 90) / 100);

        return [
            'pax_adults'    => $adults,
            'pax_children'  => $children,
            'cargo_bags'    => $bags,
            'cargo_paid_kg' => $paidCargo,
        ];
    }
}
