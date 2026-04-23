<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('Superadmin')) {
                return true;
            }

            $carreterasAbilities = [
                'ver operativos carreteras',
                'crear operativos carreteras',
                'editar operativos carreteras',
                'eliminar operativos carreteras',
                'ver estadisticas carreteras',
            ];

            if (in_array($ability, $carreterasAbilities, true)) {
                $unidadOk = $user->perteneceAUnidad('carreteras') || (int) ($user->unidad_id ?? 0) === 3;
                if (!$unidadOk) {
                    return false;
                }

                if (
                    $ability === 'ver operativos carreteras'
                    && $user->hasAnyRole(['Administrador', 'Subdirector', 'Administrativo', 'Agente Upec', 'RT', 'Encargado de Destacamento'])
                ) {
                    return true;
                }

                if (
                    $ability === 'crear operativos carreteras'
                    && $user->hasAnyRole(['Administrador', 'Agente Upec'])
                ) {
                    return true;
                }

                if (
                    in_array($ability, ['editar operativos carreteras', 'ver estadisticas carreteras'], true)
                    && $user->hasAnyRole(['Administrador', 'Subdirector', 'Administrativo', 'RT', 'Encargado de Destacamento'])
                ) {
                    return true;
                }

                if (
                    $ability === 'eliminar operativos carreteras'
                    && $user->hasRole('Administrador')
                ) {
                    return true;
                }
            }

            if (
                in_array($ability, ['ver puestas a disposicion', 'crear puestas a disposicion'], true)
                && !empty($user->unidad_id)
            ) {
                return true;
            }

            return null;
        });

        Gate::define('menu-dictamenes', function ($user) {
            return $user->can('ver dictamenes')
                && $user->perteneceAUnidad('siniestros');
        });

        Gate::define('menu-dictamenes-crear', function ($user) {
            return $user->can('crear dictamenes')
                && $user->perteneceAUnidad('siniestros');
        });

        Gate::define('menu-puestas-disposicion', function ($user) {
            return $user->can('ver puestas a disposicion')
                || (
                    $user->can('ver dictamenes')
                    && $user->perteneceAUnidad('siniestros')
                );
        });

        Gate::define('menu-puestas-disposicion-crear', function ($user) {
            return $user->can('crear puestas a disposicion');
        });

        Gate::define('menu-delegaciones', function ($user) {
            return $user->can('ver delegaciones')
                && (
                    $user->perteneceAUnidad('delegaciones')
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-delegaciones-crear', function ($user) {
            return $user->can('crear delegaciones')
                && (
                    $user->perteneceAUnidad('delegaciones')
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-modulo-examenes', function ($user) {
            return $user->can('ver modulo examenes')
                && (
                    $user->perteneceAAlgunaUnidad(['siniestros', 'delegaciones'])
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-modulo-examenes-crear', function ($user) {
            return $user->can('crear modulo examenes')
                && (
                    $user->perteneceAAlgunaUnidad(['siniestros', 'delegaciones'])
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-estadisticas-globales', function ($user) {
            return $user->can('ver estadisticas globales')
                && (
                    $user->perteneceAUnidad('siniestros')
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-estadisticas-carreteras', function ($user) {
            return $user->can('ver estadisticas carreteras')
                && (
                    $user->perteneceAUnidad('carreteras')
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-estadisticas-generales', function ($user) {
            return (
                $user->can('ver estadisticas globales')
                && (
                    $user->perteneceAUnidad('siniestros')
                    || (int) $user->unidad_id === 3
                )
            ) || (
                $user->can('ver estadisticas carreteras')
                && (
                    $user->perteneceAUnidad('carreteras')
                    || (int) $user->unidad_id === 3
                )
            );
        });

        Gate::define('menu-guardianes-camino', function ($user) {
            return $user->can('ver operativos carreteras')
                && (
                    $user->perteneceAUnidad('carreteras')
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-guardianes-camino-crear', function ($user) {
            return $user->can('crear operativos carreteras')
                && (
                    $user->perteneceAUnidad('carreteras')
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-hechos-pendientes-revision', function ($user) {
            return $user->can('ver hechos')
                && (
                    (int) $user->unidad_id === 1
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-guardianes-pendientes-revision', function ($user) {
            return $user->isSuperadmin()
                || (
                    (int) $user->unidad_id === 4
                    && $user->can('editar operativos carreteras')
                    && $user->hasAnyRole(['RT', 'Encargado de Destacamento'])
                );
        });

        Gate::define('menu-vialidades-urbanas', function ($user) {
            return $user->can('ver operativos vialidades')
                && (
                    $user->perteneceAUnidad('vialidades-urbanas')
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-vialidades-urbanas-crear', function ($user) {
            return $user->can('crear operativos vialidades')
                && (
                    $user->perteneceAUnidad('vialidades-urbanas')
                    || (int) $user->unidad_id === 3
                );
        });

        Gate::define('menu-actividades', function ($user) {
            return !(
                $user->perteneceAUnidad('carreteras')
                && (int) $user->unidad_id !== 3
            );
        });
    }
}
