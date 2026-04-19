<?php

namespace App\Support;

use App\Models\Delegacion;
use App\Models\Hechos;

class HechoAccess
{
    private const UNIDAD_DELEGACIONES_ID = 2;
    private const UNIDAD_SEGURIDAD_VIAL_ID = 3;
    private const UNIDAD_CARRETERAS_ID = 4;
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;
    private const UNIDADES_SIN_HECHOS = [5, 6];

    private const PERMISOS_HECHOS = [
        'ver busqueda',
        'ver hechos',
        'crear hechos',
        'editar hechos',
        'eliminar hechos',
        'ver vehiculos',
        'crear vehiculos',
        'editar vehiculos',
        'eliminar vehiculos',
        'ver lesionados',
        'crear lesionados',
        'editar lesionados',
        'eliminar lesionados',
    ];

    private const PERMISOS_CARRETERAS = [
        'ver operativos carreteras',
        'crear operativos carreteras',
        'editar operativos carreteras',
        'eliminar operativos carreteras',
        'ver estadisticas carreteras',
    ];

    private const PERMISOS_VIALIDADES_URBANAS = [
        'ver operativos vialidades',
        'crear operativos vialidades',
        'editar operativos vialidades',
        'eliminar operativos vialidades',
    ];

    public static function canUseHechosModule($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        if ($usuario->hasRole('Superadmin')) {
            return true;
        }

        return !in_array((int) ($usuario->unidad_id ?? 0), self::UNIDADES_SIN_HECHOS, true);
    }

    public static function filterPermissionsForUser($permissions, $usuario)
    {
        $permissions = collect($permissions)->values();

        if (!$usuario || $usuario->hasRole('Superadmin')) {
            return $permissions;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        return $permissions->reject(function ($permission) use ($unidadId, $usuario) {
            $name = mb_strtolower(trim((string) $permission), 'UTF-8');

            if (!self::canUseHechosModule($usuario) && in_array($name, self::PERMISOS_HECHOS, true)) {
                return true;
            }

            if (
                in_array($name, self::PERMISOS_CARRETERAS, true)
                && !in_array($unidadId, [self::UNIDAD_SEGURIDAD_VIAL_ID, self::UNIDAD_CARRETERAS_ID], true)
            ) {
                return true;
            }

            if (
                in_array($name, self::PERMISOS_VIALIDADES_URBANAS, true)
                && !in_array($unidadId, [self::UNIDAD_SEGURIDAD_VIAL_ID, self::UNIDAD_VIALIDADES_URBANAS_ID], true)
            ) {
                return true;
            }

            return false;
        })->values();
    }

    public static function applyVisibilityScope($query, $usuario): void
    {
        if (!self::canUseHechosModule($usuario)) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($usuario->hasRole('Superadmin') || (int) ($usuario->unidad_id ?? 0) === self::UNIDAD_SEGURIDAD_VIAL_ID) {
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);

        if ($unidadId === self::UNIDAD_DELEGACIONES_ID) {
            self::applyUnidadScope($query, self::UNIDAD_DELEGACIONES_ID);

            if (self::esRolAdministrativoUnidad($usuario)) {
                return;
            }

            if ($delegacionId <= 0) {
                $query->whereRaw('1 = 0');
                return;
            }

            $ids = self::delegacionIdsVisibles($usuario, $delegacionId);
            $query->whereIn('delegacion_id', $ids);
            return;
        }

        if ($unidadId > 0) {
            self::applyUnidadScope($query, $unidadId);
            return;
        }

        $query->whereRaw('1 = 0');
    }

    public static function canView($usuario, Hechos $hecho): bool
    {
        if (!self::canUseHechosModule($usuario)) {
            return false;
        }

        $query = Hechos::query()->whereKey($hecho->id);
        self::applyVisibilityScope($query, $usuario);

        return $query->exists();
    }

    public static function canEdit($usuario, Hechos $hecho): bool
    {
        if (!self::canUseHechosModule($usuario)) {
            return false;
        }

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Administrativo')
            || $usuario->hasRole('Subdirector')
        ) {
            return self::canView($usuario, $hecho);
        }

        if ((int) $usuario->id !== (int) ($hecho->created_by ?? 0)) {
            return false;
        }

        return self::canView($usuario, $hecho);
    }

    private static function puedeVerDelegacionesHijas($usuario): bool
    {
        return $usuario->hasRole('Delegado');
    }

    private static function esRolAdministrativoUnidad($usuario): bool
    {
        return $usuario->hasRole('Administrador')
            || $usuario->hasRole('Administrativo')
            || $usuario->hasRole('Subdirector');
    }

    private static function applyUnidadScope($query, int $unidadId): void
    {
        $query->where(function ($q) use ($unidadId) {
            $q->where('unidad_org_id', $unidadId)
                ->orWhere(function ($legacy) use ($unidadId) {
                    $legacy->whereNull('unidad_org_id')
                        ->whereHas('creator', function ($creator) use ($unidadId) {
                            $creator->where('unidad_id', $unidadId);
                        });
                });
        });
    }

    private static function delegacionIdsVisibles($usuario, int $delegacionId): array
    {
        if (!self::puedeVerDelegacionesHijas($usuario)) {
            return [$delegacionId];
        }

        $esRegional = Delegacion::query()
            ->where('id', $delegacionId)
            ->whereNull('delegacion_padre_id')
            ->exists();

        if (!$esRegional) {
            return [$delegacionId];
        }

        return Delegacion::query()
            ->where('id', $delegacionId)
            ->orWhere('delegacion_padre_id', $delegacionId)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();
    }
}
