<?php

namespace Tests\Unit;

use App\Support\ActividadVehiculoCaptura;
use PHPUnit\Framework\TestCase;

class ActividadVehiculoCapturaTest extends TestCase
{
    public function test_oculta_resguardo_solo_a_vialidades_en_orientacion_preventiva(): void
    {
        $vialidades = (object) ['unidad_id' => 5];
        $otraUnidad = (object) ['unidad_id' => 3];
        $revisiones = (object) ['nombre' => 'REVISIONES'];
        $orientacion = (object) ['nombre' => 'ORIENTACIÓN PREVENTIVA'];

        $this->assertTrue(ActividadVehiculoCaptura::ocultaDatosResguardo(
            $vialidades,
            $revisiones,
            $orientacion
        ));
        $this->assertFalse(ActividadVehiculoCaptura::ocultaDatosResguardo(
            $otraUnidad,
            $revisiones,
            $orientacion
        ));
        $this->assertFalse(ActividadVehiculoCaptura::ocultaDatosResguardo(
            $vialidades,
            (object) ['nombre' => 'OPERATIVOS'],
            $orientacion
        ));
    }

    public function test_limpia_grua_corralon_y_aseguradora_sin_alterar_el_resto_del_vehiculo(): void
    {
        $resultado = ActividadVehiculoCaptura::limpiarDatosResguardo([
            'marca' => 'NISSAN',
            'modelo' => '2020',
            'grua_id' => 8,
            'grua' => 'GRÚAS CENTRO',
            'corralon' => 'PATIOS CENTRO',
            'aseguradora' => 'EJEMPLO',
        ]);

        $this->assertSame('NISSAN', $resultado['marca']);
        $this->assertSame('2020', $resultado['modelo']);
        $this->assertNull($resultado['grua_id']);
        $this->assertNull($resultado['grua']);
        $this->assertNull($resultado['corralon']);
        $this->assertNull($resultado['aseguradora']);
    }

    public function test_valores_de_relleno_en_modelo_y_niv_se_guardan_como_vacios(): void
    {
        foreach (['X', 'S/D', 'N/A', 'NO APLICA', 'sin datos'] as $valor) {
            $resultado = ActividadVehiculoCaptura::normalizarCamposOpcionales([
                'modelo' => $valor,
                'serie' => $valor,
            ]);

            $this->assertNull($resultado['modelo']);
            $this->assertNull($resultado['serie']);
        }
    }

    public function test_servicio_publico_federal_asigna_estado_federal_sin_pedirlo(): void
    {
        $federal = ActividadVehiculoCaptura::normalizarCamposOpcionales([
            'placas' => '12ABC3',
            'tipo_servicio' => 'SERVICIO PÚBLICO FEDERAL',
            'estado_placas' => '',
        ]);

        $this->assertSame('FEDERAL', $federal['estado_placas']);
        $this->assertFalse(ActividadVehiculoCaptura::requiereEstadoPlacas($federal));
        $this->assertTrue(ActividadVehiculoCaptura::requiereEstadoPlacas([
            'placas' => 'PFA123A',
            'tipo_servicio' => 'PARTICULAR',
            'estado_placas' => '',
        ]));
    }
}
