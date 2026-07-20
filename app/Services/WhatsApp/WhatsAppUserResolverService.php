<?php

namespace App\Services\WhatsApp;

use App\Models\User;

class WhatsAppUserResolverService
{
    public function findAuthorizedUserByPhone(string $from): ?User
    {
        $telefonos = $this->phoneVariants($from);

        if (empty($telefonos)) {
            return null;
        }

        foreach ($telefonos as $telefono) {
            $user = User::query()
                ->with(['unidad', 'roles'])
                ->where('telefono', $telefono)
                ->first();

            if ($user) {
                return $user;
            }
        }

        return null;
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

    protected function phoneVariants(string $value): array
    {
        $digits = preg_replace('/\D+/', '', $value) ?: '';
        $normalized = $this->normalizePhone($digits);
        $variants = [$normalized, $digits];

        if (preg_match('/^521(\d{10})$/', $normalized, $matches)) {
            $variants[] = $matches[1];
            $variants[] = '52' . $matches[1];
        }

        if (preg_match('/^52(\d{10})$/', $digits, $matches)) {
            $variants[] = '521' . $matches[1];
            $variants[] = $matches[1];
        }

        return collect($variants)
            ->map(fn ($phone) => trim((string) $phone))
            ->filter(fn ($phone) => $phone !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }
}
