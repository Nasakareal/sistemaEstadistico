<?php

namespace App\Helpers;

class StreetNormalizer
{
    public static function normalize(?string $street): ?string
    {
        if (!$street) return null;

        $street = preg_replace('/\([^)]*\)/', '', $street);
        $street = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $street);
        $street = strtoupper($street);
        $street = str_replace(['.', ',', ';'], '', $street);
        $replacements = [
            'AVENIDA' => '',
            'AV ' => '',
            'AV.' => '',
            'BLVD' => '',
            'BOULEVARD' => '',
            'CALZADA' => '',
            'CALZ ' => '',
            'PERIFERICO' => 'PERIFERICO',
            'PASEO DE LA' => 'PASEO',
            'PASEO LA' => 'PASEO',
        ];

        foreach ($replacements as $search => $replace) {
            $street = str_replace($search, $replace, $street);
        }

        $street = preg_replace('/\s+/', ' ', $street);

        return trim($street);
    }
}
