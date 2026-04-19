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
        $module = $this->mapUnidadSlugToModule($unidadSlug);

        if ($user->hasRole('Superadmin')) {
            return [
                'acceso_total' => true,
                'modules' => [
                    'siniestros',
                    'delegaciones',
                    'seguridad_vial',
                    'carreteras',
                    'vialidades',
                    'fomento',
                ],
                'default_module' => null,
                'solo_propios' => false,
                'unidad_slug' => $unidadSlug,
                'unidad_id' => $user->unidad_id,
            ];
        }

        if ($user->hasRole('Coordinador') && (int) $user->unidad_id === 3) {
            return [
                'acceso_total' => true,
                'modules' => [
                    'siniestros',
                    'delegaciones',
                    'seguridad_vial',
                    'carreteras',
                    'vialidades',
                    'fomento',
                ],
                'default_module' => null,
                'solo_propios' => false,
                'unidad_slug' => $unidadSlug,
                'unidad_id' => $user->unidad_id,
            ];
        }

        if ($user->hasRole('Perito')) {
            return [
                'acceso_total' => false,
                'modules' => ['siniestros'],
                'default_module' => 'siniestros',
                'solo_propios' => true,
                'unidad_slug' => 'siniestros',
                'unidad_id' => 1,
            ];
        }

        return [
            'acceso_total' => false,
            'modules' => [$module],
            'default_module' => $module,
            'solo_propios' => false,
            'unidad_slug' => $unidadSlug,
            'unidad_id' => $user->unidad_id,
        ];
    }

    protected function mapUnidadSlugToModule(?string $slug): string
    {
        switch ($slug) {
            case 'siniestros':
                return 'siniestros';

            case 'delegaciones':
                return 'delegaciones';

            case 'seguridad-vial':
                return 'seguridad_vial';

            case 'carreteras':
                return 'carreteras';

            case 'vialidades-urbanas':
                return 'vialidades';

            case 'cultura-vial':
                return 'fomento';

            default:
                return 'siniestros';
        }
    }

    protected function normalizePhone(string $value): string
    {
        $value = preg_replace('/\D+/', '', $value);

        if (strlen($value) === 10) {
            return '521' . $value;
        }

        if (strlen($value) === 12 && str_starts_with($value, '52')) {
            return '521' . substr($value, 2);
        }

        return $value;
    }
}
