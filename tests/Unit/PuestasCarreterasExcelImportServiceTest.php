<?php

namespace Tests\Unit;

use App\Services\Carreteras\PuestasCarreterasExcelImportService;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class PuestasCarreterasExcelImportServiceTest extends TestCase
{
    public function test_analiza_el_detalle_y_advierte_totales_inconsistentes(): void
    {
        $workbook = new Spreadsheet();
        $sheet = $workbook->getActiveSheet();
        $sheet->setTitle("IPH´S");
        $sheet->fromArray([
            null,
            'FOLIO',
            'NÚMERO',
            'FECHA',
            'DESTACAMENTO',
            'MUNICIPIO DE LA PUESTA',
            'FALTA ADMINISTRATIVA',
            'DETENCIÓN',
            'ASEGURAMIENTO',
            'SEXO',
            'CUANTOS MENORES DE EDAD',
            'MOTIVO',
            '1ER RESPONDIENTE',
            'PERSONAL QUE PARTICIPA EN LA PUESTA A DISPOSICIÓN',
            'DETENIDOS EN LA PUESTA',
            'RND',
            'CARPETA DE INVESTIGACIÓN',
            'MP COMUN O FEDERAL',
        ], null, 'A6');
        $sheet->fromArray([
            1,
            'F-1',
            null,
            ExcelDate::PHPToExcel(new \DateTimeImmutable('2026-01-12')),
            'MORELIA II',
            'MORELIA',
            1,
            1,
            2,
            'H',
            0,
            'DESCRIPCION AMPLIA DEL HECHO',
            'JUAN PEREZ LOPEZ',
            'MARIA PEREZ',
            'PERSONA UNO',
            'RND-1',
            'CI-1',
            'COMUN',
        ], null, 'A7');
        $sheet->fromArray([
            2,
            'F-2',
            1,
            ExcelDate::PHPToExcel(new \DateTimeImmutable('2026-01-13')),
            'MORELIA',
            'MORELIA',
            null,
            null,
            1,
            null,
            null,
            'VEHICULO CON REPORTE DE ROBO',
            'PEDRO GARCIA',
            null,
            null,
            null,
            null,
            'FEDERAL',
        ], null, 'A8');
        $sheet->fromArray([null, null, null, null, null, null, 9, 65, 155], null, 'A10');

        $summary = $workbook->createSheet();
        $summary->setTitle('CONCENTRADO');
        $summary->fromArray(['TOTAL', 2, 108, 10, 64], null, 'A3');

        $path = tempnam(sys_get_temp_dir(), 'iph_import_') . '.xlsx';
        (new Xlsx($workbook))->save($path);

        try {
            $result = (new PuestasCarreterasExcelImportService())->analizarArchivo($path);

            $this->assertSame(2, $result['totales_detalle']['puestas']);
            $this->assertSame(1, $result['totales_detalle']['faltas']);
            $this->assertSame(1, $result['totales_detalle']['detenciones']);
            $this->assertSame(3, $result['totales_detalle']['aseguramientos']);
            $this->assertSame('2026-01-12', $result['registros'][0]['fecha_puesta']);
            $this->assertSame('MORELIA II', $result['registros'][0]['destacamento']);
            $this->assertNotEmpty($result['advertencias']);
            $this->assertEmpty($result['errores']);
        } finally {
            @unlink($path);
        }
    }
}
