<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MapaPatrullasAccess
{
    public const UNIDAD_SINIESTROS_ID = 1;

    private const GROUP_LEAD_ROLE_NAMES = [
        'Jefe de Grupo',
        'Jefe Grupo',
        'Encargado de Grupo',
        'Encargado Grupo',
    ];

    private const PERITO_ROLE_NAMES = [
        'Perito',
        'perito',
    ];

    public static function isSiniestrosGroupLead(User $user): bool
    {
        if ($user->hasRole('Superadmin')) {
            return false;
        }

        return self::effectiveUnidadId($user) === self::UNIDAD_SINIESTROS_ID
            && self::hasAnyRoleNormalized($user, self::GROUP_LEAD_ROLE_NAMES);
    }

    public static function applySiniestrosGroupLeadScope(
        Builder $query,
        User $actor,
        string $userTable = 'users'
    ): Builder {
        if (!self::isSiniestrosGroupLead($actor)) {
            return $query;
        }

        if (empty($actor->turno_id)) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where($userTable . '.turno_id', $actor->turno_id)
            ->whereHas('roles', function (Builder $roles) {
                $roles->whereIn('name', self::PERITO_ROLE_NAMES);
            });
    }

    public static function applyPeritoScope(Builder $query): Builder
    {
        return $query->whereHas('roles', function (Builder $roles) {
            $roles->whereIn('name', self::PERITO_ROLE_NAMES);
        });
    }

    public static function canManageScopedUser(User $actor, User $target): bool
    {
        if (!self::isSiniestrosGroupLead($actor)) {
            return true;
        }

        if (empty($actor->turno_id)) {
            return false;
        }

        if ((int) $target->turno_id !== (int) $actor->turno_id) {
            return false;
        }

        return self::hasAnyRoleNormalized($target, self::PERITO_ROLE_NAMES);
    }

    public static function groupLeadRoleNames(): array
    {
        return self::GROUP_LEAD_ROLE_NAMES;
    }

    private static function effectiveUnidadId(User $user): int
    {
        $unidadId = (int) ($user->unidad_id ?? 0);

        return $unidadId > 0 ? $unidadId : self::UNIDAD_SINIESTROS_ID;
    }

    private static function hasAnyRoleNormalized(User $user, array $roleNames): bool
    {
        $targets = array_map([self::class, 'normalizeRoleName'], $roleNames);

        $user->loadMissing('roles');

        foreach ($user->roles as $role) {
            if (in_array(self::normalizeRoleName($role->name ?? ''), $targets, true)) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeRoleName(?string $name): string
    {
        $text = mb_strtoupper(trim((string) $name), 'UTF-8');
        $text = strtr($text, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ñ' => 'N',
        ]);
        $text = preg_replace('/\s+/', ' ', $text) ?: '';

        return trim($text);
    }
}
