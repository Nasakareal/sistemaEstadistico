<?php

namespace Tests\Unit;

use App\Models\Personal;
use App\Models\Unidad;
use App\Services\Personal\PersonalExcelImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
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

    public function test_importa_telefono_particular_y_referencias_familiares_combinadas(): void
    {
        $unidad = Unidad::query()->create([
            'nombre' => 'Unidad contactos importados ' . uniqid(),
            'slug' => 'unidad-contactos-importados-' . uniqid(),
            'activa' => true,
        ]);
        $archivo = $this->crearPlantillaConContactos();
        $servicio = new PersonalExcelImportService();
        $analisis = $servicio->analizarArchivo($archivo, $unidad->id);

        $this->assertSame('4436068835', $analisis['registros'][0]['contactos'][0]['telefono_personal']);
        $this->assertCount(2, $analisis['registros'][0]['emergencias']);
        $this->assertSame('LOURDES VEGA', $analisis['registros'][0]['emergencias'][0]['nombre_contacto']);
        $this->assertSame('MAMÁ', $analisis['registros'][0]['emergencias'][0]['parentesco']);
        $this->assertSame('4531382845', $analisis['registros'][0]['emergencias'][0]['telefono_emergencia']);

        $resultado = $servicio->importar($archivo, $unidad->id);
        $personal = Personal::query()->where('curp', 'TSTX880111MMNNGN01')->firstOrFail();

        $this->assertSame(1, $resultado['importados']);
        $this->assertSame(1, $resultado['contactos_importados']);
        $this->assertSame(2, $resultado['emergencias_importadas']);
        $this->assertDatabaseHas('personal_contactos', [
            'personal_id' => $personal->id,
            'telefono_personal' => '4436068835',
        ]);
        $this->assertDatabaseHas('personal_emergencias', [
            'personal_id' => $personal->id,
            'nombre_contacto' => 'JONATHAN GARCIA',
            'parentesco' => 'ESPOSO',
            'telefono_emergencia' => '4433685592',
        ]);
    }

    public function test_reimportar_complementa_contactos_sin_mover_personal_ni_duplicarlos(): void
    {
        $origen = Unidad::query()->create([
            'nombre' => 'Unidad contactos origen ' . uniqid(),
            'slug' => 'unidad-contactos-origen-' . uniqid(),
            'activa' => true,
        ]);
        $otra = Unidad::query()->create([
            'nombre' => 'Unidad contactos destino ' . uniqid(),
            'slug' => 'unidad-contactos-destino-' . uniqid(),
            'activa' => true,
        ]);
        $servicio = new PersonalExcelImportService();

        $servicio->importar($this->crearPlantilla(), $origen->id);
        $complemento = $servicio->importar($this->crearPlantillaConContactos(), $otra->id);
        $repeticion = $servicio->importar($this->crearPlantillaConContactos(), $otra->id);
        $personal = Personal::query()->where('curp', 'TSTX880111MMNNGN01')->firstOrFail();

        $this->assertSame(0, $complemento['importados']);
        $this->assertSame(1, $complemento['complementados']);
        $this->assertSame(0, $complemento['omitidos']);
        $this->assertSame($origen->id, $personal->unidad_id);
        $this->assertSame(1, $personal->contactos()->count());
        $this->assertSame(2, $personal->emergencias()->count());
        $this->assertSame(0, $repeticion['complementados']);
        $this->assertSame(1, $repeticion['omitidos']);
        $this->assertSame(1, $personal->contactos()->count());
        $this->assertSame(2, $personal->emergencias()->count());
    }

    public function test_reimportar_restaura_personal_eliminado_logicamente(): void
    {
        $origen = Unidad::query()->create([
            'nombre' => 'Unidad restauración origen ' . uniqid(),
            'slug' => 'unidad-restauracion-origen-' . uniqid(),
            'activa' => true,
        ]);
        $destino = Unidad::query()->create([
            'nombre' => 'Unidad restauración destino ' . uniqid(),
            'slug' => 'unidad-restauracion-destino-' . uniqid(),
            'activa' => true,
        ]);
        $servicio = new PersonalExcelImportService();

        $servicio->importar($this->crearPlantilla(), $origen->id);
        $personal = Personal::query()->where('curp', 'TSTX880111MMNNGN01')->firstOrFail();
        $idOriginal = $personal->id;
        $personal->delete();

        $resultado = $servicio->importar($this->crearPlantillaConContactos(), $destino->id);
        $restaurado = Personal::query()->whereKey($idOriginal)->firstOrFail();

        $this->assertSame(0, $resultado['importados']);
        $this->assertSame(1, $resultado['restaurados']);
        $this->assertSame(0, $resultado['omitidos']);
        $this->assertSame($destino->id, $restaurado->unidad_id);
        $this->assertNull($restaurado->deleted_at);
        $this->assertSame(1, $restaurado->contactos()->count());
        $this->assertSame(2, $restaurado->emergencias()->count());
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

    private function crearPlantillaConContactos(): string
    {
        $archivo = $this->crearPlantilla();
        $spreadsheet = IOFactory::load($archivo);
        $sheet = $spreadsheet->getSheetByName('BASE DE DATOS');
        $sheet->setCellValue('R10', 'TELEFONO PARTICULAR');
        $sheet->setCellValue('S10', 'REFERENCIAS FAMILIARES');
        $sheet->mergeCells('S10:T10');
        $sheet->setCellValue('S11', '1');
        $sheet->setCellValue('T11', '2');
        $sheet->setCellValue('R12', '4436068835');
        $sheet->setCellValue('S12', "LOURDES VEGA\n(MAMÁ)\n4531382845");
        $sheet->setCellValue('T12', "JONATHAN GARCIA (ESPOSO)\n4433685592");
        (new Xlsx($spreadsheet))->save($archivo);
        $spreadsheet->disconnectWorksheets();

        return $archivo;
    }
}
