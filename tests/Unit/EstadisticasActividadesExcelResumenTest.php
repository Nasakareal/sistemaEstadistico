<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasActividadesController;
use Illuminate\Support\Collection;
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
