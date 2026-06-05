<?php

namespace Tests\Unit;

use App\Services\VialidadesUrbanas\Hojas\TotalSheetService;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ReflectionMethod;
use Tests\TestCase;

class VialidadesUrbanasTotalSheetServiceTest extends TestCase
{
    public function test_agrupa_actividades_reales_en_fila_de_escuelas(): void
    {
        $rows = $this->construirFilas(collect([
            (object) [
                'categoria' => (object) ['nombre' => 'INSTITUCIONES'],
                'subcategoria' => (object) ['nombre' => 'ESCUELAS'],
                'nombre' => 'ESCUELAS',
                'cantidad' => 8,
                'elementos_participantes_texto' => '16',
                'patrullas_participantes_texto' => '3214, 3178, 04-174, 3300',
                'km_recorridos' => 12.5,
                'personas_alcanzadas' => 350,
                'fomentoCulturaVialDetalle' => null,
                'motivo' => null,
                'narrativa' => null,
                'acciones_realizadas' => null,
                'observaciones' => 'RECOMENDACIONES: 20',
            ],
        ]));

        $escuelas = collect($rows)->firstWhere('actividad', 'ESCUELAS');

        $this->assertSame(8, $escuelas['cantidad']);
        $this->assertSame(16, $escuelas['estado_fuerza']);
        $this->assertSame(4, $escuelas['unidades']);
        $this->assertSame(12.5, $escuelas['kilometros']);
        $this->assertSame(350, $escuelas['personas']);
        $this->assertSame(20, $escuelas['recomendaciones']);
    }

    public function test_renderiza_inicio_de_hoja_total(): void
    {
        $rows = $this->construirFilas(collect());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows);

