<?php

namespace Tests\Unit;

use App\Models\Personal;
use App\Models\Unidad;
use App\Services\Personal\PersonalExcelImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PersonalExcelImportServiceTest extends TestCase
{
    use DatabaseTransactions;

    private $archivos = [];

    protected function tearDown(): void
    {
        foreach ($this->archivos as $archivo) {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }

        parent::tearDown();
    }

    public function test_analiza_la_plantilla_normaliza_catalogos_e_ignora_columnas_ajenas(): void
    {
        $archivo = $this->crearPlantilla();
        $resultado = (new PersonalExcelImportService())->analizarArchivo($archivo, 9876);
        $atributos = $resultado['registros'][0]['atributos'];

        $this->assertSame(9876, $atributos['unidad_id']);
        $this->assertSame('NANCY YURITZI', $atributos['nombre']);
        $this->assertSame('CONTRERAS', $atributos['ap_paterno']);
        $this->assertSame('VEGA', $atributos['ap_materno']);
        $this->assertSame('2026-01-26', $atributos['fecha_ingreso_unidad']);
        $this->assertSame('1988-01-11', $atributos['fecha_nacimiento']);
        $this->assertSame('A_POSITIVO', $atributos['tipo_sangre']);
        $this->assertSame('BACHILLERATO', $atributos['ultimo_grado_estudios']);
        $this->assertSame('elemento@example.com', $atributos['correo_electronico']);
        $this->assertArrayNotHasKey('foto', $atributos);
        $this->assertArrayNotHasKey('arma_corta', $atributos);
        $this->assertArrayNotHasKey('unidad_excel', $atributos);
    }

    public function test_reimportar_no_mueve_personal_a_otra_unidad(): void
    {
        $origen = Unidad::query()->create([
            'nombre' => 'Unidad importación origen ' . uniqid(),
            'slug' => 'unidad-importacion-origen-' . uniqid(),
            'activa' => true,
        ]);
        $otra = Unidad::query()->create([
            'nombre' => 'Unidad importación destino ' . uniqid(),
            'slug' => 'unidad-importacion-destino-' . uniqid(),
            'activa' => true,
        ]);
        $archivo = $this->crearPlantilla();
        $servicio = new PersonalExcelImportService();

        $primera = $servicio->importar($archivo, $origen->id);
        $segunda = $servicio->importar($archivo, $otra->id);

        $this->assertSame(1, $primera['importados']);
        $this->assertSame(0, $segunda['importados']);
        $this->assertSame(1, $segunda['omitidos']);
        $this->assertDatabaseHas('personals', [
            'curp' => 'TSTX880111MMNNGN01',
            'unidad_id' => $origen->id,
        ]);
        $this->assertDatabaseMissing('personals', [
            'curp' => 'TSTX880111MMNNGN01',
            'unidad_id' => $otra->id,
        ]);
    }

    private function crearPlantilla(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BASE DE DATOS');
        $sheet->fromArray([
            'No.',
            'FOTO',
            'FECHA DE INGRESO AL GRUPO',
            'NOMBRE COMPLETO',
            'FECHA DE NACIMIENTO',
            'CARGO Y/O FUNCIONES',
            'TIPO SANGUINEO',
            'ULTIMO GRADO DE ESTUDIOS',
            'NSS',
            'ALERGÍAS',
            'RFC',
            'CURP',
            'CUIP',
            'CUP',
            'CORREO ELECTRONICO PERSONA',
            'SI CUENTA CON ARMA COLOCAR MARCA Y MATRICULA',
            'UNIDAD',
        ], null, 'A10');
        $sheet->fromArray([
            1,
            'foto.jpg',
            ExcelDate::PHPToExcel(new \DateTimeImmutable('2026-01-26')),
            'CONTRERAS VEGA NANCY YURITZI',
            ExcelDate::PHPToExcel(new \DateTimeImmutable('1988-01-11')),
            'POLICIA',
            'A POSITIVO',
            'BACHILLERATO',
            '98765432109',
            'NINGUNA',
            'TSTX880111AB1',
            'TSTX880111MMNNGN01',
            'CUIP-IMPORT-TEST-0001',
            'CUP-IMPORT-TEST-0001',
            'ELEMENTO@EXAMPLE.COM',
            'PISTOLA DE PRUEBA',
            999999,
        ], null, 'A12');

        $base = tempnam(sys_get_temp_dir(), 'personal_excel_');
        $archivo = $base . '.xlsx';
        @unlink($base);
        (new Xlsx($spreadsheet))->save($archivo);
        $spreadsheet->disconnectWorksheets();
        $this->archivos[] = $archivo;

        return $archivo;
    }
}
