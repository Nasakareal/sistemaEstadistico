<?php

namespace App\Support;

final class TelefonoMexico
{
    public static function normalize($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) $value) ?: '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return substr($digits, 3);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            return substr($digits, 2);
        }

        if (
            strlen($digits) === 13
            && (str_starts_with($digits, '044') || str_starts_with($digits, '045'))
        ) {
            return substr($digits, 3);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '01')) {
            return substr($digits, 2);
        }

        return $digits;
    }
}
