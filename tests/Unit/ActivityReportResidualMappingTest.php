<?php

namespace Tests\Unit;

use App\Services\Delegaciones\DelegacionesExcelRevisionService;
use App\Services\Delegaciones\Hojas\MoreliaRpSheetService;
use App\Services\Delegaciones\Hojas\RegionalSheetService;
use App\Services\VialidadesUrbanas\Hojas\TotalSheetService;
use App\Services\VialidadesUrbanasDiarioWhatsAppService;
use ReflectionMethod;
use Tests\TestCase;

class ActivityReportResidualMappingTest extends TestCase
{
    public function test_delegaciones_acumula_subcategorias_no_listadas_en_otros(): void
    {
        $service = new RegionalSheetService();
        $method = new ReflectionMethod($service, 'agruparNoListadasEnOtros');
        $method->setAccessible(true);

        $vacio = [
            'cantidad' => 0,
            'estado_fuerza' => 0,
            'unidades' => 0,
            'kilometros' => 0,
            'personas' => 0,
            'recomendaciones' => 0,
        ];
        $otros = 'OTROS MONITOREOS (Especificar en las novedades relevantes)';
        $datos = [
            'VÍAS FÉRREAS' => array_replace($vacio, ['cantidad' => 1]),
            'CARRETERAS' => array_replace($vacio, ['cantidad' => 2, 'estado_fuerza' => 4]),
            'CASETAS' => array_replace($vacio, ['cantidad' => 3, 'personas' => 8]),
            $otros => array_replace($vacio, ['cantidad' => 1]),
        ];

        $resultado = $method->invoke($service, $datos, ['VÍAS FÉRREAS', $otros], $otros);

        $this->assertArrayNotHasKey('CARRETERAS', $resultado);
        $this->assertArrayNotHasKey('CASETAS', $resultado);
        $this->assertSame(6, $resultado[$otros]['cantidad']);
        $this->assertSame(4, $resultado[$otros]['estado_fuerza']);
        $this->assertSame(8, $resultado[$otros]['personas']);
    }

    public function test_excel_vialidades_envia_las_seis_subcategorias_a_sus_renglones_otros(): void
    {
        $method = new ReflectionMethod(TotalSheetService::class, 'construirFilas');
        $method->setAccessible(true);

        $actividades = collect([
            $this->actividad('MONITOREOS', 'CARRETERAS'),
            $this->actividad('MONITOREOS', 'CASETAS'),
            $this->actividad('ABANDERAMIENTOS', 'BLOQUEO CARRETERO'),
            $this->actividad('DISPOSITIVOS DE SEGURIDAD VIAL', 'RESGUARDO DE VEHÍCULO POR OBSTRUCCIÓN O ABANDONO'),
            $this->actividad('OPERATIVOS', 'ALCOHOLIMETRÍA'),
            $this->actividad('OPERATIVOS', 'CONDUCE CON LEGALIDAD'),
        ]);

        $rows = collect($method->invoke(new TotalSheetService(), $actividades, collect()));

        $this->assertSame(2, $rows->firstWhere('actividad', 'OTROS MONITOREOS (Especificar en las novedades relevantes)')['cantidad']);
        $this->assertSame(1, $rows->firstWhere('actividad', 'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)')['cantidad']);
        $this->assertSame(1, $rows->firstWhere('actividad', 'OTROS (Especificar en las novedades relevantes)')['cantidad']);
        $this->assertSame(2, $rows->firstWhere('actividad', 'OTROS OPERATIVOS (Especificar en las novedades relevantes)')['cantidad']);
    }

    public function test_morelia_rp_y_revision_delegaciones_usan_renglon_otros(): void
    {
        $morelia = new MoreliaRpSheetService();
        $clasificar = new ReflectionMethod($morelia, 'clasificarSubcategoria');
        $clasificar->setAccessible(true);

        $this->assertSame('OTROS', $clasificar->invoke($morelia, 'CASETAS', ['VÍAS FÉRREAS' => [], 'OTROS' => []]));

        $revision = new DelegacionesExcelRevisionService();
        $renglon = new ReflectionMethod($revision, 'renglonExcelActividad');
        $renglon->setAccessible(true);

        $this->assertSame(
            'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)',
            $renglon->invoke($revision, 3, 'BLOQUEO CARRETERO')
        );
        $this->assertSame(
            'OTROS OPERATIVOS (Especificar en las novedades relevantes)',
            $renglon->invoke($revision, 4, 'ALCOHOLIMETRÍA')
        );
    }

    public function test_whatsapp_vialidades_presenta_nuevas_subcategorias_como_otros(): void
    {
        $service = new VialidadesUrbanasDiarioWhatsAppService();
        $label = new ReflectionMethod($service, 'subcategoriaResumenLabel');
        $label->setAccessible(true);

        foreach ([
            'CARRETERAS',
            'CASETAS',
            'BLOQUEO CARRETERO',
            'RESGUARDO DE VEHÍCULO POR OBSTRUCCIÓN O ABANDONO',
            'ALCOHOLIMETRÍA',
            'CONDUCE CON LEGALIDAD',
        ] as $subcategoria) {
            $this->assertSame('Otros', $label->invoke($service, (object) ['nombre' => $subcategoria]));
        }

        $resumen = new ReflectionMethod($service, 'categoriaResumenConOtros');
        $resumen->setAccessible(true);
        $totales = [
            'DISPOSITIVOS DE SEGURIDAD VIAL' => [
                'nombre' => 'DISPOSITIVOS DE SEGURIDAD VIAL',
                'total' => 5,
                'subcategorias' => ['PATRULLAJES'],
            ],
            'OPERATIVOS' => [
                'nombre' => 'OPERATIVOS',
                'total' => 2,
                'subcategorias' => ['Otros'],
            ],
        ];

        $this->assertSame(
            '07 - Patrullajes y Otros.',
            $resumen->invoke($service, $totales, 'DISPOSITIVOS DE SEGURIDAD VIAL', ['OPERATIVOS'])
        );
    }

    private function actividad(string $categoria, string $subcategoria): object
    {
        return (object) [
            'categoria' => (object) ['nombre' => $categoria],
            'subcategoria' => (object) ['nombre' => $subcategoria],
            'nombre' => $subcategoria,
            'cantidad' => 1,
            'elementos_participantes_texto' => null,
            'patrullas_participantes_texto' => null,
            'km_recorridos' => 0,
            'personas_alcanzadas' => 0,
            'fomentoCulturaVialDetalle' => null,
            'motivo' => null,
            'narrativa' => null,
            'acciones_realizadas' => null,
            'observaciones' => null,
        ];
    }
}
