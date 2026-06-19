<?php

namespace App\Support;

use Illuminate\Support\Str;

class LicenciaTipoCatalog
{
    public const TIPOS = [
        'SERVICIO_PUBLICO' => 'Servicio público',
        'AUTOMOVILISTA' => 'Automovilista',
        'CHOFER' => 'Chofer',
        'MOTOCICLISTA' => 'Motociclista',
        'PERMISO' => 'Permiso',
    ];

    private const ALIASES = [
        'SERVICIOPUBLICO' => 'SERVICIO_PUBLICO',
        'PUBLICO' => 'SERVICIO_PUBLICO',
        'AUTOMOVILISTA' => 'AUTOMOVILISTA',
        'PARTICULAR' => 'AUTOMOVILISTA',
        'A' => 'AUTOMOVILISTA',
        'CHOFER' => 'CHOFER',
        'OPERADOR' => 'CHOFER',
        'B' => 'CHOFER',
        'MOTOCICLISTA' => 'MOTOCICLISTA',
        'MOTOCICLETA' => 'MOTOCICLISTA',
        'MOTO' => 'MOTOCICLISTA',
        'C' => 'MOTOCICLISTA',
        'PERMISO' => 'PERMISO',
    ];

    public static function all(): array
    {
        return self::TIPOS;
    }

    public static function keys(): array
    {
        return array_keys(self::TIPOS);
    }

    public static function normalize($value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        if (array_key_exists($text, self::TIPOS)) {
            return $text;
        }

        $key = preg_replace('/[^A-Z0-9]+/', '', Str::ascii(mb_strtoupper($text, 'UTF-8')));

        return self::ALIASES[$key] ?? null;
    }

    public static function requestValue($value): ?string
    {
        $normalized = self::normalize($value);

        if ($normalized) {
            return $normalized;
        }

        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }

    public static function label($value): string
    {
        $normalized = self::normalize($value);

        return $normalized ? self::TIPOS[$normalized] : trim((string) $value);
    }
}
