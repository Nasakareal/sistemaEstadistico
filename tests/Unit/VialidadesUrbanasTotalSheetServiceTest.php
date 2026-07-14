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

    public function test_suma_unidades_participantes_sin_sumar_numero_economico_literal(): void
    {
        $rows = $this->construirFilas(collect([
            (object) [
                'categoria' => (object) ['nombre' => 'INSTITUCIONES'],
                'subcategoria' => (object) ['nombre' => 'ESCUELAS'],
                'nombre' => 'ESCUELAS',
                'cantidad' => 1,
                'elementos_participantes_texto' => null,
                'patrullas_participantes_texto' => '2637',
                'km_recorridos' => 0,
                'personas_alcanzadas' => 0,
                'fomentoCulturaVialDetalle' => null,
                'motivo' => null,
                'narrativa' => null,
                'acciones_realizadas' => null,
                'observaciones' => null,
            ],
            (object) [
                'categoria' => (object) ['nombre' => 'INSTITUCIONES'],
                'subcategoria' => (object) ['nombre' => 'ESCUELAS'],
                'nombre' => 'ESCUELAS',
                'cantidad' => 1,
                'elementos_participantes_texto' => null,
                'patrullas_participantes_texto' => '2',
                'km_recorridos' => 0,
                'personas_alcanzadas' => 0,
                'fomentoCulturaVialDetalle' => null,
                'motivo' => null,
                'narrativa' => null,
                'acciones_realizadas' => null,
                'observaciones' => null,
            ],
        ]));

        $escuelas = collect($rows)->firstWhere('actividad', 'ESCUELAS');

        $this->assertSame(3, $escuelas['unidades']);
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
        $this->assertSame('OTROS OPERATIVOS (Especificar en las novedades relevantes)', $sheet->getCell('C31')->getValue());
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

    public function test_renderiza_control_de_aseguramientos_despues_de_fila_blanca(): void
    {
        $rows = $this->construirFilas(collect());
        $controlVehicular = $this->controlVehicularVacio();
        $controlAseguramientos = $this->controlAseguramientosVacio();
        $controlAseguramientos['personas']['ALCOHOLEMIA'] = 2;
        $controlAseguramientos['armas']['CORTAS'] = 1;
        $controlAseguramientos['drogas']['MARIHUANA_GRS'] = 15;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows, $controlVehicular, $controlAseguramientos);

        $this->assertNull($sheet->getCell('B95')->getValue());
        $this->assertSame('CONTROL DE ASEGURAMIENTOS', $sheet->getCell('B96')->getValue());
        $this->assertSame('B96:H96', $sheet->getCell('B96')->getMergeRange());
        $this->assertSame('PERSONAS ASEGURADAS', $sheet->getCell('C97')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('D97')->getValue());
        $this->assertSame('ARMAS', $sheet->getCell('E97')->getValue());
        $this->assertSame('DROGA', $sheet->getCell('G97')->getValue());
        $this->assertSame('POR ALCOHOLEMIA', $sheet->getCell('C100')->getValue());
        $this->assertSame(2, $sheet->getCell('D100')->getValue());
        $this->assertSame('CORTAS', $sheet->getCell('E99')->getValue());
        $this->assertSame(1, $sheet->getCell('F99')->getValue());
        $this->assertSame('MARIHUANA GRS', $sheet->getCell('G99')->getValue());
        $this->assertSame(15, $sheet->getCell('H99')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('B110')->getValue());
        $this->assertSame('B110:C110', $sheet->getCell('B110')->getMergeRange());
        $this->assertSame(2, $sheet->getCell('D110')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('E106')->getValue());
        $this->assertSame(1, $sheet->getCell('F106')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('G106')->getValue());
        $this->assertSame(15, $sheet->getCell('H106')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('B110')->getFill()->getStartColor()->getRGB());
        $this->assertSame('00B0F0', $sheet->getStyle('E106')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_otros_aseguramientos_despues_de_fila_blanca(): void
    {
        $rows = $this->construirFilas(collect());
        $controlVehicular = $this->controlVehicularVacio();
        $controlAseguramientos = $this->controlAseguramientosVacio();
        $controlAseguramientos['otros']['AGUACATE'] = 5;
        $controlAseguramientos['otros']['MADERA'] = 3;
        $controlAseguramientos['otros']['DINERO'] = 100;
        $controlAseguramientos['otros']['OTROS'] = 2;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows, $controlVehicular, $controlAseguramientos);

        $this->assertNull($sheet->getCell('B111')->getValue());
        $this->assertSame('No.', $sheet->getCell('B112')->getValue());
        $this->assertSame('OTROS ASEGURAMIENTOS', $sheet->getCell('C112')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('D112')->getValue());
        $this->assertSame(1, $sheet->getCell('B113')->getValue());
        $this->assertSame('AGUACATE', $sheet->getCell('C113')->getValue());
        $this->assertSame(5, $sheet->getCell('D113')->getValue());
        $this->assertSame('MADERA', $sheet->getCell('C114')->getValue());
        $this->assertSame(3, $sheet->getCell('D114')->getValue());
        $this->assertSame('DINERO', $sheet->getCell('C115')->getValue());
        $this->assertSame(100, $sheet->getCell('D115')->getValue());
        $this->assertSame('OTROS ASEGURAMIENTOS (AGREGARLOS)', $sheet->getCell('C116')->getValue());
        $this->assertSame(2, $sheet->getCell('D116')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('B117')->getValue());
        $this->assertSame('B117:C117', $sheet->getCell('B117')->getMergeRange());
        $this->assertSame(110, $sheet->getCell('D117')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('B117')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_hechos_de_transito_despues_de_fila_blanca(): void
    {
        $rows = $this->construirFilas(collect());
        $controlVehicular = $this->controlVehicularVacio();
        $controlAseguramientos = $this->controlAseguramientosVacio();
        $hechosTransito = $this->hechosTransitoVacio();
        $hechosTransito['resumen']['RESUELTOS'] = 4;
        $hechosTransito['resumen']['PENDIENTES'] = 1;
        $hechosTransito['resumen']['TURNADOS'] = 2;
        $hechosTransito['involucrados']['hombres'] = 3;
        $hechosTransito['involucrados']['mujeres'] = 2;
        $hechosTransito['involucrados']['menores'] = 1;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows, $controlVehicular, $controlAseguramientos, $hechosTransito);

        $this->assertNull($sheet->getCell('B118')->getValue());
        $this->assertSame('No.', $sheet->getCell('B119')->getValue());
        $this->assertSame('HECHOS DE TRÁNSITO', $sheet->getCell('C119')->getValue());
        $this->assertSame('CANTIDAD', $sheet->getCell('D119')->getValue());
        $this->assertSame('RESUELTOS', $sheet->getCell('C120')->getValue());
        $this->assertSame(4, $sheet->getCell('D120')->getValue());
        $this->assertSame('PENDIENTES', $sheet->getCell('C121')->getValue());
        $this->assertSame(1, $sheet->getCell('D121')->getValue());
        $this->assertSame('TURNADOS', $sheet->getCell('C122')->getValue());
        $this->assertSame(2, $sheet->getCell('D122')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('B123')->getValue());
        $this->assertSame('B123:C123', $sheet->getCell('B123')->getMergeRange());
        $this->assertSame(7, $sheet->getCell('D123')->getValue());

        $this->assertSame('No.', $sheet->getCell('F119')->getValue());
        $this->assertSame('HECHOS DE TRÁNSITO', $sheet->getCell('G119')->getValue());
        $this->assertSame('CANTIDAD', $sheet->getCell('H119')->getValue());
        $this->assertSame('HOMBRES INVOLUCRADOS', $sheet->getCell('G120')->getValue());
        $this->assertSame(3, $sheet->getCell('H120')->getValue());
        $this->assertSame('MUJERES INVOLUCRADAS', $sheet->getCell('G121')->getValue());
        $this->assertSame(2, $sheet->getCell('H121')->getValue());
        $this->assertSame('MENORES INVOLUCRADOS', $sheet->getCell('G122')->getValue());
        $this->assertSame(1, $sheet->getCell('H122')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('F123')->getValue());
        $this->assertSame('F123:G123', $sheet->getCell('F123')->getMergeRange());
        $this->assertSame(6, $sheet->getCell('H123')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('B123')->getFill()->getStartColor()->getRGB());
        $this->assertSame('00B0F0', $sheet->getStyle('F123')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_tipos_de_hechos_de_transito_despues_de_fila_blanca(): void
    {
        $rows = $this->construirFilas(collect());
        $controlVehicular = $this->controlVehicularVacio();
        $controlAseguramientos = $this->controlAseguramientosVacio();
        $hechosTransito = $this->hechosTransitoVacio();
        $tipos = $this->tiposHechosTransitoVacio();
        $tipos['VOLCADURA'] = [
            'cantidad' => 2,
            'lesionados' => 3,
            'heridos' => 1,
            'defunciones' => 1,
            'fuero_comun' => 0,
        ];
        $tipos['COLISION_PEATON'] = [
            'cantidad' => 1,
            'lesionados' => 1,
            'heridos' => 0,
            'defunciones' => 0,
            'fuero_comun' => 1,
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows, $controlVehicular, $controlAseguramientos, $hechosTransito, $tipos);

        $this->assertNull($sheet->getCell('B124')->getValue());
        $this->assertSame('No.', $sheet->getCell('B125')->getValue());
        $this->assertSame('HECHOS DE TRÁNSITO', $sheet->getCell('C125')->getValue());
        $this->assertSame('CANTIDAD', $sheet->getCell('D125')->getValue());
        $this->assertSame('LESIONADOS', $sheet->getCell('E125')->getValue());
        $this->assertSame('HERIDOS', $sheet->getCell('F125')->getValue());
        $this->assertSame('DEFUNCIONES', $sheet->getCell('G125')->getValue());
        $this->assertSame('FUERO COMÚN', $sheet->getCell('H125')->getValue());
        $this->assertSame('EXPLOSIÓN', $sheet->getCell('C126')->getValue());
        $this->assertSame('VOLCADURA', $sheet->getCell('C129')->getValue());
        $this->assertSame(2, $sheet->getCell('D129')->getValue());
        $this->assertSame(3, $sheet->getCell('E129')->getValue());
        $this->assertSame(1, $sheet->getCell('F129')->getValue());
        $this->assertSame(1, $sheet->getCell('G129')->getValue());
        $this->assertSame('COLISIÓN CON PEATÓN', $sheet->getCell('C142')->getValue());
        $this->assertSame(1, $sheet->getCell('D142')->getValue());
        $this->assertSame(1, $sheet->getCell('E142')->getValue());
        $this->assertSame(1, $sheet->getCell('H142')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('B143')->getValue());
        $this->assertSame('B143:C143', $sheet->getCell('B143')->getMergeRange());
        $this->assertSame(3, $sheet->getCell('D143')->getValue());
        $this->assertSame(4, $sheet->getCell('E143')->getValue());
        $this->assertSame(1, $sheet->getCell('F143')->getValue());
        $this->assertSame(1, $sheet->getCell('G143')->getValue());
        $this->assertSame(1, $sheet->getCell('H143')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('B143')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_choques_y_danios_despues_de_fila_blanca(): void
    {
        $rows = $this->construirFilas(collect());
        $controlVehicular = $this->controlVehicularVacio();
        $controlAseguramientos = $this->controlAseguramientosVacio();
        $hechosTransito = $this->hechosTransitoVacio();
        $tipos = $this->tiposHechosTransitoVacio();
        $choques = $this->choquesDaniosVacio();
        $choques['tipos']['CAMION_MOTO'] = 2;
        $choques['tipos']['VEHICULO_UNICO'] = 1;
        $choques['danios']['materiales'] = 1500.0;
        $choques['danios']['vehiculos'] = 1000.0;
        $choques['danios']['otros'] = 500.0;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows, $controlVehicular, $controlAseguramientos, $hechosTransito, $tipos, $choques);

        $this->assertNull($sheet->getCell('B144')->getValue());
        $this->assertSame('No.', $sheet->getCell('B145')->getValue());
        $this->assertSame('HECHOS DE TRÁNSITO', $sheet->getCell('C145')->getValue());
        $this->assertSame('CANTIDAD', $sheet->getCell('D145')->getValue());
        $this->assertSame('CHOQUE ENTRE CAMIÓN Y MOTOCICLETA', $sheet->getCell('C146')->getValue());
        $this->assertSame(2, $sheet->getCell('D146')->getValue());
        $this->assertSame('CHOQUE DE VEHÍCULO UNICO', $sheet->getCell('C152')->getValue());
        $this->assertSame(1, $sheet->getCell('D152')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('B153')->getValue());
        $this->assertSame('B153:C153', $sheet->getCell('B153')->getMergeRange());
        $this->assertSame(3, $sheet->getCell('D153')->getValue());

        $this->assertSame('No.', $sheet->getCell('F145')->getValue());
        $this->assertSame('HECHOS DE TRÁNSITO', $sheet->getCell('G145')->getValue());
        $this->assertSame('CANTIDAD', $sheet->getCell('H145')->getValue());
        $this->assertSame('MONTO DAÑOS MATERIALES ($)', $sheet->getCell('G146')->getValue());
        $this->assertSame(1500.0, $sheet->getCell('H146')->getValue());
        $this->assertSame('MONTO VEHÍCULOS', $sheet->getCell('G147')->getValue());
        $this->assertSame(1000.0, $sheet->getCell('H147')->getValue());
        $this->assertSame('MONTO OTROS', $sheet->getCell('G148')->getValue());
        $this->assertSame(500.0, $sheet->getCell('H148')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('F149')->getValue());
        $this->assertSame('F149:G149', $sheet->getCell('F149')->getMergeRange());
        $this->assertSame(1500, $sheet->getCell('H149')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('B153')->getFill()->getStartColor()->getRGB());
        $this->assertSame('00B0F0', $sheet->getStyle('F149')->getFill()->getStartColor()->getRGB());
    }

    public function test_renderiza_clasificacion_vehiculos_liberaciones_y_areas_finales(): void
    {
        $rows = $this->construirFilas(collect());
        $controlVehicular = $this->controlVehicularVacio();
        $controlAseguramientos = $this->controlAseguramientosVacio();
        $hechosTransito = $this->hechosTransitoVacio();
        $tipos = $this->tiposHechosTransitoVacio();
        $choques = $this->choquesDaniosVacio();
        $clasificacion = $this->clasificacionVehiculosVacio();
        $clasificacion['clasificacion']['AUTOMOVIL'] = 2;
        $clasificacion['clasificacion']['MOTOCICLETA'] = 1;
        $clasificacion['resumen']['particulares'] = 2;
        $clasificacion['resumen']['publicos'] = 1;
        $clasificacion['resumen']['motos'] = 1;
        $clasificacion['resumen']['oficiales'] = 1;
        $clasificacion['liberaciones']['motos'] = 1;
        $clasificacion['liberaciones']['vehiculos'] = 2;
        $clasificacion['liberaciones']['camiones'] = 3;
        $clasificacion['liberaciones']['remolques'] = 4;
        $clasificacion['areas_auxiliares']['examen_teorico'] = 4;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $method = new ReflectionMethod(TotalSheetService::class, 'render');
        $method->setAccessible(true);
        $method->invoke(new TotalSheetService(), $sheet, '2026-06-02', $rows, $controlVehicular, $controlAseguramientos, $hechosTransito, $tipos, $choques, $clasificacion);

        $this->assertNull($sheet->getCell('B154')->getValue());
        $this->assertSame('No.', $sheet->getCell('B155')->getValue());
        $this->assertSame('HECHOS DE TRÁNSITO', $sheet->getCell('C155')->getValue());
        $this->assertSame('CANTIDAD', $sheet->getCell('D155')->getValue());
        $this->assertSame('SERVICIO PÚBLICO FED', $sheet->getCell('C156')->getValue());
        $this->assertSame('AUTOMÓVIL', $sheet->getCell('C158')->getValue());
        $this->assertSame(2, $sheet->getCell('D158')->getValue());
        $this->assertSame('MOTOCICLETA', $sheet->getCell('C167')->getValue());
        $this->assertSame(1, $sheet->getCell('D167')->getValue());
        $this->assertSame('SEMOVIENTE', $sheet->getCell('C170')->getValue());

        $this->assertSame('No.', $sheet->getCell('F155')->getValue());
        $this->assertSame('VEHÍCULOS PARTICULARES INVOL.', $sheet->getCell('G156')->getValue());
        $this->assertSame(2, $sheet->getCell('H156')->getValue());
        $this->assertSame('VEHÍCULOS SERV. PÚBLIC. INVOL.', $sheet->getCell('G157')->getValue());
        $this->assertSame(1, $sheet->getCell('H157')->getValue());
        $this->assertSame('MOTOS INVOLUCRADAS', $sheet->getCell('G158')->getValue());
        $this->assertSame(1, $sheet->getCell('H158')->getValue());
        $this->assertSame('VEHÍCULOS OFICIALES INVOL', $sheet->getCell('G159')->getValue());
        $this->assertSame(1, $sheet->getCell('H159')->getValue());

        $this->assertSame('LIBERACIONES', $sheet->getCell('G161')->getValue());
        $this->assertSame('LIBERACIÓN MOTOCICLETAS', $sheet->getCell('G162')->getValue());
        $this->assertSame(1, $sheet->getCell('H162')->getValue());
        $this->assertSame('LIBERACIÓN REMOLQUES', $sheet->getCell('G165')->getValue());
        $this->assertSame(4, $sheet->getCell('H165')->getValue());
        $this->assertSame('TOTAL', $sheet->getCell('F166')->getValue());
        $this->assertSame('F166:G166', $sheet->getCell('F166')->getMergeRange());
        $this->assertSame(10, $sheet->getCell('H166')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('F161')->getFill()->getStartColor()->getRGB());

        $this->assertSame('ÁREAS AUXILIARES', $sheet->getCell('G168')->getValue());
        $this->assertSame('EXÁMEN TEÓRICO', $sheet->getCell('G169')->getValue());
        $this->assertSame(4, $sheet->getCell('H169')->getValue());
        $this->assertSame('00B0F0', $sheet->getStyle('F168')->getFill()->getStartColor()->getRGB());
    }

    public function test_suma_control_de_aseguramientos_desde_detenidos_y_texto(): void
    {
        $service = new TotalSheetService();
        $method = new ReflectionMethod(TotalSheetService::class, 'controlAseguramientos');
        $method->setAccessible(true);

        $counts = $method->invoke($service, collect([
            (object) [
                'categoria' => (object) ['nombre' => 'OPERATIVOS'],
                'subcategoria' => (object) ['nombre' => 'CONTROL'],
                'nombre' => 'CONTROL',
                'lugar' => null,
                'tramo' => null,
                'motivo' => 'PERSONAS PRESENTADAS AL MP POR DROGA',
                'narrativa' => 'Se aseguran 2 personas con 30 gramos de marihuana.',
                'acciones_realizadas' => null,
                'observaciones' => null,
                'personas_detenidas' => 2,
            ],
            (object) [
                'categoria' => (object) ['nombre' => 'OPERATIVOS'],
                'subcategoria' => (object) ['nombre' => 'CONTROL'],
                'nombre' => 'CONTROL',
                'lugar' => null,
                'tramo' => null,
                'motivo' => 'PERSONAS AL MP POR PORTACION DE ARMAS',
                'narrativa' => '1 detenido con 1 arma corta y 3 cartuchos.',
                'acciones_realizadas' => null,
                'observaciones' => null,
                'personas_detenidas' => 1,
            ],
            (object) [
                'categoria' => (object) ['nombre' => 'OPERATIVOS'],
                'subcategoria' => (object) ['nombre' => 'CONTROL'],
                'nombre' => 'CONTROL',
                'lugar' => null,
                'tramo' => null,
                'motivo' => 'ASEGURAMIENTO DE OBJETOS',
                'narrativa' => 'Se aseguran 4 piezas de madera y 500 pesos en efectivo.',
                'acciones_realizadas' => null,
                'observaciones' => null,
                'personas_detenidas' => 0,
            ],
        ]), collect());

        $this->assertSame(2, $counts['personas']['MP_DROGA']);
        $this->assertSame(1, $counts['personas']['MP_PORTACION_ARMAS']);
        $this->assertSame(30.0, $counts['drogas']['MARIHUANA_GRS']);
        $this->assertSame(1.0, $counts['armas']['CORTAS']);
        $this->assertSame(3.0, $counts['armas']['CARTUCHOS']);
        $this->assertSame(4.0, $counts['otros']['MADERA']);
        $this->assertSame(500.0, $counts['otros']['DINERO']);
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

    public function test_mapea_clasificacion_vehicular_del_excel_y_liberaciones(): void
    {
        $service = new TotalSheetService();
        $clasificacionMethod = new ReflectionMethod(TotalSheetService::class, 'claveClasificacionVehiculo');
        $clasificacionMethod->setAccessible(true);
        $liberacionMethod = new ReflectionMethod(TotalSheetService::class, 'claveLiberacionVehiculo');
        $liberacionMethod->setAccessible(true);

        $this->assertSame('AUTOMOVIL', $clasificacionMethod->invoke($service, 'Automóvil sedán'));
        $this->assertSame('TRANSPORTE_PUBLICO', $clasificacionMethod->invoke($service, 'Taxi'));
        $this->assertSame('CAMIONETA_CARGA', $clasificacionMethod->invoke($service, 'Camioneta de carga'));
        $this->assertSame('CAMION_CARGA', $clasificacionMethod->invoke($service, 'Camión de carga'));
        $this->assertSame('motos', $liberacionMethod->invoke($service, 'Bicicleta'));
        $this->assertSame('camiones', $liberacionMethod->invoke($service, 'Torton'));
        $this->assertSame('remolques', $liberacionMethod->invoke($service, 'Remolque'));
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

    private function controlAseguramientosVacio(): array
    {
        $method = new ReflectionMethod(TotalSheetService::class, 'controlAseguramientosVacios');
        $method->setAccessible(true);

        return $method->invoke(new TotalSheetService());
    }

    private function hechosTransitoVacio(): array
    {
        $method = new ReflectionMethod(TotalSheetService::class, 'hechosTransitoVacios');
        $method->setAccessible(true);

        return $method->invoke(new TotalSheetService());
    }

    private function tiposHechosTransitoVacio(): array
    {
        $method = new ReflectionMethod(TotalSheetService::class, 'tiposHechosTransitoVacios');
        $method->setAccessible(true);

        return $method->invoke(new TotalSheetService());
    }

    private function choquesDaniosVacio(): array
    {
        $method = new ReflectionMethod(TotalSheetService::class, 'choquesDaniosVacios');
        $method->setAccessible(true);

        return $method->invoke(new TotalSheetService());
    }

    private function clasificacionVehiculosVacio(): array
    {
        $method = new ReflectionMethod(TotalSheetService::class, 'clasificacionVehiculosVacios');
        $method->setAccessible(true);

        return $method->invoke(new TotalSheetService());
    }
}
