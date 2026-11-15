<?php

namespace Tests\Unit;

use App\Services\ConduceLegalidadVehiculosReporteService;
use PHPUnit\Framework\TestCase;

class ConduceLegalidadVehiculosReporteServiceTest extends TestCase
{
    public function test_construye_la_ficha_del_vehiculo_sin_campos_vacios(): void
    {
        $service = new ConduceLegalidadVehiculosReporteService();
        $vehiculo = (object) [
            'marca' => 'ITALIKA',
            'linea' => 'FT150',
            'modelo' => '2026',
            'tipo' => '',
            'tipo_general' => 'MOTOCICLETA',
            'color' => 'ROJO',
            'placas' => null,
            'estado_placas' => null,
            'serie' => 'ABC123',
            'numero_inventario' => '0863',
            'tipo_servicio' => 'PARTICULAR',
        ];

        $resultado = $service->datosVehiculo($vehiculo);

        $this->assertStringContainsString('Marca: ITALIKA', $resultado);
        $this->assertStringContainsString('Tipo: MOTOCICLETA', $resultado);
        $this->assertStringContainsString('No. inventario: 0863', $resultado);
        $this->assertStringNotContainsString('Placas:', $resultado);
    }

    public function test_combina_motivos_sin_duplicados_y_conserva_el_orden(): void
    {
        $service = new ConduceLegalidadVehiculosReporteService();

        $resultado = $service->combinarMotivos([
            'Art. 328 - Sin placas',
            '  Art. 419 - Sin casco  ',
            'art. 328 - sin placas',
            '',
        ]);

        $this->assertSame('Art. 328 - Sin placas; Art. 419 - Sin casco', $resultado);
    }

    public function test_muestra_un_texto_claro_cuando_no_hay_motivo(): void
    {
        $service = new ConduceLegalidadVehiculosReporteService();

        $this->assertSame('Sin motivo especificado', $service->combinarMotivos(['', '   ']));
    }
}
