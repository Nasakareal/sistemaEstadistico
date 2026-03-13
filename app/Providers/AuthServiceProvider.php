<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            return $user->hasRole('Superadmin') ? true : null;
        });

        /**
         * =========================
         * DICTÁMENES
         * Solo SINIESTROS
         * =========================
         */
        Gate::define('menu-dictamenes', function ($user) {
            return $user->can('ver dictamenes')
                && $user->perteneceAUnidad('siniestros');
        });

        Gate::define('menu-dictamenes-crear', function ($user) {
            return $user->can('crear dictamenes')
                && $user->perteneceAUnidad('siniestros');
        });

        /**
         * =========================
         * DELEGACIONES
         * Solo DELEGACIONES
         * =========================
         */
        Gate::define('menu-delegaciones', function ($user) {
            return $user->can('ver delegaciones')
                && $user->perteneceAUnidad('delegaciones');
        });

        Gate::define('menu-delegaciones-crear', function ($user) {
            return $user->can('crear delegaciones')
                && $user->perteneceAUnidad('delegaciones');
        });

        /**
         * =========================
         * MÓDULO EXÁMENES DIARIOS
         * SINIESTROS y DELEGACIONES
         * =========================
         */
        Gate::define('menu-modulo-examenes', function ($user) {
            return $user->can('ver modulo examenes')
                && $user->perteneceAAlgunaUnidad(['siniestros', 'delegaciones']);
        });

        Gate::define('menu-modulo-examenes-crear', function ($user) {
            return $user->can('crear modulo examenes')
                && $user->perteneceAAlgunaUnidad(['siniestros', 'delegaciones']);
        });
    }
}
