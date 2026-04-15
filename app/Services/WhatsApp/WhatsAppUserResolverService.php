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
        if ($user->hasRole('Superadmin')) {
            return [
                'acceso_total' => true,
                'modules' => ['siniestros', 'carreteras', 'vialidades'],
                'default_module' => null,
                'solo_propios' => false,
                'unidad_slug' => optional($user->unidad)->slug,
                'unidad_id' => $user->unidad_id,
            ];
        }

        if ($user->hasRole('Coordinador') && (int) $user->unidad_id === 3) {
            return [
                'acceso_total' => true,
                'modules' => ['siniestros', 'carreteras', 'vialidades'],
                'default_module' => null,
                'solo_propios' => false,
                'unidad_slug' => optional($user->unidad)->slug,
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

        $unidadSlug = optional($user->unidad)->slug;

        return [
            'acceso_total' => false,
            'modules' => [$this->mapUnidadSlugToModule($unidadSlug)],
            'default_module' => $this->mapUnidadSlugToModule($unidadSlug),
            'solo_propios' => false,
            'unidad_slug' => $unidadSlug,
            'unidad_id' => $user->unidad_id,
        ];
    }

    protected function mapUnidadSlugToModule(?string $slug): string
    {
        return match ($slug) {
            'siniestros' => 'siniestros',
            'carreteras' => 'carreteras',
            'vialidades-urbanas' => 'vialidades',
            default => 'siniestros',
        };
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
