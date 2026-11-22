<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Middleware\LimitExternalSync;

class LimitExternalSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array', 'cache.limiter' => 'array']);

        RateLimiter::clear('external-sync:2.24.67.135');
    }

    private function consultar($usuario, $ip, $agente, $metodo, $ruta): int
    {
        $request = Request::create($ruta, $metodo, [], [], [], [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => $agente,
        ]);

        $request->setUserResolver(fn () => (object) ['id' => $usuario]);

        $response = (new LimitExternalSync())->handle(
            $request,
            fn () => response()->json(['ok' => true])
        );

        return $response->getStatusCode();
    }

    public function test_limite_exclusivo_del_bot(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->assertSame(200, $this->consultar(
                71, '2.24.67.135', 'node', 'GET', '/api/hechos'
            ));
        }

        $this->assertSame(429, $this->consultar(
            71, '2.24.67.135', 'node', 'GET', '/api/actividades'
        ));
    }

    public function test_operativo_y_navegadores_sin_limite_adicional(): void
    {
        for ($i = 0; $i < 104; $i++) {
            $this->assertSame(200, $this->consultar(
                71, '2.24.67.135', 'node', 'GET',
                '/api/conduce-legalidad/operativos'
            ));

            $this->assertSame(200, $this->consultar(
                72, '2.24.67.135', 'node', 'GET', '/api/hechos'
            ));

            $this->assertSame(200, $this->consultar(
                71, '177.227.74.245', 'Mozilla/5.0', 'GET',
                '/api/actividades'
            ));

            $this->assertSame(200, $this->consultar(
                72, '177.227.74.245', 'Mozilla/5.0', 'POST',
                '/api/conduce-legalidad/operativos'
            ));
        }
    }

    public function test_rutas_reales_del_operativo_no_tienen_limitador(): void
    {
        $request = Request::create('/api/conduce-legalidad/operativos', 'GET');

        $route = app('router')->getRoutes()->match($request);

        $this->assertNotContains(
            'limit.external.sync',
            $route->gatherMiddleware()
        );
    }
}
