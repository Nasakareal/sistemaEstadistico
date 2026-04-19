<?php

namespace App\Support;

use App\Models\Delegacion;
use App\Models\Hechos;

class HechoAccess
{
    private const UNIDAD_DELEGACIONES_ID = 2;
    private const UNIDAD_CARRETERAS_ID = 4;
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

        if (self::canUseHechosModule($usuario)) {
            return $permissions;
        }

        return $permissions
            ->reject(function ($permission) {
                $name = mb_strtolower(trim((string) $permission), 'UTF-8');
                return in_array($name, self::PERMISOS_HECHOS, true);
            })
            ->values();
    }

    public static function applyVisibilityScope($query, $usuario): void
    {
        if (!self::canUseHechosModule($usuario)) {
            $query->whereRaw('1 = 0');
            return;
        }

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Coordinador')
        ) {
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);

        $query->where(function ($q) use ($usuario, $unidadId, $delegacionId) {
            $q->where('created_by', $usuario->id);

            if ($unidadId === self::UNIDAD_CARRETERAS_ID) {
                $q->orWhere('unidad_org_id', self::UNIDAD_CARRETERAS_ID);
                return;
            }

            if ($unidadId === self::UNIDAD_DELEGACIONES_ID) {
                if ($delegacionId <= 0) {
                    return;
                }

                $esRegional = Delegacion::query()
                    ->where('id', $delegacionId)
                    ->whereNull('delegacion_padre_id')
                    ->exists();

                if ($usuario->hasRole('Subdirector') && $esRegional) {
                    $ids = Delegacion::query()
                        ->where('id', $delegacionId)
                        ->orWhere('delegacion_padre_id', $delegacionId)
                        ->pluck('id')
                        ->toArray();

                    $q->orWhereIn('delegacion_id', $ids);
                    return;
                }

                $q->orWhere('delegacion_id', $delegacionId);
                return;
            }

            if ($unidadId > 0) {
                $q->orWhere('unidad_org_id', $unidadId);
            }
        });
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
}
