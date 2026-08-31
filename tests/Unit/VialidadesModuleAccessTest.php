<?php

namespace Tests\Unit;

use App\Providers\AuthServiceProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class VialidadesModuleAccessTest extends TestCase
{
    public function test_unidad_cinco_no_puede_usar_siniestros_ni_gruas(): void
    {
        $provider = (new ReflectionClass(AuthServiceProvider::class))->newInstanceWithoutConstructor();
        $metodo = new \ReflectionMethod($provider, 'isModuloRestringidoParaVialidades');
        $metodo->setAccessible(true);
        $usuario = (object) ['unidad_id' => 5];

        foreach (['ver hechos', 'crear hechos', 'ver vehiculos', 'ver gruas', 'crear gruas'] as $permiso) {
            $this->assertTrue($metodo->invoke($provider, $usuario, $permiso), $permiso);
        }

        $this->assertFalse($metodo->invoke($provider, $usuario, 'ver actividades'));
    }

    public function test_la_restriccion_no_se_aplica_a_otras_unidades(): void
    {
        $provider = (new ReflectionClass(AuthServiceProvider::class))->newInstanceWithoutConstructor();
        $metodo = new \ReflectionMethod($provider, 'isModuloRestringidoParaVialidades');
        $metodo->setAccessible(true);

        $this->assertFalse($metodo->invoke(
            $provider,
            (object) ['unidad_id' => 3],
            'ver hechos'
        ));
        $this->assertFalse($metodo->invoke(
            $provider,
            (object) ['unidad_id' => 1],
            'ver gruas'
        ));
    }
}
