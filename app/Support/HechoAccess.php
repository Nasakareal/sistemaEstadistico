<?php

namespace App\Support;

use App\Models\Delegacion;
use App\Models\Hechos;

class HechoAccess
{
    private const UNIDAD_SINIESTROS_ID = 1;
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

    private const PERMISOS_SINIESTROS_CAPTURA_PROPIA = [
        'ver busqueda',
        'ver hechos',
        'editar hechos',
        'ver vehiculos',
        'crear vehiculos',
        'editar vehiculos',
        'eliminar vehiculos',
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

    public static function shouldResolvePermissionDirectly(?string $ability): bool
    {
        $name = self::normalizePermissionName($ability);

        if ($name === '') {
            return false;
        }

        return in_array($name, self::PERMISOS_HECHOS, true);
    }

    public static function hasAssignedPermission($usuario, ?string $permission): bool
    {
        if (!$usuario) {
            return false;
        }

        $target = self::normalizePermissionName($permission);
        if ($target === '') {
            return false;
        }

        if (self::hasImplicitSiniestrosPermission($usuario, $target)) {
            return true;
        }

        if ($target === 'crear hechos' && self::isDelegacionesAdministrativeCreator($usuario)) {
            return true;
        }

        $usuario->loadMissing(['roles.permissions', 'permissions']);

        if ($usuario->permissions->contains(fn ($permiso) => self::normalizePermissionName($permiso->name ?? null) === $target)) {
            return true;
        }

        return $usuario->roles->contains(function ($role) use ($target) {
            return $role->permissions->contains(
                fn ($permiso) => self::normalizePermissionName($permiso->name ?? null) === $target
            );
        });
    }

    public static function canUseHechosModule($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        if ($usuario->hasRole('Superadmin')) {
            return true;
        }

        return !in_array(self::effectiveUnidadId($usuario), self::UNIDADES_SIN_HECHOS, true);
    }

    public static function effectiveUnidadId($usuario): int
    {
        if (!$usuario) {
            return 0;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        return $unidadId > 0 ? $unidadId : self::UNIDAD_SINIESTROS_ID;
    }

    public static function effectiveUnidadIdForHecho(Hechos $hecho): int
    {
        $unidadId = (int) ($hecho->unidad_org_id ?: ($hecho->creator->unidad_id ?? 0));

        return $unidadId > 0 ? $unidadId : self::UNIDAD_SINIESTROS_ID;
    }

    public static function filterPermissionsForUser($permissions, $usuario)
    {
        $permissions = collect($permissions)->values();

        if ($usuario && self::isSiniestrosOperationalUser($usuario) && self::canUseHechosModule($usuario)) {
            $permissions = $permissions
                ->merge(self::PERMISOS_SINIESTROS_CAPTURA_PROPIA)
                ->unique(function ($permission) {
                    return mb_strtolower(trim((string) $permission), 'UTF-8');
                })
                ->values();
        }

        if (!$usuario || $usuario->hasRole('Superadmin')) {
            return $permissions;
        }

        $unidadId = self::effectiveUnidadId($usuario);

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

    private static function hasImplicitSiniestrosPermission($usuario, string $permission): bool
    {
        return self::isSiniestrosOperationalUser($usuario)
            && in_array($permission, self::PERMISOS_SINIESTROS_CAPTURA_PROPIA, true);
    }

    private static function isDelegacionesAdministrativeCreator($usuario): bool
    {
        return self::effectiveUnidadId($usuario) === self::UNIDAD_DELEGACIONES_ID
            && $usuario->hasRole('Administrativo');
    }

    private static function isSiniestrosOperationalUser($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return self::effectiveUnidadId($usuario) === self::UNIDAD_SINIESTROS_ID
            || $usuario->hasAnyRole(['Perito', 'Jefe de Grupo']);
    }

    public static function applyVisibilityScope($query, $usuario): void
    {
        if (!self::canUseHechosModule($usuario)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $unidadId = self::effectiveUnidadId($usuario);

        if ($usuario->hasRole('Superadmin') || $unidadId === self::UNIDAD_SEGURIDAD_VIAL_ID) {
            return;
        }

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

        if (!self::canView($usuario, $hecho)) {
            return false;
        }

        if ($usuario->hasRole('Superadmin')) {
            return true;
        }

        if (self::effectiveUnidadId($usuario) === self::UNIDAD_SEGURIDAD_VIAL_ID) {
            return false;
        }

        $unidadId = self::effectiveUnidadId($usuario);

        if ($unidadId === self::UNIDAD_DELEGACIONES_ID) {
            if ($usuario->hasRole('Administrador') || $usuario->hasRole('Subdirector')) {
                return true;
            }

            if ($usuario->hasRole('Administrativo') || $usuario->hasRole('Delegado')) {
                $delegacionId = (int) ($usuario->delegacion_id ?? 0);
                $hechoDelegacionId = (int) ($hecho->delegacion_id ?? 0);

                return $delegacionId > 0
                    && $hechoDelegacionId > 0
                    && in_array($hechoDelegacionId, self::delegacionIdsVisibles($usuario, $delegacionId), true);
            }

            return (int) $usuario->id === (int) ($hecho->created_by ?? 0);
        }

        if ($unidadId === 1) {
            if (self::isSiniestrosSubdirector($usuario)
                && self::effectiveUnidadIdForHecho($hecho) === self::UNIDAD_DELEGACIONES_ID) {
                return false;
            }

            if (
                $usuario->hasRole('Administrador')
                || $usuario->hasRole('Administrativo')
                || $usuario->hasRole('Jefe de Grupo')
                || $usuario->hasRole('Subdirector')
            ) {
                return true;
            }

            if ($usuario->hasRole('Perito')) {
                if ((int) $usuario->id === (int) ($hecho->created_by ?? 0)) {
                    return true;
                }

                $nombreUsuario = strtoupper(self::removeAccents(trim((string) ($usuario->name ?? ''))));
                $nombrePeritoHecho = strtoupper(self::removeAccents(trim((string) ($hecho->perito ?? ''))));

                return $nombreUsuario !== '' && $nombreUsuario === $nombrePeritoHecho;
            }

            return (int) $usuario->id === (int) ($hecho->created_by ?? 0);
        }

        if ($unidadId === self::UNIDAD_CARRETERAS_ID) {
            if ($usuario->hasRole('Administrador') || $usuario->hasRole('Subdirector')) {
                return true;
            }

            return (int) $usuario->id === (int) ($hecho->created_by ?? 0);
        }

        if ($usuario->hasRole('Administrador') || $usuario->hasRole('Subdirector')) {
            return true;
        }

        return (int) $usuario->id === (int) ($hecho->created_by ?? 0);
    }

    public static function canManageTotalesEsperados($usuario, ?Hechos $hecho = null): bool
    {
        if (!$usuario) {
            return false;
        }

        if ($usuario->hasRole('Superadmin')) {
            return true;
        }

        if (self::effectiveUnidadId($usuario) === self::UNIDAD_SEGURIDAD_VIAL_ID) {
            return false;
        }

        if (!$usuario->hasRole('Administrador') && !$usuario->hasRole('Subdirector')) {
            return false;
        }

        if (self::isSiniestrosSubdirector($usuario) && $hecho !== null) {
            return false;
        }

        $unidadId = $hecho
            ? self::effectiveUnidadIdForHecho($hecho)
            : self::effectiveUnidadId($usuario);

        return $unidadId === self::UNIDAD_DELEGACIONES_ID;
    }

    private static function puedeVerDelegacionesHijas($usuario): bool
    {
        return $usuario->hasAnyRole(['Delegado', 'Administrativo']);
    }

    private static function esRolAdministrativoUnidad($usuario): bool
    {
        return $usuario->hasRole('Administrador')
            || $usuario->hasRole('Subdirector');
    }

    private static function isSiniestrosSubdirector($usuario): bool
    {
        return $usuario
            && self::effectiveUnidadId($usuario) === self::UNIDAD_SINIESTROS_ID
            && $usuario->hasRole('Subdirector');
    }

    public static function applyUnidadScope($query, int $unidadId): void
    {
        $query->where(function ($q) use ($unidadId) {
            $q->where('unidad_org_id', $unidadId)
                ->orWhere(function ($legacy) use ($unidadId) {
                    $legacy->whereNull('unidad_org_id')
                        ->where(function ($creatorScope) use ($unidadId) {
                            $creatorScope->whereHas('creator', function ($creator) use ($unidadId) {
                                $creator->where('unidad_id', $unidadId);
                            });

                            if ($unidadId === self::UNIDAD_SINIESTROS_ID) {
                                $creatorScope
                                    ->orWhereDoesntHave('creator')
                                    ->orWhereHas('creator', function ($creator) {
                                        $creator->whereNull('unidad_id');
                                    });
                            }
                        });
                });
        });
    }

    private static function delegacionIdsVisibles($usuario, int $delegacionId): array
    {
        $delegacionBase = Delegacion::query()
            ->where('id', $delegacionId)
            ->where('activa', 1)
            ->first(['id', 'delegacion_padre_id']);

        if (!$delegacionBase) {
            return [];
        }

        if (!self::puedeVerDelegacionesHijas($usuario)) {
            return [$delegacionId];
        }

        $idsEspeciales = self::delegacionIdsMoreliaAdministradaPorPatzcuaro($delegacionId);

        if (!empty($idsEspeciales)) {
            return $idsEspeciales;
        }

        if (!is_null($delegacionBase->delegacion_padre_id)) {
            return [$delegacionId];
        }

        return Delegacion::query()
            ->where('activa', 1)
            ->where(function ($query) use ($delegacionId) {
                $query->where('id', $delegacionId)
                    ->orWhere('delegacion_padre_id', $delegacionId);
            })
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();
    }

    public static function delegacionIdsVisiblesParaUsuario($usuario): array
    {
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);

        if ($delegacionId <= 0) {
            return [];
        }

        return self::delegacionIdsVisibles($usuario, $delegacionId);
    }

    private static function delegacionIdsMoreliaAdministradaPorPatzcuaro(int $delegacionId): array
    {
        $delegacion = Delegacion::query()
            ->select(['id', 'clave', 'nombre', 'municipio', 'delegacion_padre_id'])
            ->where('activa', 1)
            ->find($delegacionId);

        if (!$delegacion || !str_contains(self::textoNormalizadoDelegacion($delegacion), 'PATZCUARO')) {
            return [];
        }

        $morelia = self::delegacionRegionalMorelia();

        if (!$morelia) {
            return [];
        }

        if ((int) ($delegacion->delegacion_padre_id ?? 0) !== (int) $morelia->id) {
            return [];
        }

        return Delegacion::query()
            ->where('activa', 1)
            ->where(function ($query) use ($morelia) {
                $query->where('id', $morelia->id)
                    ->orWhere('delegacion_padre_id', $morelia->id);
            })
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();
    }

    private static function delegacionRegionalMorelia(): ?Delegacion
    {
        return Delegacion::query()
            ->whereNull('delegacion_padre_id')
            ->where('activa', 1)
            ->get(['id', 'clave', 'nombre', 'municipio', 'delegacion_padre_id'])
            ->first(function ($delegacion) {
                $texto = self::textoNormalizadoDelegacion($delegacion);

                return str_contains($texto, 'MORELIA') && !str_contains($texto, 'PATZCUARO');
            });
    }

    private static function textoNormalizadoDelegacion(Delegacion $delegacion): string
    {
        return strtoupper(self::removeAccents(trim(implode(' ', array_filter([
            $delegacion->clave ?? null,
            $delegacion->nombre ?? null,
            $delegacion->municipio ?? null,
        ])))));
    }

    private static function removeAccents(string $string): string
    {
        $unwanted_array = [
            'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U',
            'À'=>'A','È'=>'E','Ì'=>'I','Ò'=>'O','Ù'=>'U',
            'Â'=>'A','Ê'=>'E','Î'=>'I','Ô'=>'O','Û'=>'U',
            'Ä'=>'A','Ë'=>'E','Ï'=>'I','Ö'=>'O','Ü'=>'U',
            'á'=>'A','é'=>'E','í'=>'I','ó'=>'O','ú'=>'U',
            'à'=>'A','è'=>'E','ì'=>'I','ò'=>'O','ù'=>'U',
            'â'=>'A','ê'=>'E','î'=>'I','ô'=>'O','û'=>'U',
            'ä'=>'A','ë'=>'A','ï'=>'I','ö'=>'O','ü'=>'U',
            'Ñ'=>'N','ñ'=>'N','Ç'=>'C','ç'=>'C'
        ];

        return strtr($string, $unwanted_array);
    }

    private static function normalizePermissionName(?string $permission): string
    {
        return mb_strtolower(trim((string) $permission), 'UTF-8');
    }
}