        $this->assertSame('VIALIDADES URBANAS', $sheet->getCell('C1')->getValue());
        $this->assertSame('FECHA', $sheet->getCell('B2')->getValue());
        $this->assertSame('02/06/2026', $sheet->getCell('C2')->getValue());
        $this->assertSame('No.', $sheet->getCell('A3')->getValue());
        $this->assertSame('CATEGORÍA', $sheet->getCell('B3')->getValue());
        $this->assertSame('ACTIVIDAD', $sheet->getCell('C3')->getValue());
        $this->assertSame('APOYO A EVENTOS PÚBLICOS', $sheet->getCell('C4')->getValue());
        $this->assertSame('ESCUELAS', $sheet->getCell('C9')->getValue());
    }

    public function test_renderiza_bloque_reportes_c5i_inmediatamente_despues(): void
    {
        $rows = $this->construirFilas(collect());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows);

        $this->assertSame('OBSTRUCCIÓN DE COCHERAS', $sheet->getCell('C12')->getValue());
        $this->assertSame('OTROS TIPOS DE OBSTRUCCIÓN', $sheet->getCell('C13')->getValue());
        $this->assertSame('ACTOS DELICTIVOS', $sheet->getCell('C14')->getValue());
        $this->assertSame('SINIESTROS', $sheet->getCell('C15')->getValue());
        $this->assertSame('HECHOS DE TRÁNSITO', $sheet->getCell('C16')->getValue());
        $this->assertSame('CONSENTRACION PERSONAS', $sheet->getCell('C17')->getValue());
        $this->assertSame('OTROS REPORTES (Especificar en las novedades relevantes)', $sheet->getCell('C18')->getValue());
        $this->assertSame('A12:A18', $sheet->getCell('A12')->getMergeRange());
        $this->assertSame('B12:B18', $sheet->getCell('B12')->getMergeRange());
        $this->assertSame('9BC2E6', $sheet->getStyle('C13')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_bloque_abanderamientos_inmediatamente_despues(): void
    {
        $rows = $this->construirFilas(collect());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows);

        $this->assertSame('CORTES DE CIRCULACIÓN', $sheet->getCell('C19')->getValue());
        $this->assertSame('ACCIDENTES', $sheet->getCell('C20')->getValue());
        $this->assertSame('MARCHAS', $sheet->getCell('C21')->getValue());
        $this->assertSame('MÍTINES', $sheet->getCell('C22')->getValue());
        $this->assertSame('OBRAS PÚBLICAS', $sheet->getCell('C23')->getValue());
        $this->assertSame('ACOMPAÑAMIENTO A CARAVANAS U OTROS', $sheet->getCell('C24')->getValue());
        $this->assertSame('OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)', $sheet->getCell('C25')->getValue());
        $this->assertSame('A19:A25', $sheet->getCell('A19')->getMergeRange());
        $this->assertSame('B19:B25', $sheet->getCell('B19')->getMergeRange());
        $this->assertSame('FFFFFF', $sheet->getStyle('C20')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_bloque_operativos_con_mismas_filas_de_la_plantilla(): void
    {
        $rows = $this->construirFilas(collect());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows);

        $this->assertSame('ESCUELA SEGURA', $sheet->getCell('C26')->getValue());
        $this->assertSame('CONEXIÓN INSTITUCIONAL', $sheet->getCell('C27')->getValue());
        $this->assertSame('RESPUESTA VIAL INMEDIATA', $sheet->getCell('C28')->getValue());
        $this->assertSame('ABANDERAMIENTO ACTIVO', $sheet->getCell('C29')->getValue());
        $this->assertSame('PASO CONTINUO', $sheet->getCell('C30')->getValue());
        $this->assertSame('', $sheet->getCell('C31')->getValue());
        $this->assertSame('', $sheet->getCell('C32')->getValue());
        $this->assertSame('', $sheet->getCell('C33')->getValue());
        $this->assertSame('', $sheet->getCell('C34')->getValue());
        $this->assertSame('', $sheet->getCell('C35')->getValue());
        $this->assertSame('A26:A35', $sheet->getCell('A26')->getMergeRange());
        $this->assertSame('B26:B35', $sheet->getCell('B26')->getMergeRange());
        $this->assertSame('9BC2E6', $sheet->getStyle('C30')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_bloques_programas_monitoreos_y_auxilio_vial(): void
    {
        $rows = $this->construirFilas(collect());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows);

        $this->assertSame('CONDUCE SIN ALCOHOL (ALCOHOLÍMETRO)', $sheet->getCell('C36')->getValue());
        $this->assertSame('OTROS PROGRAMAS (Especificar en las novedades relevantes)', $sheet->getCell('C37')->getValue());
        $this->assertSame('A36:A37', $sheet->getCell('A36')->getMergeRange());
        $this->assertSame('B36:B37', $sheet->getCell('B36')->getMergeRange());

        $this->assertSame('VÍAS FÉRREAS', $sheet->getCell('C38')->getValue());
        $this->assertSame('PERIFÉRICOS', $sheet->getCell('C39')->getValue());
        $this->assertSame('AVENIDAS', $sheet->getCell('C40')->getValue());
        $this->assertSame('TIENDAS DEPARTAMENTALES', $sheet->getCell('C41')->getValue());
        $this->assertSame('BANCOS', $sheet->getCell('C42')->getValue());
        $this->assertSame('GASOLINERAS', $sheet->getCell('C43')->getValue());
        $this->assertSame('OFICINAS GUBERNAMENTALES', $sheet->getCell('C44')->getValue());
        $this->assertSame('MANIFESTACIONES', $sheet->getCell('C45')->getValue());
        $this->assertSame('OTROS MONITOREOS (Especificar en las novedades relevantes)', $sheet->getCell('C46')->getValue());
        $this->assertSame('A38:A46', $sheet->getCell('A38')->getMergeRange());
        $this->assertSame('B38:B46', $sheet->getCell('B38')->getMergeRange());
        $this->assertSame('9BC2E6', $sheet->getStyle('C38')->getFill()->getStartColor()->getRGB());

        $this->assertSame('FALLAS MECÁNICAS', $sheet->getCell('C47')->getValue());
        $this->assertSame('PEATÓN', $sheet->getCell('C48')->getValue());
        $this->assertSame('ESCOLTA EN SITUACIONES DE EMERGENCIA', $sheet->getCell('C49')->getValue());
        $this->assertSame('AGRICOLAS', $sheet->getCell('C50')->getValue());
        $this->assertSame('OTROS AUXILIOS (Especificar en las novedades relevantes)', $sheet->getCell('C51')->getValue());
        $this->assertSame('A47:A51', $sheet->getCell('A47')->getMergeRange());
        $this->assertSame('B47:B51', $sheet->getCell('B47')->getMergeRange());
        $this->assertSame('FFFFFF', $sheet->getStyle('C47')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_bloques_dispositivos_seguridad_vial_y_capacitaciones(): void
    {
        $rows = $this->construirFilas(collect());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows);

        $this->assertSame('APOYO A LA VIALIDAD', $sheet->getCell('C52')->getValue());
        $this->assertSame('PASO LIBRE DE FUNCIONARIOS', $sheet->getCell('C53')->getValue());
        $this->assertSame('ZONAS DE MAYOR PASE DE TRANSEÚNTES', $sheet->getCell('C54')->getValue());
        $this->assertSame('PASOS PEATONALES', $sheet->getCell('C55')->getValue());
        $this->assertSame('MEDIDAS DE PROTECCIÓN', $sheet->getCell('C56')->getValue());
        $this->assertSame('PATRULLAJES', $sheet->getCell('C57')->getValue());
        $this->assertSame('SERVICIOS DE ESCOLTAS', $sheet->getCell('C58')->getValue());
        $this->assertSame('OTROS (Especificar en las novedades relevantes)', $sheet->getCell('C59')->getValue());
        $this->assertSame('A52:A59', $sheet->getCell('A52')->getMergeRange());
        $this->assertSame('B52:B59', $sheet->getCell('B52')->getMergeRange());
        $this->assertSame('9BC2E6', $sheet->getStyle('C52')->getFill()->getStartColor()->getRGB());

        $this->assertSame('TALLER EDUCACIÓN SEGURIDAD VIAL', $sheet->getCell('C60')->getValue());
        $this->assertSame('CAMPAÑA EDUCACIÓN SEGURIDAD VIAL', $sheet->getCell('C61')->getValue());
        $this->assertSame('CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL', $sheet->getCell('C62')->getValue());
        $this->assertSame('MÓDULOS EDUCACIÓN SEGURIDAD VIAL', $sheet->getCell('C63')->getValue());
        $this->assertSame('SSP', $sheet->getCell('C64')->getValue());
        $this->assertSame('CALEA', $sheet->getCell('C65')->getValue());
        $this->assertSame('OTRAS (Especificar en las novedades relevantes)', $sheet->getCell('C66')->getValue());
        $this->assertSame('A60:A66', $sheet->getCell('A60')->getMergeRange());
        $this->assertSame('B60:B66', $sheet->getCell('B60')->getMergeRange());
        $this->assertSame('FFFFFF', $sheet->getStyle('C60')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_cierre_con_campanas_proximidad_y_total_sin_fila_vacia(): void
    {
        $rows = $this->construirFilas(collect());
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows);

        $this->assertSame('CONCIENTIZACIÓN Y PREVENCIÓN', $sheet->getCell('C67')->getValue());
        $this->assertSame('REPARTICIÓN DE TRÍPTICOS', $sheet->getCell('C68')->getValue());
        $this->assertSame('ESTACIONALES (SEMANA SANTA, NAVIDAD ETC.)', $sheet->getCell('C69')->getValue());
        $this->assertSame('OTRAS (Especificar en las novedades relevantes)', $sheet->getCell('C70')->getValue());
        $this->assertSame('A67:A70', $sheet->getCell('A67')->getMergeRange());
        $this->assertSame('B67:B70', $sheet->getCell('B67')->getMergeRange());
        $this->assertSame('9BC2E6', $sheet->getStyle('C67')->getFill()->getStartColor()->getRGB());

        $this->assertSame('PREVENCIÓN SOCIAL', $sheet->getCell('C71')->getValue());
        $this->assertSame('RECORRIDOS DE PROXIMIDAD', $sheet->getCell('C72')->getValue());
        $this->assertSame('APOYO A TURISTAS', $sheet->getCell('C73')->getValue());
        $this->assertSame('APOYO A PERSONAS DE LA TERCERA EDAD', $sheet->getCell('C74')->getValue());
        $this->assertSame('APOYO A PERSONAS PERDIDAS', $sheet->getCell('C75')->getValue());
        $this->assertSame('RECUPERACIÓN DE ESPACIOS', $sheet->getCell('C76')->getValue());
        $this->assertSame('OTRAS (Especificar en las novedades relevantes)', $sheet->getCell('C77')->getValue());
        $this->assertSame('A71:A77', $sheet->getCell('A71')->getMergeRange());
        $this->assertSame('B71:B77', $sheet->getCell('B71')->getMergeRange());
        $this->assertSame('FFFFFF', $sheet->getStyle('C71')->getFill()->getStartColor()->getRGB());

        $this->assertSame('TOTAL', $sheet->getCell('A78')->getValue());
        $this->assertSame('DISPOSITIVOS REALIZADOS', $sheet->getCell('C78')->getValue());
        $this->assertSame('A78:B78', $sheet->getCell('A78')->getMergeRange());
        $this->assertSame(0, $sheet->getCell('D78')->getValue());
        $this->assertSame(0, $sheet->getCell('G78')->getValue());
        $this->assertSame(0, $sheet->getCell('I78')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('A78')->getFill()->getStartColor()->getRGB());
        $this->assertSame('000000', $sheet->getStyle('A78')->getFont()->getColor()->getRGB());
    }

    public function test_renderiza_control_vehicular_despues_de_fila_blanca(): void
    {
        $rows = $this->construirFilas(collect());
        $control = $this->controlVehicularVacio();
        $control['REVISION_ANTECEDENTES']['vehiculos'] = 2;
        $control['REVISION_ANTECEDENTES']['motocicletas'] = 1;
        $control['CORRALON_TRANSITO']['camiones'] = 1;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows, $control);

        $this->assertNull($sheet->getCell('B79')->getValue());
        $this->assertSame('No.', $sheet->getCell('B80')->getValue());
        $this->assertSame('CONTROL VEHÍCULAR', $sheet->getCell('C80')->getValue());
        $this->assertSame('VEHÍCULOS', $sheet->getCell('D80')->getValue());
        $this->assertSame('REVISIÓN DE ANTECEDENTES', $sheet->getCell('C81')->getValue());
        $this->assertSame(2, $sheet->getCell('D81')->getValue());
        $this->assertSame(1, $sheet->getCell('E81')->getValue());
        $this->assertSame('ASEGURADOS POR OTROS MOTIVOS', $sheet->getCell('C93')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('B94')->getValue());
        $this->assertSame('B94:C94', $sheet->getCell('B94')->getMergeRange());
        $this->assertSame(2, $sheet->getCell('D94')->getValue());
        $this->assertSame(1, $sheet->getCell('E94')->getValue());
        $this->assertSame(1, $sheet->getCell('F94')->getValue());
        $this->assertSame(0, $sheet->getCell('G94')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('B94')->getFill()->getStartColor()->getRGB());
    }

    public function test_clasifica_control_vehicular_por_texto_y_tipo(): void
    {
        $service = new TotalSheetService();
        $keysMethod = new ReflectionMethod(TotalSheetService::class, 'clavesControlVehicular');
        $keysMethod->setAccessible(true);
        $bucketMethod = new ReflectionMethod(TotalSheetService::class, 'bucketControlVehicular');
        $bucketMethod->setAccessible(true);

        $actividad = (object) [
            'categoria' => (object) ['nombre' => 'OPERATIVOS'],
            'subcategoria' => (object) ['nombre' => 'CONTROL'],
            'nombre' => 'CONTROL',
            'motivo' => 'RECUPERADOS CON REPORTE DE ROBO',
            'narrativa' => null,
            'acciones_realizadas' => null,
            'observaciones' => null,
        ];
        $vehiculo = (object) [
            'tipo' => 'Motocicleta',
            'antecedente_vehiculo' => 1,
            'corralon' => null,
        ];

        $keys = $keysMethod->invoke($service, $actividad, $vehiculo);

        $this->assertContains('REVISION_ANTECEDENTES', $keys);
        $this->assertContains('REC_ROBO', $keys);
        $this->assertSame('motocicletas', $bucketMethod->invoke($service, 'Motocicleta'));
        $this->assertSame('camiones', $bucketMethod->invoke($service, 'Camión'));
        $this->assertSame('vehiculos', $bucketMethod->invoke($service, 'Camioneta SUV'));
    }

    private function construirFilas(Collection $actividades): array
    {
        $method = new ReflectionMethod(TotalSheetService::class, 'construirFilas');
        $method->setAccessible(true);

        return $method->invoke(new TotalSheetService(), $actividades, collect());
    }

    private function controlVehicularVacio(): array
    {
        $method = new ReflectionMethod(TotalSheetService::class, 'controlesVehicularesVacios');
        $method->setAccessible(true);

        return $method->invoke(new TotalSheetService());
    }
}
