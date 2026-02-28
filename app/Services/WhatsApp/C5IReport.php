<?php

namespace App\Services\WhatsApp;

use App\Models\Hechos;
use Illuminate\Support\Str;

class C5IReport
{
    public static function parseCoordsFromC5IText(string $text): ?array
    {
        $t = Str::upper($text);

        // Soporta:
        // "LATITUD:19.7068798 LONGITUD:-101.21537"
        // "LATITUD: 19.706... LONGITUD: -101.21..."
        $re = '/LATITUD\s*:\s*([\-]?\d+(?:\.\d+)?)\s+LONGITUD\s*:\s*([\-]?\d+(?:\.\d+)?)/i';

        if (!preg_match($re, $t, $m)) {
            return null;
        }

        $lat = is_numeric($m[1]) ? (float)$m[1] : null;
        $lng = is_numeric($m[2]) ? (float)$m[2] : null;

        if ($lat === null || $lng === null) return null;

        return ['lat' => $lat, 'lng' => $lng];
    }

    public static function googleMapsLinkFromCoords(float $lat, float $lng): string
    {
        $latTxt = self::trimZeros($lat);
        $lngTxt = self::trimZeros($lng);
        return "Google Maps: https://www.google.com/maps?q={$latTxt},{$lngTxt}";
    }

    public static function googleMapsLinkFromHecho(Hechos $hecho): ?string
    {
        if (!is_numeric($hecho->lat) || !is_numeric($hecho->lng)) return null;
        return self::googleMapsLinkFromCoords((float)$hecho->lat, (float)$hecho->lng);
    }

    private static function trimZeros(float $n): string
    {
        $s = rtrim(rtrim(number_format($n, 15, '.', ''), '0'), '.');
        return $s === '' ? '0' : $s;
    }
}
