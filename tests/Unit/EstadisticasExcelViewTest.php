<?php

namespace Tests\Unit;

use Tests\TestCase;

class EstadisticasExcelViewTest extends TestCase
{
    public function test_las_dos_estadisticas_incluyen_la_vista_excel_total(): void
    {
        $views = [
            resource_path('views/estadisticas_globales/index.blade.php') => 'hechos',
            resource_path('views/estadisticas_actividades/index.blade.php') => 'actividades',
        ];

        foreach ($views as $path => $mode) {
            $view = (string) file_get_contents($path);

            $this->assertStringContainsString('id="btn_vista_excel"', $view);
            $this->assertStringContainsString('Vista Excel', $view);
            $this->assertStringContainsString("'excelMode' => '{$mode}'", $view);
            $this->assertStringContainsString('id="sv_dashboard_view"', $view);
            $this->assertStringContainsString("mode: '{$mode}'", $view);
            $this->assertStringContainsString('estadisticas-excel-view.css', $view);
            $this->assertStringContainsString('estadisticas-excel-view.js', $view);
        }
    }

    public function test_el_componente_reproduce_los_controles_de_excel_y_solo_la_hoja_total(): void
    {
        $partial = (string) file_get_contents(
            resource_path('views/partials/estadisticas_excel_view.blade.php')
        );

        foreach (['Archivo', 'Inicio', 'Diseño de página', 'Fórmulas', 'Datos', 'Revisar', 'Vista'] as $tab) {
            $this->assertStringContainsString($tab, $partial);
        }

        $this->assertStringContainsString('data-excel-formula', $partial);
        $this->assertStringContainsString('data-excel-sheet', $partial);
        $this->assertStringContainsString('data-excel-zoom', $partial);
        $this->assertSame(1, substr_count($partial, '>TOTAL<'));
    }

    public function test_el_script_carga_datos_filtrados_para_actividades_y_hechos(): void
    {
        $script = (string) file_get_contents(
            public_path('js/estadisticas-excel-view.js')
        );

        foreach ([
            "fetchJson(options.base, 'kpis', query)",
            "fetchJson(options.base, 'resumen/categorias', query)",
            "fetchJson(options.base, 'series/unidad', query)",
            "fetchJson(options.base, 'series/vehiculos/tipo', query)",
            'buildActivities',
            'buildFacts',
            'refreshIfVisible',
        ] as $expected) {
            $this->assertStringContainsString($expected, $script);
        }
    }
}
