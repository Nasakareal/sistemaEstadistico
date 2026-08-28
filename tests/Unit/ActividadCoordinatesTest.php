<?php

namespace Tests\Unit;

use App\Http\Controllers\ActividadController;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ActividadCoordinatesTest extends TestCase
{
    public function test_solo_el_rol_administrativo_puede_escribir_coordenadas(): void
    {
        $method = new ReflectionMethod(ActividadController::class, 'userCanWriteCoordinates');
        $method->setAccessible(true);
        $controller = new ActividadController();

        $administrativo = $this->usuarioConRoles(['Administrativo']);
        $administrador = $this->usuarioConRoles(['Administrador']);

        $this->assertTrue($method->invoke($controller, $administrativo));
        $this->assertFalse($method->invoke($controller, $administrador));
    }

    public function test_el_rol_administrativo_puede_capturar_otro_nombre(): void
    {
        $method = new ReflectionMethod(ActividadController::class, 'userCanWriteActivityReporter');
        $method->setAccessible(true);
        $controller = new ActividadController();

        $this->assertTrue($method->invoke($controller, $this->usuarioConRoles(['Administrativo'])));
        $this->assertFalse($method->invoke($controller, $this->usuarioConRoles(['Administrador'])));
    }

    public function test_el_rol_administrativo_puede_capturar_fecha_y_hora(): void
    {
        $method = new ReflectionMethod(ActividadController::class, 'userCanCaptureFechaHora');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(
            new ActividadController(),
            $this->usuarioConRoles(['Administrativo'])
        ));
    }

    public function test_coordenadas_vacias_se_normalizan_a_null(): void
    {
        $request = Request::create('/actividades', 'POST', [
            'lat' => '19.7000000',
            'lng' => '-101.2000000',
            'coordenadas_texto' => '',
            'fuente_ubicacion' => 'GPS_WEB',
            'nota_geo' => 'ACC:10m',
        ]);

        $this->normalizar($request);

        $this->assertNull($request->input('lat'));
        $this->assertNull($request->input('lng'));
        $this->assertNull($request->input('coordenadas_texto'));
        $this->assertNull($request->input('fuente_ubicacion'));
        $this->assertNull($request->input('nota_geo'));
    }

    public function test_coordenadas_escritas_se_separan_en_latitud_y_longitud(): void
    {
        $request = Request::create('/actividades', 'POST', [
            'coordenadas_texto' => '19.6808588, -101.2339535',
        ]);

        $this->normalizar($request);

        $this->assertSame('19.6808588', $request->input('lat'));
        $this->assertSame('-101.2339535', $request->input('lng'));
        $this->assertSame('19.6808588, -101.2339535', $request->input('coordenadas_texto'));
        $this->assertSame('MANUAL_WEB', $request->input('fuente_ubicacion'));
    }

    private function normalizar(Request $request): void
    {
        $method = new ReflectionMethod(ActividadController::class, 'normalizeWritableCoordinates');
        $method->setAccessible(true);
        $method->invoke(new ActividadController(), $request, true);
    }

    private function usuarioConRoles(array $roles)
    {
        return new class($roles) {
            private array $roles;

            public function __construct(array $roles)
            {
                $this->roles = $roles;
            }

            public function hasRole(string $role): bool
            {
                return in_array($role, $this->roles, true);
            }
        };
    }
}
