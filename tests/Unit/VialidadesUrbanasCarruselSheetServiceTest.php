<?php

namespace Tests\Unit;

use App\Services\VialidadesUrbanas\Hojas\CarruselSheetService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ReflectionMethod;
use Tests\TestCase;

class VialidadesUrbanasCarruselSheetServiceTest extends TestCase
{
    public function test_cuenta_unidades_participantes_sin_sumar_numeros_economicos(): void
    {
        $this->assertSame(4, $this->invokeInt('contarUnidadesTexto', '4'));
        $this->assertSame(3, $this->invokeInt('contarUnidadesTexto', '3214, 3178, 04-174'));
        $this->assertSame(2, $this->invokeInt('contarUnidadesTexto', "MOTO 1\nMOTO 2"));
    }

    public function test_cuenta_cantidades_de_estado_de_fuerza(): void
    {
        $this->assertSame(5, $this->invokeInt('contarCantidadTexto', '5 elementos'));
        $this->assertSame(7, $this->invokeInt('contarCantidadTexto', '3 oficiales, 4 auxiliares'));
        $this->assertSame(2, $this->invokeInt('contarCantidadTexto', 'Juan; Pedro'));
    }

    public function test_renderiza_tablas_de_ubicacion_y_tramo_carretero(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $style = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ];

        $method = new ReflectionMethod(CarruselSheetService::class, 'renderReferencias');
        $method->setAccessible(true);
        $method->invoke(new CarruselSheetService(), $sheet, 7, $style, $style);

        $this->assertSame('UBICACIÓN', $sheet->getCell('B7')->getValue());
        $this->assertSame('NOMBRE', $sheet->getCell('C7')->getValue());
        $this->assertSame('A', $sheet->getCell('B8')->getValue());
        $this->assertSame('L', $sheet->getCell('B19')->getValue());
        $this->assertSame('TRAMO CARRETERO', $sheet->getCell('B20')->getValue());
        $this->assertSame('Ñ', $sheet->getCell('B21')->getValue());
        $this->assertSame('Z', $sheet->getCell('B32')->getValue());
    }

    private function invokeInt(string $methodName, string $value): int
    {
        $method = new ReflectionMethod(CarruselSheetService::class, $methodName);
        $method->setAccessible(true);

        return (int) $method->invoke(new CarruselSheetService(), $value);
    }
}
