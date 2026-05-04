<?php

namespace App\Support;

class HechoLocationGuard
{
    public const OFFICE_LAT = 19.6808588;
    public const OFFICE_LNG = -101.2339535;
    public const OFFICE_BLOCK_RADIUS_METERS = 50.0;
    public const OFFICE_MESSAGE = 'El hecho debe ser capturado en el lugar donde se suscitó.';

    public static function isBlockedOfficeLocation($lat, $lng): bool
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            return false;
        }

        return self::distanceMeters(
            (float) $lat,
            (float) $lng,
            self::OFFICE_LAT,
            self::OFFICE_LNG
        ) <= self::OFFICE_BLOCK_RADIUS_METERS;
    }

    public static function distanceMeters(float $latA, float $lngA, float $latB, float $lngB): float
    {
        $earthRadiusMeters = 6371000.0;

        $latDelta = deg2rad($latB - $latA);
        $lngDelta = deg2rad($lngB - $lngA);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latA)) * cos(deg2rad($latB)) * sin($lngDelta / 2) ** 2;
        $a = min(1.0, max(0.0, $a));

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
