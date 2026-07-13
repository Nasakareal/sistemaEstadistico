<?php

namespace Tests\Unit;

use App\Models\Vehiculo;
use Tests\TestCase;

class VehiculoFotoUrlTest extends TestCase
{
    public function test_serializa_urls_firmadas_para_fotos_del_vehiculo(): void
    {
        $vehiculo = new Vehiculo([
            'fotos' => 'vehiculos/foto-prueba.jpg',
            'foto_inventario_grua' => 'inventarios/foto-prueba.jpg',
        ]);

        $data = $vehiculo->toArray();

        $this->assertStringContainsString(
            '/hechos-fotos/archivo-temporal/vehiculos/foto-prueba.jpg',
            $data['fotos_url']
        );
        $this->assertStringContainsString('signature=', $data['fotos_url']);
        $this->assertStringContainsString(
            '/hechos-fotos/archivo-temporal/inventarios/foto-prueba.jpg',
            $data['foto_inventario_grua_url']
        );
        $this->assertStringContainsString(
            'signature=',
            $data['foto_inventario_grua_url']
        );
    }

    public function test_serializa_urls_nulas_cuando_no_hay_fotos(): void
    {
        $data = (new Vehiculo())->toArray();

        $this->assertNull($data['fotos_url']);
        $this->assertNull($data['foto_inventario_grua_url']);
    }
}
