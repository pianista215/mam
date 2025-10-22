<?php

namespace app\helpers;

class GeoUtils
{
    /**
     * Calculate the distance between two points on Earth.
     *
     * @param float $lat1 Latitude of point 1 in decimal degrees
     * @param float $lon1 Longitude of point 1 in decimal degrees
     * @param float $lat2 Latitude of point 2 in decimal degrees
     * @param float $lon2 Longitude of point 2 in decimal degrees
     * @param string $unit 'km' or 'nm'
     * @return float Distance in the selected unit
     */
    public static function haversine(float $lat1, float $lon1, float $lat2, float $lon2, string $unit = 'km'): float
    {
        // Haversine formula
        $earthRadiusKm = 6371.0;

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2 +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distanceKm = $earthRadiusKm * $c;

        return match (strtolower($unit)) {
            'km' => $distanceKm,
            'nm' => $distanceKm * 0.539957,
            default => throw new \InvalidArgumentException("Invalid unit '$unit'. Use 'km' or 'nm'."),
        };
    }

    public static function sqlHaversine(): \yii\db\Expression
    {
        $earthRadius = 6371;
        return new \yii\db\Expression("
                                   (
                                       $earthRadius * ACOS(
                                           COS(RADIANS(:latitude)) * COS(RADIANS(latitude)) *
                                           COS(RADIANS(longitude) - RADIANS(:longitude)) +
                                           SIN(RADIANS(:latitude)) * SIN(RADIANS(latitude))
                                       )
                                   ) AS distance
                               ");
    }
}
