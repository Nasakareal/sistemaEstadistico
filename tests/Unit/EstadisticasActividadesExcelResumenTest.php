<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasActividadesController;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ReflectionMethod;
use Tests\TestCase;

class EstadisticasActividadesExcelResumenTest extends TestCase
{
    public function test_resumen_incluye_estado_de_fuerza_unidades_y_personas_alcanzadas(): void
    {
        $rows = collect([
            $this->actividad(1, 'ABANDERAMIENTOS', 10, 'ACCIDENTES', 4, '3214, 3178', 25),
            $this->actividad(1, 'ABANDERAMIENTOS', 10, 'ACCIDENTES', 3, '2', 15),
            $this->actividad(1, 'ABANDERAMIENTOS', 11, 'OBRAS PÚBLICAS', 0, '2637', 8, 'OF. ANA, OF. LUIS'),
        ]);

        $categorias = $this->agrupar($rows);
        $categoria = $categorias->first();
        $accidentes = $categoria['subcategorias']->firstWhere('nombre', 'ACCIDENTES');

        $this->assertSame(3, $categoria['total']);
        $this->assertSame(9, $categoria['estado_fuerza_participante']);
        $this->assertSame(5, $categoria['unidades_participantes']);
        $this->assertSame(48, $categoria['personas_alcanzadas']);
        $this->assertSame(2, $accidentes['total']);
        $this->assertSame(7, $accidentes['estado_fuerza_participante']);
        $this->assertSame(4, $accidentes['unidades_participantes']);
        $this->assertSame(40, $accidentes['personas_alcanzadas']);
    }

    public function test_resumen_descarta_duplicados_de_una_misma_actividad(): void
    {
        $actividad = $this->actividad(1, 'PROGRAMAS', 20, 'CONCIENTIZACIÓN', 5, '04-174', 100);
        $categorias = $this->agrupar(collect([$actividad, clone $actividad]));
        $categoria = $categorias->first();

        $this->assertSame(1, $categoria['total']);
        $this->assertSame(5, $categoria['estado_fuerza_participante']);
        $this->assertSame(1, $categoria['unidades_participantes']);
        $this->assertSame(100, $categoria['personas_alcanzadas']);
    }

    public function test_libro_descargable_reproduce_la_vista_excel_filtrada(): void
    {
        $categorias = $this->agrupar(collect([
            $this->actividad(1, 'ABANDERAMIENTOS', 10, 'ACCIDENTES', 4, '3214, 3178', 25),
            $this->actividad(1, 'ABANDERAMIENTOS', 10, 'ACCIDENTES', 3, '2', 15),
            $this->actividad(1, 'ABANDERAMIENTOS', 11, 'OBRAS PÚBLICAS', 0, '2637', 8, 'OF. ANA, OF. LUIS'),
        ]));

        $controller = new EstadisticasActividadesController();
        $method = new ReflectionMethod($controller, 'crearLibroVistaExcel');
        $method->setAccessible(true);
        $spreadsheet = $method->invoke($controller, $categorias, 'TODAS LAS UNIDADES', '01/09/2026 AL 08/09/2026');
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('TOTAL', $sheet->getTitle());
        $this->assertSame('TODAS LAS UNIDADES', $sheet->getCell('C1')->getValue());
        $this->assertSame('01/09/2026 AL 08/09/2026', $sheet->getCell('C2')->getValue());
        $this->assertSame('ESTADO DE FUERZA PARTICIPANTE', $sheet->getCell('E3')->getValue());
        $this->assertSame('PERSONAS ALCANZADAS', $sheet->getCell('H3')->getValue());
        $this->assertSame(2, $sheet->getCell('D4')->getValue());
        $this->assertSame(7, $sheet->getCell('E4')->getValue());
        $this->assertSame(4, $sheet->getCell('F4')->getValue());
        $this->assertNull($sheet->getCell('G4')->getValue());
        $this->assertSame(40, $sheet->getCell('H4')->getValue());
        $this->assertContains('A4:A5', $sheet->getMergeCells());
        $this->assertContains('B4:B5', $sheet->getMergeCells());
        $this->assertSame(3, $sheet->getCell('D6')->getValue());
        $this->assertSame(9, $sheet->getCell('E6')->getValue());
        $this->assertSame(5, $sheet->getCell('F6')->getValue());
        $this->assertSame(48, $sheet->getCell('H6')->getValue());
        $this->assertSame('FF00B050', $sheet->getStyle('D3')->getFill()->getStartColor()->getARGB());

        $tempFile = tempnam(sys_get_temp_dir(), 'vista_excel_');
        $this->assertNotFalse($tempFile);
        (new Xlsx($spreadsheet))->save($tempFile);
        $this->assertGreaterThan(0, filesize($tempFile));

        $reloaded = IOFactory::load($tempFile);
        $this->assertSame(48, $reloaded->getSheetByName('TOTAL')->getCell('H6')->getValue());
        $reloaded->disconnectWorksheets();
        $spreadsheet->disconnectWorksheets();
        unlink($tempFile);
    }

    private function agrupar(Collection $rows): Collection
    {
        $controller = new EstadisticasActividadesController();
        $method = new ReflectionMethod($controller, 'agruparResumenCategorias');
        $method->setAccessible(true);

        return $method->invoke($controller, $rows);
    }

    private function actividad(
        int $categoriaId,
        string $categoria,
        int $subcategoriaId,
        string $subcategoria,
        int $participantes,
        string $unidades,
        int $alcanzadas,
        string $elementos = ''
    ): object {
        static $id = 0;

        return (object) [
            'id' => ++$id,
            'categoria_id' => $categoriaId,
            'categoria' => $categoria,
            'subcategoria_id' => $subcategoriaId,
            'subcategoria' => $subcategoria,
            'personas_participantes' => $participantes,
            'elementos_participantes_texto' => $elementos,
            'patrullas_participantes_texto' => $unidades,
            'personas_alcanzadas' => $alcanzadas,
        ];
    }
}
