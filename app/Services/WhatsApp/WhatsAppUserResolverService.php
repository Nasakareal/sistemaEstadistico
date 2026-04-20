<?php

namespace App\Services\WhatsApp;

use App\Models\User;

class WhatsAppUserResolverService
{
    public function findAuthorizedUserByPhone(string $from): ?User
    {
        $telefono = $this->normalizePhone($from);

        if ($telefono === '') {
            return null;
        }

        return User::query()
            ->with(['unidad', 'roles'])
            ->where('telefono', $telefono)
            ->first();
    }

    public function resolveContext(User $user): array
    {
        $unidadSlug = optional($user->unidad)->slug;
        $unidadId = $user->unidad_id ? (int) $user->unidad_id : null;
        $module = $this->mapUnidadIdToModule($unidadId) ?: $this->mapUnidadSlugToModule($unidadSlug);

        if ($user->hasRole('Superadmin')) {
            return [
                'acceso_total' => true,
                'modules' => [
                    'siniestros',
                    'delegaciones',
                    'coordinacion',
                    'carreteras',
                    'vialidades',
                ],
                'default_module' => null,
                'solo_propios' => false,
                'unidad_slug' => $unidadSlug,
                'unidad_id' => $unidadId,
            ];
        }

        if ($user->hasRole('Coordinador') && $unidadId === 3) {
            return [
                'acceso_total' => true,
                'modules' => [
                    'siniestros',
                    'delegaciones',
                    'coordinacion',
                    'carreteras',
                    'vialidades',
                ],
                'default_module' => null,
                'solo_propios' => false,
                'unidad_slug' => $unidadSlug,
                'unidad_id' => $unidadId,
            ];
        }

        return [
            'acceso_total' => false,
            'modules' => $module ? [$module] : [],
            'default_module' => $module,
            'solo_propios' => false,
            'unidad_slug' => $unidadSlug,
            'unidad_id' => $unidadId,
        ];
    }

    protected function mapUnidadIdToModule(?int $unidadId): ?string
    {
        switch ($unidadId) {
            case 1:
                return 'siniestros';

            case 2:
                return 'delegaciones';

            case 3:
                return 'coordinacion';

            case 4:
                return 'carreteras';

            case 5:
                return 'vialidades';

            default:
                return null;
        }
    }

    protected function mapUnidadSlugToModule(?string $slug): ?string
    {
        switch ($slug) {
            case 'siniestros':
                return 'siniestros';

            case 'delegaciones':
                return 'delegaciones';

            case 'seguridad-vial':
            case 'coordinacion':
            case 'coordinación':
                return 'coordinacion';

            case 'carreteras':
                return 'carreteras';

            case 'vialidades-urbanas':
                return 'vialidades';

            case 'cultura-vial':
                return null;

            default:
                return null;
        }
    }

    protected function normalizePhone(string $value): string
    {
        $value = preg_replace('/\D+/', '', $value);

        if (strlen($value) === 10) {
            return '521' . $value;
        }

        if (strlen($value) === 12 && $this->startsWith($value, '52')) {
            return '521' . substr($value, 2);
        }

        return $value;
    }

    protected function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }
}
