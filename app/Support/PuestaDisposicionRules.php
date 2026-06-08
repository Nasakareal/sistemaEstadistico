<?php

namespace App\Support;

class PuestaDisposicionRules
{
    public const UNIDAD_DELEGACIONES_ID = 2;
    public const MOTIVO_OTRO = 'OTRO';

    public static function motivosCatalogo(): array
    {
        return [
            'PERSONA DETENIDA',
            'FALTA ADMINISTRATIVA',
            'ALTERAR EL ORDEN PUBLICO',
            'AGRESIONES',
            'AMENAZAS',
            'VIOLENCIA FAMILIAR',
            'POSESION DE SUSTANCIAS PROHIBIDAS',
            'POSESION DE ARMA DE FUEGO',
            'POSESION DE ARMA BLANCA',
            'ROBO',
            'ROBO A COMERCIO',
            'ROBO A CASA HABITACION',
            'ROBO DE VEHICULO',
            'VEHICULO RECUPERADO',
            'VEHICULO CON REPORTE DE ROBO',
            'VEHICULO ABANDONADO',
            'VEHICULO ALTERADO',
            'DAÑOS',
            'LESIONES',
            'OBJETO ASEGURADO',
            'MERCANCIA ASEGURADA',
            'MANDAMIENTO JUDICIAL',
            'ORDEN DE APREHENSION',
            'HECHO DE TRANSITO',
            'HECHO DE TRANSITO TURNADO',
            self::MOTIVO_OTRO,
        ];
    }

    public static function motivoEsHechoTransito($motivo): bool
    {
        $texto = trim((string) $motivo);

        if ($texto === '') {
            return false;
        }

        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
        ]);

        return str_contains(strtoupper($texto), 'HECHO DE TRANSITO');
    }

    public static function requiereHechoVinculadoDelegaciones(?int $unidadId, $motivo, bool $tieneHechoVinculado): bool
    {
        return !$tieneHechoVinculado
            && (int) $unidadId === self::UNIDAD_DELEGACIONES_ID
            && self::motivoEsHechoTransito($motivo);
    }
}
