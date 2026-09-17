<?php

namespace Tests\Feature;

use Tests\TestCase;

class PatrullaServicioAuthorizationTest extends TestCase
{
    public function test_patrulla_routes_authenticate_the_api_token_before_authorizing_permission(): void
    {
        $routeNames = [
            'api.patrullas.disponibles',
            'api.patrullas.mi_servicio',
            'api.patrullas.mi_bitacora',
            'api.patrullas.mi_historial',
            'api.patrullas.recibir',
            'api.patrullas.entregar',
            'api.patrullas.mi_servicio.update',
            'api.patrullas.mi_servicio.kilometraje',
        ];

        foreach ($routeNames as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "No se encontró la ruta {$routeName}.");
            $middleware = $route->gatherMiddleware();
            $authIndex = array_search('auth:sanctum', $middleware, true);
            $permissionIndex = array_search('can:ver patrullas', $middleware, true);

            $this->assertNotFalse($authIndex, "Falta auth:sanctum en {$routeName}.");
            $this->assertNotFalse($permissionIndex, "Falta el permiso en {$routeName}.");
            $this->assertLessThan(
                $permissionIndex,
                $authIndex,
                "{$routeName} debe autenticar antes de autorizar."
            );
        }
    }
}
