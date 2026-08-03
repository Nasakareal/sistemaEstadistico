<?php

namespace Tests\Unit;

use App\Services\Inegi\InegiChoquesExcelGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class InegiChoquesExcelGeneratorTest extends TestCase
{
    public function test_fila_usa_clave_municipal_y_deja_vacias_las_columnas_otro(): void
    {
        $lesionados = collect([
            (object) ['tipo_lesion' => 'Leve', 'tipo_victima' => 'Conductor'],
            (object) ['tipo_lesion' => 'Fallecido', 'tipo_victima' => 'Pasajero'],
            (object) ['tipo_lesion' => 'Leve', 'tipo_victima' => null],
        ]);

        $fila = $this->filaInegi('Pátzcuaro', $lesionados);

        $this->assertSame(66, $fila['MPIO']);
        $this->assertSame(2, $fila['HERIDOS']);
        $this->assertSame(1, $fila['MUERTOS']);
        $this->assertSame(1, $fila['CONDHERIDO']);
        $this->assertSame(1, $fila['PASAMUERTO']);
        $this->assertNull($fila['OTROMUERTO']);
        $this->assertNull($fila['OTROHERIDO']);
    }

    private function filaInegi(string $municipio, Collection $lesionados): array
    {
        $method = new ReflectionMethod(InegiChoquesExcelGenerator::class, 'filaInegi');
        $method->setAccessible(true);

        return $method->invoke(
            new InegiChoquesExcelGenerator(),
            (object) ['id' => 1, 'municipio' => $municipio],
            Carbon::parse('2026-07-01'),
            collect(),
            collect(),
            $lesionados
        );
    }
}
