<?php

namespace Tests\Unit;

use App\Models\BoquillaDotacion;
use App\Models\BoquillaPerdida;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use App\Models\ConduceLegalidadPersona;
use App\Models\ConduceLegalidadVehiculo;
use App\Services\Alcoholimetria\AlcoholimetriaMensualService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class AlcoholimetriaMensualServiceTest extends TestCase
{
    public function test_concilia_pruebas_y_perdidas_sin_alterar_los_no_aptos(): void
    {
        $capturas = new Collection([
            $this->captura('2026-07-03', 'MASCULINO', 'automovil', 'PARTICULAR'),
            $this->captura('2026-07-10', 'FEMENINO', 'motocicleta', 'PARTICULAR'),
            $this->captura('2026-07-29', 'MASCULINO', 'automovil', 'PÚBLICO'),
        ]);
        $perdidas = new Collection([
            new BoquillaPerdida([
                'fecha_perdida' => '2026-07-11',
                'cantidad' => 2,
            ]),
        ]);

        $resumen = (new AlcoholimetriaMensualService())->construirResumen(
            Carbon::parse('2026-07-01'),
            $capturas,
            $perdidas,
            100,
            20,
            'Morelia'
        );

        $this->assertSame(3, $resumen['pruebas_reales']);
        $this->assertSame(2, $resumen['boquillas']['perdidas']);
        $this->assertSame(5, $resumen['pruebas_reportadas']);
        $this->assertSame(3, $resumen['conductores_no_aptos']);
        $this->assertSame(2, $resumen['conductores_aptos_reportados']);
        $this->assertSame(2, $resumen['ajuste_aptos_por_boquillas_perdidas']);
        $this->assertSame(115, $resumen['boquillas']['existencia_final']);
        $this->assertSame(5, $resumen['boquillas']['salidas_inventario_controlado']);
        $this->assertSame(0, $resumen['boquillas']['externas_no_controladas']);
        $this->assertSame('1', $resumen['variables']['ts1']);
        $this->assertSame('3', $resumen['variables']['ts2']);
        $this->assertSame('1', $resumen['variables']['ts5']);
        $this->assertSame('1', $resumen['variables']['ahs1']);
        $this->assertSame('1', $resumen['variables']['mms2']);
        $this->assertSame('1', $resumen['variables']['tpihs5']);
        $this->assertSame('3', $resumen['variables']['tcna']);
    }

    public function test_sigue_contando_pruebas_al_agotarse_las_boquillas_proporcionadas(): void
    {
        $capturas = new Collection([
            $this->captura('2026-07-03', 'MASCULINO', 'automovil', 'PARTICULAR'),
            $this->captura('2026-07-04', 'MASCULINO', 'automovil', 'PARTICULAR'),
            $this->captura('2026-07-05', 'MASCULINO', 'automovil', 'PARTICULAR'),
            $this->captura('2026-07-06', 'MASCULINO', 'automovil', 'PARTICULAR'),
            $this->captura('2026-07-07', 'MASCULINO', 'automovil', 'PARTICULAR'),
        ]);

        $resumen = (new AlcoholimetriaMensualService())->construirResumen(
            Carbon::parse('2026-07-01'),
            $capturas,
            new Collection(),
            1,
            0,
            'Morelia'
        );

        $this->assertSame(5, $resumen['pruebas_reales']);
        $this->assertSame(1, $resumen['boquillas']['salidas_inventario_controlado']);
        $this->assertSame(4, $resumen['boquillas']['externas_no_controladas']);
        $this->assertSame(0, $resumen['boquillas']['existencia_final']);
        $this->assertSame('5', $resumen['variables']['um']);
        $this->assertSame('0', $resumen['variables']['efm']);
    }

    public function test_una_entrega_futura_no_cubre_recuentos_anteriores(): void
    {
        $capturas = new Collection([
            $this->captura('2026-07-03', 'MASCULINO', 'automovil', 'PARTICULAR'),
            $this->captura('2026-07-04', 'MASCULINO', 'automovil', 'PARTICULAR'),
        ]);
        $dotaciones = new Collection([
            new BoquillaDotacion([
                'fecha_recepcion' => '2026-07-10',
                'cantidad' => 3,
            ]),
        ]);

        $resumen = (new AlcoholimetriaMensualService())->construirResumen(
            Carbon::parse('2026-07-01'),
            $capturas,
            new Collection(),
            0,
            3,
            'Morelia',
            $dotaciones
        );

        $this->assertSame(2, $resumen['pruebas_reales']);
        $this->assertSame(0, $resumen['boquillas']['salidas_inventario_controlado']);
        $this->assertSame(2, $resumen['boquillas']['externas_no_controladas']);
        $this->assertSame(3, $resumen['boquillas']['existencia_final']);
    }

    private function captura(
        string $fecha,
        string $sexo,
        string $tipoGeneral,
        string $tipoServicio
    ): ConduceLegalidadCaptura {
        $captura = new ConduceLegalidadCaptura(['fecha' => $fecha]);
        $captura->setRelation('operativo', new ConduceLegalidadOperativo([
            'fecha' => $fecha,
            'municipio' => 'MORELIA',
            'tipo_operativo' => 'alcoholimetria',
        ]));
        $captura->setRelation('personas', new Collection([
            new ConduceLegalidadPersona(['sexo' => $sexo]),
        ]));
        $captura->setRelation('vehiculos', new Collection([
            new ConduceLegalidadVehiculo([
                'tipo_general' => $tipoGeneral,
                'tipo_servicio' => $tipoServicio,
            ]),
        ]));

        return $captura;
    }
}
