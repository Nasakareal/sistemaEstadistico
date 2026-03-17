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
            return $user->hasRole('Superadmin') ? true : null;
        });

        Gate::define('menu-dictamenes', function ($user) {
            return $user->can('ver dictamenes')
                && $user->perteneceAUnidad('siniestros');
        });

        Gate::define('menu-dictamenes-crear', function ($user) {
            return $user->can('crear dictamenes')
                && $user->perteneceAUnidad('siniestros');
        });

        Gate::define('menu-delegaciones', function ($user) {
            return $user->can('ver delegaciones')
                && $user->perteneceAUnidad('delegaciones');
        });

        Gate::define('menu-delegaciones-crear', function ($user) {
            return $user->can('crear delegaciones')
                && $user->perteneceAUnidad('delegaciones');
        });

        Gate::define('menu-modulo-examenes', function ($user) {
            return $user->can('ver modulo examenes')
                && $user->perteneceAAlgunaUnidad(['siniestros', 'delegaciones']);
        });

        Gate::define('menu-modulo-examenes-crear', function ($user) {
            return $user->can('crear modulo examenes')
                && $user->perteneceAAlgunaUnidad(['siniestros', 'delegaciones']);
        });

        Gate::define('menu-estadisticas-globales', function ($user) {
            return $user->can('ver estadisticas globales')
                && $user->perteneceAUnidad('siniestros');
        });

        Gate::define('menu-estadisticas-carreteras', function ($user) {
            return $user->can('ver estadisticas carreteras')
                && $user->perteneceAUnidad('carreteras');
        });

        Gate::define('menu-guardianes-camino', function ($user) {
            return $user->can('ver operativos carreteras')
                && $user->perteneceAUnidad('carreteras');
        });

        Gate::define('menu-guardianes-camino-crear', function ($user) {
            return $user->can('crear operativos carreteras')
                && $user->perteneceAUnidad('carreteras');
        });
    }
}
