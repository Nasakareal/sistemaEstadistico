<?php

namespace App\Support;

use Carbon\Carbon;

final class UtcDatabaseDate
{
    /**
     * Las marcas enviadas por la app se guardan como UTC en columnas MySQL sin
     * zona. El cast datetime de Eloquent les asignaría la zona local al leerlas,
     * desplazando el instante seis horas. Siempre se debe partir del valor crudo.
     */
    public static function parseRaw(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i:s', $value, 'UTC');
    }

    public static function toIso8601(?string $value): ?string
    {
        return self::parseRaw($value)?->toIso8601String();
    }
}
