<?php

namespace App\Support;

use App\Models\Actividad;
use App\Models\Grua;
use App\Models\Hechos;
use App\Models\Vehiculo;
use Illuminate\Support\Facades\DB;

class GruaEditGuard
{
    private const UNIDAD_DELEGACIONES_ID = 2;

    private const VALORES_SIN_CORRALON = [
        '',
        'N/A',
        'NA',
        'NO',
        'NO APLICA',
        'NO SE UTILIZA',
        'NO SE UTILIZO',
        'NINGUNO',
        'NULL',
        'O',
        'SIN CORRALON',
        'SIN DATO',
        'SIN GRUA',
    ];

    public static function canModifyDelegacionesGrua($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        if ((int) ($usuario->unidad_id ?? 0) === 3 && !$usuario->hasRole('Superadmin')) {
            return false;
        }

        return $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Subdirector');
    }

    public static function canViewFullGruaCatalog($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return $usuario->hasRole('Superadmin')
            || (int) ($usuario->unidad_id ?? 0) === 3
            || self::isDelegacionesManager($usuario);
    }

    private static function isDelegacionesManager($usuario): bool
    {
        return (int) ($usuario->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID
            && (
                $usuario->hasRole('Administrador')
                || $usuario->hasRole('Subdirector')
            );
    }

    public static function locksHecho($usuario, Hechos $hecho): bool
    {
        return self::isDelegacionesHecho($hecho)
            && !self::canModifyDelegacionesGrua($usuario);
    }

    public static function locksActividad($usuario, Actividad $actividad): bool
    {
        return self::isDelegacionesActividad($actividad)
            && !self::canModifyDelegacionesGrua($usuario);
    }

    public static function vehicleHasGruaData(Vehiculo $vehiculo): bool
    {
        return self::vehicleHasGrua($vehiculo)
            || self::vehicleHasCorralon($vehiculo);
    }

    public static function vehicleHasGrua(Vehiculo $vehiculo): bool
    {
        return self::currentGruaId($vehiculo) !== null
            || self::normalizeProtectedText($vehiculo->grua ?? null) !== '';
    }

    public static function vehicleHasCorralon(Vehiculo $vehiculo): bool
    {
        return self::normalizeProtectedText($vehiculo->corralon ?? null) !== '';
    }

    public static function requestedGruaMatchesCurrent(Vehiculo $vehiculo, ?int $requestedGruaId): bool
    {
        $currentGruaId = self::currentGruaId($vehiculo);

        if ($currentGruaId !== null) {
            return $currentGruaId === $requestedGruaId;
        }

        $currentGruaName = self::normalizeProtectedText($vehiculo->grua ?? null);

        if ($currentGruaName === '') {
            return true;
        }

        if ($requestedGruaId === null) {
            return false;
        }

        $requestedGruaName = Grua::whereKey($requestedGruaId)->value('nombre');

        return $currentGruaName === self::normalizeProtectedText($requestedGruaName);
    }

    public static function currentGruaId(Vehiculo $vehiculo): ?int
    {
        $vehiculoGruaId = (int) ($vehiculo->grua_id ?? 0);

        if ($vehiculoGruaId > 0) {
            return $vehiculoGruaId;
        }

        if (empty($vehiculo->id)) {
            return null;
        }

        $servicioGruaId = DB::table('servicios')
            ->where('vehiculo_id', $vehiculo->id)
            ->whereNotNull('grua_id')
            ->orderByDesc('id')
            ->value('grua_id');

        return $servicioGruaId ? (int) $servicioGruaId : null;
    }

    public static function normalizeProtectedText($value): string
    {
        $texto = mb_strtoupper(trim((string) $value), 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
        ]);
        $texto = preg_replace('/\s+/', ' ', $texto) ?? '';

        return in_array($texto, self::VALORES_SIN_CORRALON, true) ? '' : $texto;
    }

    private static function isDelegacionesHecho(Hechos $hecho): bool
    {
        $unidadId = (int) ($hecho->unidad_org_id ?? 0);

        if ($unidadId === self::UNIDAD_DELEGACIONES_ID) {
            return true;
        }

        if ($unidadId > 0) {
            return false;
        }

        $hecho->loadMissing('creator');

        return (int) optional($hecho->creator)->unidad_id === self::UNIDAD_DELEGACIONES_ID;
    }

    private static function isDelegacionesActividad(Actividad $actividad): bool
    {
        $unidadId = (int) ($actividad->unidad_org_id ?? 0);

        if ($unidadId === self::UNIDAD_DELEGACIONES_ID) {
            return true;
        }

        if ($unidadId > 0) {
            return false;
        }

        $actividad->loadMissing('creador');

        return (int) optional($actividad->creador)->unidad_id === self::UNIDAD_DELEGACIONES_ID;
    }
}
