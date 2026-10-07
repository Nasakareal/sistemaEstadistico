<?php

namespace Tests\Unit;

use App\Models\PuestaDisposicion;
use App\Models\PuestaDisposicionVehiculo;
use App\Services\Carreteras\PuestasCarreterasExcelImportService;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class PuestasCarreterasExcelImportServiceTest extends TestCase
{
    public function test_extrae_vehiculo_y_objetos_del_texto_historico(): void
    {
        $service = new PuestasCarreterasExcelImportService();
        $vehicleMethod = new \ReflectionMethod($service, 'parseImportedVehicle');
        $vehicleMethod->setAccessible(true);
        $objectMethod = new \ReflectionMethod($service, 'parseImportedObject');
        $objectMethod->setAccessible(true);

        $vehicle = $vehicleMethod->invoke($service, [
            'descripcion_origen' => '1 PERSONA, 1 CAMIONETA DE LA MARCA CHEVROLET SILVERADO MODELO 2021 TIPO PICK UP COLOR GRIS CON PLACAS DE LA UCD-G10242 SERIE: 1GCPYFELXMZ375490',
            'motivo_destino' => 'OTRO',
        ]);
        $this->assertSame('CAMIONETA', $vehicle['tipo']);
        $this->assertSame('CHEVROLET', $vehicle['marca']);
        $this->assertSame('SILVERADO', $vehicle['submarca']);
        $this->assertSame('2021', $vehicle['modelo']);
        $this->assertSame('GRIS', $vehicle['color']);
        $this->assertSame('G10242', $vehicle['placas']);
        $this->assertSame('1GCPYFELXMZ375490', $vehicle['serie']);

        $object = $objectMethod->invoke($service, [
            'descripcion_origen' => '3 PERSONAS CON 1 ARMA, 9 CARTUCHOS, 11 ENVOLTORIOS DE DROGA Y $565,000.00',
            'rnd' => 'RND-1',
        ]);
        $this->assertSame('DIVERSOS INDICIOS', $object['tipo_objeto']);
        $this->assertSame('RND-1', $object['cadena_custodia']);
    }

    public function test_separa_nombres_de_detenidos_sin_crear_registros_para_sin_detenidos(): void
    {
        $method = new \ReflectionMethod(PuestasCarreterasExcelImportService::class, 'parseDetainedNames');
        $method->setAccessible(true);
        $service = new PuestasCarreterasExcelImportService();

        $this->assertSame([
            'LUIS BASURTO SANCHEZ',
            'ANGEL GABRIEL CISNEROS ANDRADE',
            'ANTONIO SANCHEZ BASURTO',
        ], $method->invoke($service, 'LUIS BASURTO SANCHEZ, ANGEL GABRIEL CISNEROS ANDRADE Y ANTONIO SANCHEZ BASURTO'));
        $this->assertSame([], $method->invoke($service, 'SIN DETENIDOS'));
    }

    public function test_distingue_puestas_del_mismo_dia_por_identificadores_del_vehiculo(): void
    {
        $existing = new PuestaDisposicion();
        $existing->setRelation('vehiculos', collect([
            new PuestaDisposicionVehiculo([
                'tipo' => 'CAJA SECA',
                'marca' => 'FRUEHAUF',
                'modelo' => '2024',
                'placas' => '63-UX-9R (S.A.F.)',
                'serie' => '3AWVF4028SX457040',
            ]),
        ]));

        $method = new \ReflectionMethod(PuestasCarreterasExcelImportService::class, 'vehicleMatchRank');
        $method->setAccessible(true);
        $service = new PuestasCarreterasExcelImportService();

        $differentVehicle = $method->invoke($service, [
            'descripcion_origen' => '1 CAJA SECA FRUEHAUF MODELO 2019 PLACAS 97-UW-4C SERIE 3AWV24KX176008',
        ], $existing);
        $matchingVehicle = $method->invoke($service, [
            'descripcion_origen' => '1 CAJA SECA FRUEHAUF MODELO 2024 PLACAS 63-UX-9R SERIE 3AWVF028SX457040',
        ], $existing);

        $this->assertNull($differentVehicle);
        $this->assertGreaterThanOrEqual(100, $matchingVehicle);
    }

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
