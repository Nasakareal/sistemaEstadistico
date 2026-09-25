<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\GruaController;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use ReflectionMethod;
use Tests\TestCase;

class GruaConduceLegalidadOriginTest extends TestCase
{
    public function test_reconoce_los_aliases_del_origen_conduce_legalidad(): void
    {
        $metodo = new ReflectionMethod(GruaController::class, 'esOrigenConduceLegalidad');
        $metodo->setAccessible(true);
        $controller = new GruaController();

        foreach (['conduce', 'conduce_legalidad', 'conduce-legalidad'] as $origen) {
            $request = Request::create('/api/gruas', 'GET', ['origen' => $origen]);

            $this->assertTrue($metodo->invoke($controller, $request));
        }

        $request = Request::create('/api/gruas', 'GET', ['origen' => 'siniestros']);
        $this->assertFalse($metodo->invoke($controller, $request));
    }

    public function test_conduce_usa_el_catalogo_de_gruas_de_siniestros(): void
    {
        $metodo = new ReflectionMethod(GruaController::class, 'requestedUnidadId');
        $metodo->setAccessible(true);
        $request = Request::create('/api/gruas', 'GET', [
            'origen' => 'conduce_legalidad',
        ]);

        $this->assertSame(1, $metodo->invoke(new GruaController(), $request));
    }

    public function test_catalogo_conduce_parte_de_todas_las_gruas_de_siniestros(): void
    {
        $metodo = new ReflectionMethod(GruaController::class, 'conduceGruasQuery');
        $metodo->setAccessible(true);

        $query = $metodo->invoke(
            new GruaController(),
            Request::create('/api/gruas', 'GET', ['origen' => 'conduce_legalidad'])
        );

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertStringContainsString('unidad_grua', $query->toSql());
        $this->assertContains(1, $query->getBindings());
    }
}
