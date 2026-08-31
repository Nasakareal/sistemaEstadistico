<?php

namespace App\Support;

use Illuminate\Support\Str;

class ActividadVehiculoCaptura
{
    private const UNIDAD_VIALIDADES_URBANAS = 5;

    public static function ocultaDatosResguardo($usuario, $categoria, $subcategoria): bool
    {
        if ((int) ($usuario->unidad_id ?? 0) !== self::UNIDAD_VIALIDADES_URBANAS) {
            return false;
        }

        return self::normalizarNombre($categoria->nombre ?? $categoria) === 'REVISIONES'
            && self::normalizarNombre($subcategoria->nombre ?? $subcategoria) === 'ORIENTACION PREVENTIVA';
    }

    public static function limpiarDatosResguardo(array $vehiculo): array
    {
        $vehiculo['grua_id'] = null;
        $vehiculo['grua'] = null;
        $vehiculo['corralon'] = null;
        $vehiculo['aseguradora'] = null;

        return $vehiculo;
    }

    public static function limpiarVehiculos(array $vehiculos): array
    {
        return array_map([self::class, 'limpiarDatosResguardo'], $vehiculos);
    }

    public static function normalizarCamposOpcionales(array $vehiculo): array
    {
        foreach (['modelo', 'serie'] as $campo) {
            if (array_key_exists($campo, $vehiculo) && self::esValorNoDisponible($vehiculo[$campo])) {
                $vehiculo[$campo] = null;
            }
        }

        $placas = trim((string) ($vehiculo['placas'] ?? ''));
        $servicio = self::normalizarNombre($vehiculo['tipo_servicio'] ?? '');

        if ($placas === '') {
            $vehiculo['estado_placas'] = null;
        } elseif ($servicio === 'SERVICIO PUBLICO FEDERAL') {
            $vehiculo['estado_placas'] = 'FEDERAL';
        }

        return $vehiculo;
    }

    public static function normalizarVehiculos(array $vehiculos): array
    {
        return array_map([self::class, 'normalizarCamposOpcionales'], $vehiculos);
    }

    public static function requiereEstadoPlacas(array $vehiculo): bool
    {
        return trim((string) ($vehiculo['placas'] ?? '')) !== ''
            && self::normalizarNombre($vehiculo['tipo_servicio'] ?? '') !== 'SERVICIO PUBLICO FEDERAL';
    }

    private static function esValorNoDisponible($valor): bool
    {
        $normalizado = preg_replace('/[^A-Z0-9]/', '', self::normalizarNombre($valor));

        return in_array($normalizado, [
            'X',
            'SD',
            'NA',
            'NOAPLICA',
            'SINDATO',
            'SINDATOS',
            'NODISPONIBLE',
        ], true);
    }

    private static function normalizarNombre($nombre): string
    {
        return trim(Str::ascii(mb_strtoupper((string) $nombre, 'UTF-8')));
    }
}
