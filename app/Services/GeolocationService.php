<?php

namespace App\Services;

class GeolocationService
{
    private const EARTH_RADIUS_M = 6371000;

    public function distanceMeters(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2,
    ): float {
        $lat1 = deg2rad($latitude1);
        $lat2 = deg2rad($latitude2);

        $deltaLat = deg2rad(
            $latitude2 - $latitude1
        );

        $deltaLng = deg2rad(
            $longitude2 - $longitude1
        );

        $a =
            sin($deltaLat / 2) ** 2
            +
            cos($lat1)
            *
            cos($lat2)
            *
            sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(
            sqrt($a),
            sqrt(1 - $a)
        );

        return self::EARTH_RADIUS_M * $c;
    }
}
