<?php

namespace App\Services\Exports\Sheets;

use App\Services\CorteDiarioService;
use App\Services\EstadoFuerzaService;
use App\Services\OperativosService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\Personal;

class TotalSheet
{
    protected CorteDiarioService $corteDiarioService;
    protected OperativosService $operativosService;
    protected EstadoFuerzaService $estadoFuerzaService;

    public function __construct(
        CorteDiarioService $corteDiarioService,
        OperativosService $operativosService,
        EstadoFuerzaService $estadoFuerzaService
    ) {
        $this->corteDiarioService = $corteDiarioService;
        $this->operativosService = $operativosService;
        $this->estadoFuerzaService = $estadoFuerzaService;
    }

    public function build(Spreadsheet $spreadsheet, Carbon $corte): void
    {
        $corte = $corte->copy()->timezone('America/Mexico_City');
        [$inicio, $fin] = $this->corteDiarioService->rango($corte);

        $fechaReporte = $fin->copy()->subSecond()->toDateString();

        $sheet = new Worksheet($spreadsheet, 'TOTAL');
        $spreadsheet->addSheet($sheet);

        $sheet->getSheetView()->setZoomScale(85);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(44);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(26);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(22);
        $sheet->getColumnDimension('H')->setWidth(22);
        $sheet->getColumnDimension('I')->setWidth(22);

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');

        $sheet->setCellValue('A1', 'SINIESTROS');
        $sheet->setCellValue('A2', 'FECHA   ' . $fechaReporte);

        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getRowDimension(2)->setRowHeight(22);

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $sheet->getStyle('A1:A2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $headerRow = 4;

        $sheet->setCellValue('A' . $headerRow, 'No.');
        $sheet->setCellValue('B' . $headerRow, 'CATEGORÍA');
        $sheet->setCellValue('C' . $headerRow, 'ACTIVIDAD');
        $sheet->setCellValue('D' . $headerRow, 'CANTIDAD');
        $sheet->setCellValue('E' . $headerRow, 'ESTADO DE FUERZA PARTICIPANTE');
        $sheet->setCellValue('F' . $headerRow, 'UNIDADES PARTICIPANTES');
        $sheet->setCellValue('G' . $headerRow, 'KILOMETROS RECORRIDOS');
        $sheet->setCellValue('H' . $headerRow, 'PERSONAS ALCANZADAS');
        $sheet->setCellValue('I' . $headerRow, 'RECOMENDACIONES');

        $sheet->getRowDimension($headerRow)->setRowHeight(30);

        $sheet->getStyle('A' . $headerRow . ':I' . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B5FA5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $hechosCount = $this->contarHechosEnRango($inicio, $fin);
        $conductoresCount = $this->contarConductoresEnRango($inicio, $fin);

        $momento = $fin->copy()->subSecond();

        $monosAB = (int) $this->contarMonosEnServicioTurnoAB($momento);

        $estadoFuerza = min($monosAB, $hechosCount);

        $patrullaIdsAB = $this->patrullaIdsEnServicioTurnoAB($momento);

        $kmTotalDia = (int) DB::table('patrulla_kilometrajes')
            ->where('fecha', $fechaReporte)
            ->whereIn('patrulla_id', $patrullaIdsAB)
            ->sum(DB::raw('COALESCE(kilometros_recorridos, 0)'));

        $patrullasTrabajando = (int) DB::table('patrulla_kilometrajes')
            ->where('fecha', $fechaReporte)
            ->whereIn('patrulla_id', $patrullaIdsAB)
            ->distinct()
            ->count('patrulla_id');

        $unidadesPatrullaje = min($monosAB, $patrullasTrabajando);

        $template = $this->templateCompleto();

        $startRow = $headerRow + 1;
        $rows = [];
        $rowNum = $startRow;

        foreach ($template as $item) {
            $rows[] = [
                'row' => $rowNum++,
                'no' => $item['no'],
                'categoria' => $item['categoria'],
                'actividad' => $item['actividad'],
                'cantidad' => null,
                'ef' => null,
                'unidades' => null,
                'km' => null,
                'personas' => null,
                'recom' => null,
                'band' => $item['band'],
                'key' => $item['key'],
            ];
        }

        foreach ($rows as &$r) {
            if ($r['key'] === 'REPORTES_C5I_SINIESTROS') {
                $r['cantidad'] = $hechosCount;
                $r['ef'] = $estadoFuerza;
                $r['unidades'] = $estadoFuerza;
                $r['personas'] = $conductoresCount;
            }

            if ($r['key'] === 'ABANDERAMIENTOS_ACCIDENTES') {
                $r['cantidad'] = $hechosCount;
                $r['ef'] = $estadoFuerza;
                $r['unidades'] = $estadoFuerza;
            }

            if ($r['key'] === 'DSV_PATRULLAJES') {
                $r['cantidad'] = $monosAB;
                $r['ef'] = $monosAB;
                $r['unidades'] = $monosAB;
            }
        }
        unset($r);

        $marcadas = [];
        foreach ($rows as $idx => $r) {
            $c = (int) ($r['cantidad'] ?? 0);
            $e = (int) ($r['ef'] ?? 0);
            $u = (int) ($r['unidades'] ?? 0);
            if ($c > 0 || $e > 0 || $u > 0) {
                $marcadas[] = $idx;
            }
        }

        if ($kmTotalDia > 0 && count($marcadas) > 0) {
            $kmPorFila = intdiv($kmTotalDia, count($marcadas));
            $residuo = $kmTotalDia - ($kmPorFila * count($marcadas));

            foreach ($marcadas as $pos => $idx) {
                $rows[$idx]['km'] = $kmPorFila + ($pos < $residuo ? 1 : 0);
            }
        }

        foreach ($rows as $r) {
            $row = (int) $r['row'];

            $sheet->setCellValue('A' . $row, $r['no']);
            $sheet->setCellValue('B' . $row, $r['categoria']);
            $sheet->setCellValue('C' . $row, $r['actividad']);
            $sheet->setCellValue('D' . $row, $r['cantidad']);
            $sheet->setCellValue('E' . $row, $r['ef']);
            $sheet->setCellValue('F' . $row, $r['unidades']);
            $sheet->setCellValue('G' . $row, $r['km']);
            $sheet->setCellValue('H' . $row, $r['personas']);
            $sheet->setCellValue('I' . $row, $r['recom']);

            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $sheet->getStyle('A' . $row . ':A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if (!empty($r['band'])) {
                $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D9EEF9');
            }
        }

        $this->mergeBloquesNoCategoria($sheet, $rows);

        $totalRow = $rowNum + 1;

        $sheet->mergeCells('A' . $totalRow . ':B' . $totalRow);
        $sheet->setCellValue('A' . $totalRow, 'TOTAL');

        $sheet->setCellValue('C' . $totalRow, 'DISPOSITIVOS REALIZADOS');

        $dispositivosRealizados = 0;
        foreach ($rows as $r) {
            $dispositivosRealizados += (int) ($r['cantidad'] ?? 0);
        }

        $sheet->setCellValue('D' . $totalRow, $dispositivosRealizados);
        $sheet->setCellValue('E' . $totalRow, 0);
        $sheet->setCellValue('F' . $totalRow, 0);
        $sheet->setCellValue('G' . $totalRow, $kmTotalDia);
        $sheet->setCellValue('H' . $totalRow, 0);
        $sheet->setCellValue('I' . $totalRow, 0);

        $sheet->getRowDimension($totalRow)->setRowHeight(22);

        $sheet->getStyle('A' . $totalRow . ':I' . $totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'B7E1F3'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->freezePane('A' . ($headerRow + 1));




        $this->buildControlVehicularTable($sheet, $inicio, $fin, 82);
        $this->buildControlVehicularTable($sheet, $inicio, $fin, 82);
        $this->buildControlAseguramientosTable($sheet, $inicio, $fin, 100);
        $this->buildOtrosAseguramientosTable($sheet, 116);
        $this->buildHechosYPersonasInvolucradasTables($sheet, $inicio, $fin, 126);
        $this->buildHechosDeTransitoTable($sheet, $inicio, $fin, 126);
        $this->buildBloqueFinalTresTablas($sheet, $inicio, $fin, 126);
    }

    protected function mergeBloquesNoCategoria(Worksheet $sheet, array $rows): void
    {
        $starts = [];
        foreach ($rows as $i => $r) {
            if ((string) ($r['no'] ?? '') !== '') {
                $starts[] = $i;
            }
        }

        $starts[] = count($rows);

        for ($k = 0; $k < count($starts) - 1; $k++) {
            $iStart = $starts[$k];
            $iEndExclusive = $starts[$k + 1];

            $rowStart = (int) $rows[$iStart]['row'];
            $rowEnd = (int) $rows[$iEndExclusive - 1]['row'];

            if ($rowEnd > $rowStart) {
                $sheet->mergeCells('A' . $rowStart . ':A' . $rowEnd);
                $sheet->mergeCells('B' . $rowStart . ':B' . $rowEnd);

                $sheet->getStyle('A' . $rowStart . ':B' . $rowEnd)->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('B' . $rowStart . ':B' . $rowEnd)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        }
    }

    protected function contarHechosEnRango(Carbon $inicio, Carbon $fin): int
    {
        $inicioStr = $inicio->format('Y-m-d H:i:s');
        $finStr = $fin->format('Y-m-d H:i:s');

        return (int) DB::table('hechos')
            ->whereRaw("STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') >= ?", [$inicioStr])
            ->whereRaw("STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') < ?", [$finStr])
            ->count();
    }

    protected function contarConductoresEnRango(Carbon $inicio, Carbon $fin): int
    {
        $inicioStr = $inicio->format('Y-m-d H:i:s');
        $finStr = $fin->format('Y-m-d H:i:s');

        return (int) DB::table('hechos as h')
            ->join('hecho_vehiculo as hv', 'hv.hecho_id', '=', 'h.id')
            ->join('vehiculo_conductor as vc', 'vc.vehiculo_id', '=', 'hv.vehiculo_id')
            ->whereRaw("STR_TO_DATE(CONCAT(h.fecha, ' ', h.hora), '%Y-%m-%d %H:%i:%s') >= ?", [$inicioStr])
            ->whereRaw("STR_TO_DATE(CONCAT(h.fecha, ' ', h.hora), '%Y-%m-%d %H:%i:%s') < ?", [$finStr])
            ->distinct()
            ->count('vc.conductor_id');
    }

    protected function tablaExiste(string $tabla): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($tabla);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function contarMonosEnServicioTurnoAB(Carbon $momento): int
    {
        $personales = Personal::with(['turno', 'incidencias'])
            ->where('estatus', 'ACTIVO')
            ->get();

        $total = 0;

        foreach ($personales as $p) {
            $turno = strtoupper((string)($p->turno->nombre ?? $p->turno->name ?? $p->turno->clave ?? ''));
            if (!in_array($turno, ['A', 'B'], true)) {
                continue;
            }

            $estado = $this->estadoFuerzaService->estado($p, $momento);

            if ($estado === 'EN_SERVICIO') {
                $total++;
            }
        }

        return $total;
    }

    protected function patrullaIdsEnServicioTurnoAB(Carbon $momento): array
    {
        $personales = Personal::with(['turno', 'incidencias'])
            ->where('estatus', 'ACTIVO')
            ->whereNotNull('patrulla_id')
            ->get();

        $ids = [];

        foreach ($personales as $p) {
            $turno = strtoupper((string)($p->turno->nombre ?? $p->turno->name ?? $p->turno->clave ?? ''));
            if (!in_array($turno, ['A', 'B'], true)) {
                continue;
            }

            $estado = $this->estadoFuerzaService->estado($p, $momento);

            if ($estado === 'EN_SERVICIO') {
                $ids[(int)$p->patrulla_id] = true;
            }
        }

        return array_keys($ids);
    }

    protected function templateCompleto(): array
    {
        return [
            ['no' => 1, 'categoria' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS PÚBLICOS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'APOYO A EVENTOS DEPORTIVOS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'APOYO A EVENTOS CULTURALES', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'APOYO A EVENTOS RELIGIOSOS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'ESCUELAS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'DILIGENCIAS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS TIPOS (Especificar en las novedades relevantes)', 'band' => false, 'key' => null],

            ['no' => 2, 'categoria' => 'REPORTES C5i', 'actividad' => 'OBSTRUCCIÓN DE COCHERAS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS TIPOS DE OBSTRUCCIÓN', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'ACTOS DELICTIVOS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'SINIESTROS', 'band' => true, 'key' => 'REPORTES_C5I_SINIESTROS'],
            ['no' => '', 'categoria' => '', 'actividad' => 'HECHOS DE TRÁNSITO', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'CONSENTRACION PERSONAS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS REPORTES (Especificar en las novedades relevantes)', 'band' => true, 'key' => null],

            ['no' => 3, 'categoria' => 'ABANDERAMIENTOS', 'actividad' => 'CORTES DE CIRCULACIÓN', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'ACCIDENTES', 'band' => false, 'key' => 'ABANDERAMIENTOS_ACCIDENTES'],
            ['no' => '', 'categoria' => '', 'actividad' => 'MARCHAS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'MÍTINES', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OBRAS PÚBLICAS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'ACOMPAÑAMIENTO A CARAVANAS U OTROS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)', 'band' => false, 'key' => null],

            ['no' => 4, 'categoria' => 'OPERATIVOS', 'actividad' => 'RELÁMPAGO', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'CARRUSEL', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'BLINDAJE', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'CONCIENTIZACIÓN USO DE CASCO', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'PUESTO DE REVISIÓN', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'PUESTO DE CONTROL', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'APOYO COCOTRA', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'BLINDAJE CON ESTADOS COLINDANTES', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'BASES DE OPERACIONES INTERINSTITUCIONAL', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS OPERATIVOS (Especificar en las novedades relevantes)', 'band' => true, 'key' => null],

            ['no' => 5, 'categoria' => 'PROGRAMAS', 'actividad' => 'CONDUCE SIN ALCOHOL (ALCOHOLÍMETRO)', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS PROGRAMAS (Especificar en las novedades relevantes)', 'band' => false, 'key' => null],

            ['no' => 6, 'categoria' => 'MONITOREOS', 'actividad' => 'VÍAS FÉRREAS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'PERIFÉRICOS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'AVENIDAS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'TIENDAS DEPARTAMENTALES', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'BANCOS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'GASOLINERAS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OFICINAS GUBERNAMENTALES', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'MANIFESTACIONES', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS MONITOREOS (Especificar en las novedades relevantes)', 'band' => true, 'key' => null],

            ['no' => 7, 'categoria' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'FALLAS MECÁNICAS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'PEATÓN', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'ESCOLTA EN SITUACIONES DE EMERGENCIA', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'AGRICOLAS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS AUXILIOS (Especificar en las novedades relevantes)', 'band' => false, 'key' => null],

            ['no' => 8, 'categoria' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'APOYO A LA VIALIDAD', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'PASO LIBRE DE FUNCIONARIOS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'ZONAS DE MAYOR PASE DE TRANSEÚNTES', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'PASOS PEATONALES', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'MEDIDAS DE PROTECCIÓN', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'PATRULLAJES', 'band' => true, 'key' => 'DSV_PATRULLAJES'],
            ['no' => '', 'categoria' => '', 'actividad' => 'SERVICIOS DE ESCOLTAS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTROS (Especificar en las novedades relevantes)', 'band' => true, 'key' => null],

            ['no' => 9, 'categoria' => 'CAPACITACIONES', 'actividad' => 'TALLER EDUCACIÓN SEGURIDAD VIAL', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'MÓDULOS EDUCACIÓN SEGURIDAD VIAL', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'SSP', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'CALEA', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => false, 'key' => null],

            ['no' => 10, 'categoria' => 'CAMPAÑAS', 'actividad' => 'CONCIENTIZACIÓN Y PREVENCIÓN', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'REPARTICIÓN DE TRÍPTICOS', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'ESTACIONALES (SEMANA SANTA, NAVIDAD ETC.)', 'band' => true, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => true, 'key' => null],

            ['no' => 11, 'categoria' => 'PROXIMIDAD SOCIAL', 'actividad' => 'PREVENCIÓN SOCIAL', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'RECORRIDOS DE PROXIMIDAD', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'APOYO A TURISTAS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'APOYO A PERSONAS DE LA TERCERA EDAD', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'APOYO A PERSONAS PERDIDAS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'RECUPERACIÓN DE ESPACIOS', 'band' => false, 'key' => null],
            ['no' => '', 'categoria' => '', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => false, 'key' => null],
        ];
    }

                                //********************************************
                                //                  TABLA 2                 //
                                //********************************************


    protected function buildControlVehicularTable(Worksheet $sheet, Carbon $inicio, Carbon $fin, int $startRow = 82): void
    {
        $colNo   = 'B';
        $colDesc = 'C';
        $colVeh  = 'D';
        $colMot  = 'E';
        $colCam  = 'F';
        $colOtr  = 'G';

        $headerRow = $startRow;

        $sheet->setCellValue($colNo . $headerRow, 'No.');
        $sheet->setCellValue($colDesc . $headerRow, 'CONTROL VEHÍCULAR');
        $sheet->setCellValue($colVeh . $headerRow, 'VEHÍCULOS');
        $sheet->setCellValue($colMot . $headerRow, 'MOTOCICLETAS');
        $sheet->setCellValue($colCam . $headerRow, 'CAMIONES');
        $sheet->setCellValue($colOtr . $headerRow, 'OTROS');

        $sheet->getRowDimension($headerRow)->setRowHeight(22);

        $sheet->getStyle($colNo . $headerRow . ':' . $colOtr . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B5FA5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $template = $this->templateControlVehicular();
        $counts = $this->controlesVehicularesContadores($inicio, $fin);

        $row = $headerRow + 1;

        $totVeh = 0;
        $totMot = 0;
        $totCam = 0;
        $totOtr = 0;

        foreach ($template as $item) {
            $key = (string)($item['key'] ?? '');

            $v = (int)($counts[$key]['vehiculos'] ?? 0);
            $m = (int)($counts[$key]['motocicletas'] ?? 0);
            $c = (int)($counts[$key]['camiones'] ?? 0);
            $o = (int)($counts[$key]['otros'] ?? 0);

            $sheet->setCellValue($colNo . $row, $item['no']);
            $sheet->setCellValue($colDesc . $row, $item['label']);
            $sheet->setCellValue($colVeh . $row, $v);
            $sheet->setCellValue($colMot . $row, $m);
            $sheet->setCellValue($colCam . $row, $c);
            $sheet->setCellValue($colOtr . $row, $o);

            $sheet->getRowDimension($row)->setRowHeight(18);

            $sheet->getStyle($colNo . $row . ':' . $colOtr . $row)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $sheet->getStyle($colNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colVeh . $row . ':' . $colOtr . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $totVeh += $v;
            $totMot += $m;
            $totCam += $c;
            $totOtr += $o;

            $row++;
        }

        $totalRow = $row;

        $sheet->mergeCells($colNo . $totalRow . ':' . $colDesc . $totalRow);
        $sheet->setCellValue($colNo . $totalRow, 'TOTAL');
        $sheet->setCellValue($colVeh . $totalRow, $totVeh);
        $sheet->setCellValue($colMot . $totalRow, $totMot);
        $sheet->setCellValue($colCam . $totalRow, $totCam);
        $sheet->setCellValue($colOtr . $totalRow, $totOtr);

        $sheet->getRowDimension($totalRow)->setRowHeight(20);

        $sheet->getStyle($colNo . $totalRow . ':' . $colOtr . $totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B5FA5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle($colNo . $totalRow . ':' . $colOtr . $totalRow)->getFont()->getColor()->setRGB('FFFFFF');
    }

    protected function templateControlVehicular(): array
    {
        return [
            ['no' => 1,  'label' => 'REVISIÓN DE ANTECEDENTES', 'key' => 'REVISION_ANTECEDENTES'],
            ['no' => 2,  'label' => 'VEHÍCULOS REVISADOS DE PROCEDENCIA EXTRANJERA', 'key' => 'PROC_EXTRANJERA'],
            ['no' => 3,  'label' => 'DESPOLARIZADO', 'key' => 'DESPOLARIZADO'],
            ['no' => 4,  'label' => 'CORRALON POR FALTAS ADMINISTRATIVAS', 'key' => 'CORRALON_ADMIN'],
            ['no' => 5,  'label' => 'CORRALÓN POR HECHOS DE TRANSITO', 'key' => 'CORRALON_TRANSITO'],
            ['no' => 6,  'label' => 'PUESTOS A DISPOSICIÓN DEL MP POR HECHO DE TRÁNSITO', 'key' => 'MP_TRANSITO'],
            ['no' => 7,  'label' => 'PRESENTADOS AL MP', 'key' => 'PRESENTADOS_MP'],
            ['no' => 8,  'label' => 'RESGUARDADOS POR ABANDONO', 'key' => 'ABANDONO'],
            ['no' => 9,  'label' => 'ASEGURADOS POR HECHOS DELICTIVOS', 'key' => 'DELICTIVOS'],
            ['no' => 10, 'label' => 'RECUPERADOS CON ALTERACIONES EN SUS MEDIOS DE IDENTIFICACIÓN', 'key' => 'ALTERACIONES_ID'],
            ['no' => 11, 'label' => 'RECUPERADOS CON REPORTE DE ROBO', 'key' => 'REC_ROBO'],
            ['no' => 12, 'label' => 'CONOCIMIENTO DE REPORTE DE ROBO', 'key' => 'CONOCIMIENTO_ROBO'],
            ['no' => 13, 'label' => 'ASEGURADOS POR OTROS MOTIVOS', 'key' => 'OTROS_MOTIVOS'],
        ];
    }

    protected function controlesVehicularesContadores(Carbon $inicio, Carbon $fin): array
    {
        $inicioStr = $inicio->format('Y-m-d H:i:s');
        $finStr    = $fin->format('Y-m-d H:i:s');

        $subHechos = DB::table('hechos')
            ->select('hechos.id')
            ->whereRaw("STR_TO_DATE(CONCAT(hechos.fecha, ' ', hechos.hora), '%Y-%m-%d %H:%i:%s') >= ?", [$inicioStr])
            ->whereRaw("STR_TO_DATE(CONCAT(hechos.fecha, ' ', hechos.hora), '%Y-%m-%d %H:%i:%s') < ?", [$finStr]);

        $baseVeh = DB::table('hecho_vehiculo')
            ->join('vehiculos', 'vehiculos.id', '=', 'hecho_vehiculo.vehiculo_id')
            ->join('hechos', 'hechos.id', '=', 'hecho_vehiculo.hecho_id')
            ->whereIn('hecho_vehiculo.hecho_id', $subHechos)
            ->select([
                'vehiculos.id as vehiculo_id',
                'vehiculos.tipo as vehiculo_tipo',
                'vehiculos.corralon as vehiculo_corralon',
                'vehiculos.antecedente_vehiculo as antecedente_vehiculo',
                'hechos.oficio_mp as oficio_mp',
                'hechos.vehiculos_mp as vehiculos_mp',
            ]);

        $keys = array_column($this->templateControlVehicular(), 'key');

        $out = [];
        foreach ($keys as $k) {
            $out[$k] = ['vehiculos' => 0, 'motocicletas' => 0, 'camiones' => 0, 'otros' => 0];
        }

        $push = function (string $key, string $tipoCarroceria) use (&$out) {
            $bucket = $this->bucketControlVehicular($tipoCarroceria);
            $out[$key][$bucket] = (int)($out[$key][$bucket] ?? 0) + 1;
        };

        (clone $baseVeh)
            ->whereRaw("COALESCE(vehiculos.antecedente_vehiculo, 0) = 1")
            ->distinct()
            ->get(['vehiculos.id as vehiculo_id', 'vehiculos.tipo as vehiculo_tipo'])
            ->each(function ($r) use ($push) {
                $push('REVISION_ANTECEDENTES', (string)($r->vehiculo_tipo ?? ''));
            });

        (clone $baseVeh)
            ->distinct()
            ->get(['vehiculos.id as vehiculo_id', 'vehiculos.tipo as vehiculo_tipo', 'vehiculos.corralon as vehiculo_corralon'])
            ->each(function ($r) use ($push) {
                $corralon = (string)($r->vehiculo_corralon ?? '');
                if ($this->isCorralonResguardado($corralon)) {
                    $push('CORRALON_TRANSITO', (string)($r->vehiculo_tipo ?? ''));
                }
            });

        (clone $baseVeh)
            ->where(function ($q) {
                $q->whereRaw("COALESCE(hechos.vehiculos_mp, 0) > 0")
                  ->orWhereRaw("TRIM(COALESCE(hechos.oficio_mp, '')) <> ''");
            })
            ->distinct()
            ->get(['vehiculos.id as vehiculo_id', 'vehiculos.tipo as vehiculo_tipo'])
            ->each(function ($r) use ($push) {
                $push('MP_TRANSITO', (string)($r->vehiculo_tipo ?? ''));
            });

        return $out;
    }

    protected function bucketControlVehicular(string $tipoCarroceria): string
    {
        $general = $this->tipoGeneralFromTipo($tipoCarroceria);

        if (in_array($general, ['automovil', 'camioneta'], true)) return 'vehiculos';
        if ($general === 'motocicleta') return 'motocicletas';
        if (in_array($general, ['camion', 'remolque'], true)) return 'camiones';

        return 'otros';
    }

    protected function isCorralonResguardado(string $corralon): bool
    {
        $c = trim($corralon);
        if ($c === '') return false;

        $cN = $this->norm($c);

        $bloqueados = [
            'N/A',
            'NA',
            'NO',
            'NO SE UTILIZA',
            'NOSEUTILIZA',
            'NA.',
            'N.A',
            'N.A.',
        ];

        return !in_array($cN, $bloqueados, true);
    }

    protected function norm(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';

        $s = mb_strtoupper($s, 'UTF-8');

        $map = [
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'Ñ' => 'N',
        ];

        $s = strtr($s, $map);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;

        return $s;
    }

    protected function tipoGeneralFromTipo(string $tipoCarroceria): string
    {
        $t = $this->norm($tipoCarroceria);
        if ($t === '') return 'otros';

        if (
            str_contains($t, 'MOTO') ||
            str_contains($t, 'SCOOTER') ||
            str_contains($t, 'MOTONETA') ||
            str_contains($t, 'ENDURO') ||
            str_contains($t, 'NAKED') ||
            str_contains($t, 'PISTA') ||
            str_contains($t, 'DOBLE PROPOSITO') ||
            str_contains($t, 'DOBLE PROPOSITO') ||
            str_contains($t, 'DOBLE') ||
            str_contains($t, 'CRUISER') ||
            str_contains($t, 'CRUISIER') ||
            str_contains($t, 'CHOPPER') ||
            str_contains($t, 'CUATRIMOTO')
        ) {
            return 'motocicleta';
        }

        if (
            str_contains($t, 'CAMION') ||
            str_contains($t, 'TRACTO') ||
            str_contains($t, 'TRAILER') ||
            str_contains($t, 'VOLTEO') ||
            str_contains($t, 'PIPA') ||
            str_contains($t, 'TORTON') ||
            str_contains($t, 'RABON')
        ) {
            return 'camion';
        }

        if (
            str_contains($t, 'REMOLQUE') ||
            str_contains($t, 'SEMIRREM') ||
            str_contains($t, 'SEMIRREMOLQUE') ||
            str_contains($t, 'PLATAFORMA') ||
            str_contains($t, 'DOLLY')
        ) {
            return 'remolque';
        }

        if (
            str_contains($t, 'PICK') ||
            str_contains($t, 'CAMIONETA') ||
            str_contains($t, 'SUV') ||
            str_contains($t, 'VAN') ||
            str_contains($t, 'MINIVAN') ||
            str_contains($t, 'PANEL') ||
            str_contains($t, 'URVAN') ||
            str_contains($t, 'FURGON') ||
            str_contains($t, 'VAGONETA')
        ) {
            return 'camioneta';
        }

        if (
            str_contains($t, 'AUTO') ||
            str_contains($t, 'SEDAN') ||
            str_contains($t, 'HATCH') ||
            str_contains($t, 'COUPE') ||
            str_contains($t, 'CONVERTIBLE') ||
            str_contains($t, 'VOCHO') ||
            str_contains($t, 'TSURU')
        ) {
            return 'automovil';
        }

        if (
            str_contains($t, 'BICICLETA') ||
            str_contains($t, 'BMX') ||
            str_contains($t, 'RUTA') ||
            str_contains($t, 'MONTANA')
        ) {
            return 'bicicleta';
        }

        if (
            str_contains($t, 'SEMOVIENTE') ||
            str_contains($t, 'CABALLO') ||
            str_contains($t, 'BURRO') ||
            str_contains($t, 'VACA')
        ) {
            return 'semoviente';
        }

        return 'otros';
    }

                                //********************************************
                                //                  TABLA 3                 //
                                //********************************************



    protected function buildControlAseguramientosTable(Worksheet $sheet, Carbon $inicio, Carbon $fin, int $startRow = 100): void
    {
        $colNo = 'B';
        $colPers = 'C';
        $colPersTot = 'D';
        $colArmas = 'E';
        $colArmasTot = 'F';
        $colDroga = 'G';
        $colDrogaTot = 'H';

        $titleRow = $startRow - 1;
        $headerRow = $startRow;

        $sheet->mergeCells($colNo . $titleRow . ':' . $colDrogaTot . $titleRow);
        $sheet->setCellValue($colNo . $titleRow, 'CONTROL DE ASEGURAMIENTOS');

        $sheet->getRowDimension($titleRow)->setRowHeight(22);

        $sheet->getStyle($colNo . $titleRow . ':' . $colDrogaTot . $titleRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B5FA5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->setCellValue($colNo . $headerRow, 'No.');
        $sheet->setCellValue($colPers . $headerRow, 'PERSONAS ASEGURADAS');
        $sheet->setCellValue($colPersTot . $headerRow, 'TOTAL');
        $sheet->setCellValue($colArmas . $headerRow, 'ARMAS');
        $sheet->setCellValue($colArmasTot . $headerRow, 'TOTAL');
        $sheet->setCellValue($colDroga . $headerRow, 'DROGA');
        $sheet->setCellValue($colDrogaTot . $headerRow, 'TOTAL');

        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        $sheet->getStyle($colNo . $headerRow . ':' . $colDrogaTot . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B5FA5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $pers = $this->templatePersonasAseguradas();
        $armas = $this->templateArmas();
        $drogas = $this->templateDrogas();

        $conductoresCount = $this->contarConductoresEnRango($inicio, $fin);

        $row = $headerRow + 1;

        for ($i = 0; $i < 12; $i++) {
            $no = $i + 1;

            $sheet->setCellValue($colNo . $row, $no);
            $sheet->setCellValue($colPers . $row, $pers[$i] ?? '');
            $sheet->setCellValue($colArmas . $row, $armas[$i] ?? '');
            $sheet->setCellValue($colDroga . $row, $drogas[$i] ?? '');

            if ($no === 1) {
                $sheet->setCellValue($colPersTot . $row, $conductoresCount);
            }

            if ($no === 9) {
                $sheet->setCellValue($colArmasTot . $row, 0);
                $sheet->setCellValue($colDrogaTot . $row, 0);

                $sheet->getStyle($colArmas . $row . ':' . $colDrogaTot . $row)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9EEF9'],
                    ],
                ]);
            }

            $sheet->getRowDimension($row)->setRowHeight(18);

            $sheet->getStyle($colNo . $row . ':' . $colDrogaTot . $row)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $sheet->getStyle($colNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colPersTot . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colArmasTot . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colDrogaTot . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $totalRow = $row;

        $sheet->mergeCells($colNo . $totalRow . ':' . $colPers . $totalRow);
        $sheet->setCellValue($colNo . $totalRow, 'TOTAL');
        $sheet->setCellValue($colPersTot . $totalRow, $conductoresCount);

        $sheet->getRowDimension($totalRow)->setRowHeight(20);

        $sheet->getStyle($colNo . $totalRow . ':' . $colPersTot . $totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B5FA5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle($colNo . $totalRow . ':' . $colPersTot . $totalRow)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($colNo . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle($colArmas . ($headerRow + 1) . ':' . $colArmasTot . $totalRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle($colDroga . ($headerRow + 1) . ':' . $colDrogaTot . $totalRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    protected function templatePersonasAseguradas(): array
    {
        return [
            'CONSULTA DE ANTECEDENTES PENALES',
            'PERSONAS A BARANDILLA',
            'POR ALCOHOLEMIA',
            'PERSONAS PRESENTADAS AL MP',
            'POR ROBOS DIVERSOS',
            'POR LESIONES',
            'POR HOMICIDIO CULPOSO',
            'POR HOMICIDIO DOLOSO',
            'PERSONAS AL MP POR VEHÍCULOS, MOTOS O CAMIONES ROBADOS',
            'PERSONAS AL MP POR PORTACION DE ARMAS',
            'PERSONAS AL MP POR DROGA',
            'OTROS DELITOS',
        ];
    }

    protected function templateArmas(): array
    {
        return [
            'ARMAS',
            'CORTAS',
            'LARGAS',
            'CARGADORES',
            'CARTUCHOS',
            'GRANADAS',
            'LANZA GRANADAS',
            'PUNZOCORTANTE',
            'TOTAL',
            '',
            '',
            '',
        ];
    }

    protected function templateDrogas(): array
    {
        return [
            'DROGA',
            'MARIHUANA GRS',
            'CRISTAL GRS',
            'COCAINA GRS',
            'PASTILLAS',
            'PLANTIOS',
            'PLANTAS DE MARIHUANA',
            'OTRAS DROGAS',
            'TOTAL',
            '',
            '',
            '',
        ];
    }


                                //********************************************
                                //                  TABLA 4                 //
                                //********************************************

    protected function buildOtrosAseguramientosTable(Worksheet $sheet, int $startRow = 116): void
    {
        $colNo = 'B';
        $colDesc = 'C';
        $colTot = 'D';

        $headerRow = $startRow;

        $sheet->setCellValue($colNo . $headerRow, 'No.');
        $sheet->setCellValue($colDesc . $headerRow, 'OTROS ASEGURAMIENTOS');
        $sheet->setCellValue($colTot . $headerRow, 'TOTAL');

        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        $sheet->getStyle($colNo . $headerRow . ':' . $colTot . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B5FA5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $items = $this->templateOtrosAseguramientos();

        $row = $headerRow + 1;

        foreach ($items as $it) {
            $sheet->setCellValue($colNo . $row, $it['no']);
            $sheet->setCellValue($colDesc . $row, $it['label']);
            $sheet->setCellValue($colTot . $row, null);

            $sheet->getRowDimension($row)->setRowHeight(18);

            $sheet->getStyle($colNo . $row . ':' . $colTot . $row)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            $sheet->getStyle($colNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($colTot . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $totalRow = $row;

        $sheet->mergeCells($colNo . $totalRow . ':' . $colDesc . $totalRow);
        $sheet->setCellValue($colNo . $totalRow, 'TOTAL');
        $sheet->setCellValue($colTot . $totalRow, 0);

        $sheet->getRowDimension($totalRow)->setRowHeight(20);

        $sheet->getStyle($colNo . $totalRow . ':' . $colTot . $totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'B7E1F3'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }

    protected function templateOtrosAseguramientos(): array
    {
        return [
            ['no' => 1, 'label' => 'AGUACATE'],
            ['no' => 2, 'label' => 'MADERA'],
            ['no' => 3, 'label' => 'DINERO'],
            ['no' => 4, 'label' => 'OTROS ASEGURAMIENTOS (AGREGARLOS)'],
        ];
    }

                                //********************************************
                                //                  TABLA 5                 //
                                //********************************************


    protected function buildHechosYPersonasInvolucradasTables(
        Worksheet $sheet,
        Carbon $inicio,
        Carbon $fin,
        int $startRow = 126
    ): void {
        $r = $startRow;

        $hNo = 'B'; $hDesc = 'C'; $hCant = 'D';
        $pNo = 'F'; $pDesc = 'G'; $pCant = 'H';

        $sheet->setCellValue($hNo . $r, 'No.');
        $sheet->setCellValue($hDesc . $r, 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue($hCant . $r, 'CANTIDAD');

        $sheet->setCellValue($pNo . $r, 'No.');
        $sheet->setCellValue($pDesc . $r, 'HECHOS DE TRÁNSITO');
        $sheet->setCellValue($pCant . $r, 'CANTIDAD');

        $sheet->getRowDimension($r)->setRowHeight(20);

        $sheet->getStyle($hNo . $r . ':' . $hCant . $r)->applyFromArray($this->styleHeaderBlue());
        $sheet->getStyle($pNo . $r . ':' . $pCant . $r)->applyFromArray($this->styleHeaderBlue());

        $hechos = $this->contarHechosResPendTurn($inicio, $fin);
        $personas = $this->contarConductoresPorSexoYMenores($inicio, $fin);

        $rows = [
            ['no' => 1, 'label' => 'RESUELTOS',  'val' => (int)($hechos['resueltos'] ?? 0)],
            ['no' => 2, 'label' => 'PENDIENTES', 'val' => (int)($hechos['pendientes'] ?? 0)],
            ['no' => 3, 'label' => 'TURNADOS',   'val' => (int)($hechos['turnados'] ?? 0)],
        ];

        $rows2 = [
            ['no' => 1, 'label' => 'HOMBRES', 'val' => (int)($personas['hombres'] ?? 0)],
            ['no' => 2, 'label' => 'MUJERES', 'val' => (int)($personas['mujeres'] ?? 0)],
            ['no' => 3, 'label' => 'MENORES', 'val' => (int)($personas['menores'] ?? 0)],
        ];

        $row = $r + 1;

        foreach ($rows as $i => $it) {
            $sheet->setCellValue($hNo . $row, $it['no']);
            $sheet->setCellValue($hDesc . $row, $it['label']);
            $sheet->setCellValue($hCant . $row, $it['val']);

            $sheet->setCellValue($pNo . $row, $rows2[$i]['no']);
            $sheet->setCellValue($pDesc . $row, $rows2[$i]['label']);
            $sheet->setCellValue($pCant . $row, $rows2[$i]['val']);

            $sheet->getRowDimension($row)->setRowHeight(18);

            $sheet->getStyle($hNo . $row . ':' . $hCant . $row)->applyFromArray($this->styleBodyThin());
            $sheet->getStyle($pNo . $row . ':' . $pCant . $row)->applyFromArray($this->styleBodyThin());

            $sheet->getStyle($hNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($hCant . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle($pNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($pCant . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $totalRow = $row;

        $totalHechos = (int)($hechos['total'] ?? 0);
        $totalConductores = (int)($personas['total'] ?? 0);

        $sheet->mergeCells($hNo . $totalRow . ':' . $hDesc . $totalRow);
        $sheet->setCellValue($hNo . $totalRow, 'TOTAL');
        $sheet->setCellValue($hCant . $totalRow, $totalHechos);

        $sheet->mergeCells($pNo . $totalRow . ':' . $pDesc . $totalRow);
        $sheet->setCellValue($pNo . $totalRow, 'TOTAL');
        $sheet->setCellValue($pCant . $totalRow, $totalConductores);

        $sheet->getRowDimension($totalRow)->setRowHeight(20);

        $sheet->getStyle($hNo . $totalRow . ':' . $hCant . $totalRow)->applyFromArray($this->styleTotalBlue());
        $sheet->getStyle($pNo . $totalRow . ':' . $pCant . $totalRow)->applyFromArray($this->styleTotalBlue());
    }

    protected function contarHechosResPendTurn(Carbon $inicio, Carbon $fin): array
    {
        $inicioStr = $inicio->format('Y-m-d H:i:s');
        $finStr    = $fin->format('Y-m-d H:i:s');

        $base = DB::table('hechos')
            ->whereRaw("STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') >= ?", [$inicioStr])
            ->whereRaw("STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') < ?", [$finStr]);

        $pendientes = (int) (clone $base)
            ->whereRaw("UPPER(TRIM(COALESCE(situacion,''))) = 'PENDIENTE'")
            ->count();

        $turnados = (int) (clone $base)
            ->whereRaw("UPPER(TRIM(COALESCE(situacion,''))) = 'TURNADO'")
            ->count();

        $total = (int) (clone $base)->count();

        $resueltos = max(0, $total - $pendientes - $turnados);

        return [
            'resueltos' => $resueltos,
            'pendientes' => $pendientes,
            'turnados' => $turnados,
            'total' => $total,
        ];
    }

    protected function contarConductoresPorSexoYMenores(Carbon $inicio, Carbon $fin): array
    {
        $inicioStr = $inicio->format('Y-m-d H:i:s');
        $finStr    = $fin->format('Y-m-d H:i:s');

        $subHechos = DB::table('hechos')
            ->select('hechos.id')
            ->whereRaw("STR_TO_DATE(CONCAT(hechos.fecha, ' ', hechos.hora), '%Y-%m-%d %H:%i:%s') >= ?", [$inicioStr])
            ->whereRaw("STR_TO_DATE(CONCAT(hechos.fecha, ' ', hechos.hora), '%Y-%m-%d %H:%i:%s') < ?", [$finStr]);

        $base = DB::table('hecho_vehiculo')
            ->join('vehiculo_conductor', 'vehiculo_conductor.vehiculo_id', '=', 'hecho_vehiculo.vehiculo_id')
            ->join('conductores', 'conductores.id', '=', 'vehiculo_conductor.conductor_id')
            ->whereIn('hecho_vehiculo.hecho_id', $subHechos)
            ->select([
                'conductores.id as conductor_id',
                'conductores.sexo as sexo',
                'conductores.edad as edad',
            ])
            ->distinct();

        $total = (int) (clone $base)->count('conductores.id');

        $hombres = (int) (clone $base)
            ->where(function ($q) {
                $q->whereRaw("UPPER(TRIM(COALESCE(conductores.sexo,''))) IN ('M','MASCULINO','HOMBRE')")
                  ->orWhereRaw("UPPER(TRIM(COALESCE(conductores.sexo,''))) LIKE 'MASC%'");
            })
            ->count('conductores.id');

        $mujeres = (int) (clone $base)
            ->where(function ($q) {
                $q->whereRaw("UPPER(TRIM(COALESCE(conductores.sexo,''))) IN ('F','FEMENINO','MUJER')")
                  ->orWhereRaw("UPPER(TRIM(COALESCE(conductores.sexo,''))) LIKE 'FEM%'");
            })
            ->count('conductores.id');

        $menores = (int) (clone $base)
            ->whereRaw("COALESCE(conductores.edad, 0) > 0 AND COALESCE(conductores.edad, 0) < 18")
            ->count('conductores.id');

        return [
            'total' => $total,
            'hombres' => $hombres,
            'mujeres' => $mujeres,
            'menores' => $menores,
        ];
    }

    protected function styleHeaderBlue(): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B5FA5'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    }

    protected function styleBodyThin(): array
    {
        return [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    }

    protected function styleTotalBlue(): array
    {
        return [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'B7E1F3'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];
    }

//********************************************
//                  TABLA 6                 //
//********************************************

protected function buildHechosDeTransitoTable(
    Worksheet $sheet,
    Carbon $inicio,
    Carbon $fin,
    int $startRow = 134
): void {
    $r = $startRow;

    $colNo    = 'B';
    $colHecho = 'C';
    $colCant  = 'D';
    $colLes   = 'E';
    $colHer   = 'F';
    $colDef   = 'G';
    $colFuero = 'H';

    $template = $this->templateHechosDeTransito();
    $counts   = $this->contarHechosDeTransitoPorTipo($inicio, $fin);

    $lastRow = $r + count($template) + 1;
    $this->unmergeRange($sheet, $colNo . $r . ':' . $colFuero . $lastRow);

    $sheet->getStyle($colNo . $r . ':' . $colFuero . $lastRow)->getFill()->setFillType(Fill::FILL_NONE);

    $sheet->setCellValue($colNo . $r, 'No.');
    $sheet->setCellValue($colHecho . $r, 'HECHOS DE TRÁNSITO');
    $sheet->setCellValue($colCant . $r, 'CANTIDAD');
    $sheet->setCellValue($colLes . $r, 'LESIONADOS');
    $sheet->setCellValue($colHer . $r, 'HERIDOS');
    $sheet->setCellValue($colDef . $r, 'DEFUNCIONES');
    $sheet->setCellValue($colFuero . $r, 'FUERO COMÚN');

    $sheet->getRowDimension($r)->setRowHeight(20);
    $sheet->getStyle($colNo . $r . ':' . $colFuero . $r)->applyFromArray($this->styleHeaderBlue());

    $row = $r + 1;

    foreach ($template as $it) {
        $key = (string)($it['key'] ?? '');

        $sheet->setCellValue($colNo . $row, $it['no']);
        $sheet->setCellValue($colHecho . $row, $it['label']);
        $sheet->setCellValue($colCant . $row, (int)($counts['rows'][$key]['cantidad'] ?? 0));
        $sheet->setCellValue($colLes . $row, (int)($counts['rows'][$key]['lesionados'] ?? 0));
        $sheet->setCellValue($colHer . $row, (int)($counts['rows'][$key]['heridos'] ?? 0));
        $sheet->setCellValue($colDef . $row, (int)($counts['rows'][$key]['defunciones'] ?? 0));
        $sheet->setCellValue($colFuero . $row, (int)($counts['rows'][$key]['fuero_comun'] ?? 0));

        $sheet->getRowDimension($row)->setRowHeight(18);

        $sheet->getStyle($colNo . $row . ':' . $colFuero . $row)->applyFromArray($this->styleBodyThin());
        $sheet->getStyle($colNo . $row . ':' . $colFuero . $row)->getFill()->setFillType(Fill::FILL_NONE);

        $sheet->getStyle($colNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colCant . $row . ':' . $colFuero . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
    }

    $totalRow = $row;

    $this->unmergeRange($sheet, $colNo . $totalRow . ':' . $colHecho . $totalRow);
    $sheet->mergeCells($colNo . $totalRow . ':' . $colHecho . $totalRow);

    $sheet->setCellValue($colNo . $totalRow, 'TOTAL');
    $sheet->setCellValue($colCant . $totalRow, (int)($counts['totals']['cantidad'] ?? 0));
    $sheet->setCellValue($colLes . $totalRow, (int)($counts['totals']['lesionados'] ?? 0));
    $sheet->setCellValue($colHer . $totalRow, (int)($counts['totals']['heridos'] ?? 0));
    $sheet->setCellValue($colDef . $totalRow, (int)($counts['totals']['defunciones'] ?? 0));
    $sheet->setCellValue($colFuero . $totalRow, (int)($counts['totals']['fuero_comun'] ?? 0));

    $sheet->getRowDimension($totalRow)->setRowHeight(20);
    $sheet->getStyle($colNo . $totalRow . ':' . $colFuero . $totalRow)->applyFromArray($this->styleTotalBlue());
}

protected function templateHechosDeTransito(): array
{
    return [
        ['no' => 1,  'label' => 'EXPLOSIÓN',                        'key' => 'EXPLOSION'],
        ['no' => 2,  'label' => 'INCENDIO',                         'key' => 'INCENDIO'],
        ['no' => 3,  'label' => 'DESBARRANCAMIENTO',                'key' => 'DESBARRANCAMIENTO'],
        ['no' => 4,  'label' => 'VOLCADURA',                        'key' => 'VOLCADURA'],
        ['no' => 5,  'label' => 'SALIDA DE RODAMIENTO',             'key' => 'SALIDA_RODAMIENTO'],
        ['no' => 6,  'label' => 'SUBIDA A CAMELLÓN',                'key' => 'SUBIDA_CAMELLON'],
        ['no' => 7,  'label' => 'CAIDA DE MOTOCICLETA',             'key' => 'CAIDA_MOTOCICLETA'],
        ['no' => 8,  'label' => 'CHOQUE OBJETO FIJO',               'key' => 'CHOQUE_OBJETO_FIJO'],
        ['no' => 9,  'label' => 'COLISIÓN POR ALCANCE',             'key' => 'COLISION_ALCANCE'],
        ['no' => 10, 'label' => 'COLISIÓN POR NO RESPETAR SEMÁFORO','key' => 'COLISION_SEMAFORO'],
        ['no' => 11, 'label' => 'COLISIÓN POR INVASIÓN DE CARRIL',  'key' => 'COLISION_INVASION_CARRIL'],
        ['no' => 12, 'label' => 'COLISIÓN POR CAMBIO DE CARRIL',    'key' => 'COLISION_CAMBIO_CARRIL'],
        ['no' => 13, 'label' => 'COLISIÓN POR CORTE DE CIRCULACIÓN','key' => 'COLISION_CORTE_CIRCULACION'],
        ['no' => 14, 'label' => 'COLISIÓN POR MANIOBRA REVERSA',    'key' => 'COLISION_REVERSA'],
        ['no' => 15, 'label' => 'CAIDA A CUNETA',                   'key' => 'CAIDA_CUNETA'],
        ['no' => 16, 'label' => 'CAIDA ACUÁTICA DE VEHÍCULO',       'key' => 'CAIDA_ACUATICA'],
        ['no' => 17, 'label' => 'COLISIÓN CON PEATÓN',              'key' => 'COLISION_PEATON'],
    ];
}

protected function unmergeRange(Worksheet $sheet, string $range): void
{
    $merges = $sheet->getMergeCells();
    if (empty($merges)) return;

    [$rStart, $rEnd] = explode(':', $range);
    [$c1, $r1] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($rStart);
    [$c2, $r2] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($rEnd);

    $x1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c1);
    $x2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($c2);
    $y1 = (int)$r1;
    $y2 = (int)$r2;

    $minX = min($x1, $x2);
    $maxX = max($x1, $x2);
    $minY = min($y1, $y2);
    $maxY = max($y1, $y2);

    foreach ($merges as $m => $_) {
        [$ms, $me] = explode(':', $m);
        [$mc1, $mr1] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($ms);
        [$mc2, $mr2] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($me);

        $mx1 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($mc1);
        $mx2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($mc2);
        $my1 = (int)$mr1;
        $my2 = (int)$mr2;

        $mMinX = min($mx1, $mx2);
        $mMaxX = max($mx1, $mx2);
        $mMinY = min($my1, $my2);
        $mMaxY = max($my1, $my2);

        $intersects = !(
            $mMaxX < $minX ||
            $mMinX > $maxX ||
            $mMaxY < $minY ||
            $mMinY > $maxY
        );

        if ($intersects) {
            $sheet->unmergeCells($m);
        }
    }
}

protected function contarHechosDeTransitoPorTipo(Carbon $inicio, Carbon $fin): array
{
    $inicioStr = $inicio->format('Y-m-d H:i:s');
    $finStr    = $fin->format('Y-m-d H:i:s');

    $hasTipo = DB::getSchemaBuilder()->hasColumn('hechos', 'tipo_hecho');
    if (!$hasTipo) {
        $zeros = [];
        foreach ($this->templateHechosDeTransito() as $t) {
            $zeros[$t['key']] = ['cantidad' => 0, 'lesionados' => 0, 'heridos' => 0, 'defunciones' => 0, 'fuero_comun' => 0];
        }
        return [
            'rows' => $zeros,
            'totals' => ['cantidad' => 0, 'lesionados' => 0, 'heridos' => 0, 'defunciones' => 0, 'fuero_comun' => 0],
        ];
    }

    $hasLes = DB::getSchemaBuilder()->hasColumn('hechos', 'lesionados');
    $hasHer = DB::getSchemaBuilder()->hasColumn('hechos', 'heridos');
    $hasDef = DB::getSchemaBuilder()->hasColumn('hechos', 'defunciones');
    $hasFue = DB::getSchemaBuilder()->hasColumn('hechos', 'fuero_comun');

    $select = [
        'tipo_hecho',
        DB::raw('COUNT(*) as cantidad'),
        DB::raw(($hasLes ? 'SUM(COALESCE(lesionados,0))' : '0') . ' as lesionados'),
        DB::raw(($hasHer ? 'SUM(COALESCE(heridos,0))' : '0') . ' as heridos'),
        DB::raw(($hasDef ? 'SUM(COALESCE(defunciones,0))' : '0') . ' as defunciones'),
        DB::raw(($hasFue ? 'SUM(COALESCE(fuero_comun,0))' : '0') . ' as fuero_comun'),
    ];

    $base = DB::table('hechos')
        ->whereRaw("STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') >= ?", [$inicioStr])
        ->whereRaw("STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') < ?", [$finStr])
        ->groupBy('tipo_hecho')
        ->get($select);

    $out = [];
    foreach ($this->templateHechosDeTransito() as $t) {
        $out[$t['key']] = ['cantidad' => 0, 'lesionados' => 0, 'heridos' => 0, 'defunciones' => 0, 'fuero_comun' => 0];
    }

    $map = [
        $this->norm('VOLCADURA') => 'VOLCADURA',

        $this->norm('SALIDA DE SUPERFICIE DE RODAMIENTO') => 'SALIDA_RODAMIENTO',
        $this->norm('SALIDA DE RODAMIENTO') => 'SALIDA_RODAMIENTO',

        $this->norm('SUBIDA AL CAMELLÓN') => 'SUBIDA_CAMELLON',
        $this->norm('SUBIDA A CAMELLÓN') => 'SUBIDA_CAMELLON',

        $this->norm('CAIDA DE MOTOCICLETA') => 'CAIDA_MOTOCICLETA',

        $this->norm('CAIDA A CUNETA') => 'CAIDA_CUNETA',

        $this->norm('COLISIÓN CON PEATÓN') => 'COLISION_PEATON',

        $this->norm('COLISIÓN POR ALCANCE') => 'COLISION_ALCANCE',

        $this->norm('COLISIÓN POR NO RESPETAR SEMÁFORO') => 'COLISION_SEMAFORO',

        $this->norm('COLISIÓN POR INVASIÓN DE CARRIL') => 'COLISION_INVASION_CARRIL',

        $this->norm('COLISIÓN POR CORTE DE CIRCULACIÓN') => 'COLISION_CORTE_CIRCULACION',

        $this->norm('COLISIÓN POR CAMBIO DE CARRIL') => 'COLISION_CAMBIO_CARRIL',

        $this->norm('COLISIÓN POR MANIOBRA DE REVERSA') => 'COLISION_REVERSA',
        $this->norm('COLISIÓN POR MANIOBRA REVERSA') => 'COLISION_REVERSA',

        $this->norm('COLISIÓN CONTRA OBJETO FIJO') => 'CHOQUE_OBJETO_FIJO',
        $this->norm('COLISIÓN CONTRA OBJETO') => 'CHOQUE_OBJETO_FIJO',
        $this->norm('CHOQUE OBJETO FIJO') => 'CHOQUE_OBJETO_FIJO',

        $this->norm('CAIDA ACUATICA DE VEHÍCULO') => 'CAIDA_ACUATICA',
        $this->norm('CAIDA ACUÁTICA DE VEHÍCULO') => 'CAIDA_ACUATICA',

        $this->norm('DESBARRANCAMIENTO') => 'DESBARRANCAMIENTO',
        $this->norm('INCENDIO') => 'INCENDIO',
        $this->norm('EXPLOSIÓN') => 'EXPLOSION',
        $this->norm('EXPLOSION') => 'EXPLOSION',
    ];

    foreach ($base as $r) {
        $tipoRaw = (string)($r->tipo_hecho ?? '');
        $tipoN   = $this->norm($tipoRaw);

        $key = $map[$tipoN] ?? 'COLISION_ALCANCE';

        $out[$key]['cantidad']    = (int)($out[$key]['cantidad'] ?? 0) + (int)($r->cantidad ?? 0);
        $out[$key]['lesionados']  = (int)($out[$key]['lesionados'] ?? 0) + (int)($r->lesionados ?? 0);
        $out[$key]['heridos']     = (int)($out[$key]['heridos'] ?? 0) + (int)($r->heridos ?? 0);
        $out[$key]['defunciones'] = (int)($out[$key]['defunciones'] ?? 0) + (int)($r->defunciones ?? 0);
        $out[$key]['fuero_comun'] = (int)($out[$key]['fuero_comun'] ?? 0) + (int)($r->fuero_comun ?? 0);
    }

    $totals = ['cantidad' => 0, 'lesionados' => 0, 'heridos' => 0, 'defunciones' => 0, 'fuero_comun' => 0];
    foreach ($out as $vals) {
        $totals['cantidad']    += (int)($vals['cantidad'] ?? 0);
        $totals['lesionados']  += (int)($vals['lesionados'] ?? 0);
        $totals['heridos']     += (int)($vals['heridos'] ?? 0);
        $totals['defunciones'] += (int)($vals['defunciones'] ?? 0);
        $totals['fuero_comun'] += (int)($vals['fuero_comun'] ?? 0);
    }

    return [
        'rows' => $out,
        'totals' => $totals,
    ];
}


//********************************************
//                  TABLA 7                 //
//********************************************


protected function buildBloqueFinalTresTablas(
    Worksheet $sheet,
    Carbon $inicio,
    Carbon $fin,
    int $startRow = 126
): void {
    $r = $startRow;

    $sheet->getColumnDimension('E')->setWidth(6);

    $this->buildHechosTransitoPorTipoVehiculoLeft($sheet, $inicio, $fin, $r);

    $rightR1 = $r;
    $this->buildHechosTransitoInvolucradosRight($sheet, $inicio, $fin, $rightR1);

    $rightR2 = $rightR1 + 6;
    $this->buildLiberacionesRight($sheet, $inicio, $fin, $rightR2);

    $rightR3 = $rightR2 + 7;
    $this->buildAreasAuxiliaresRight($sheet, $inicio, $fin, $rightR3);
}

protected function buildHechosTransitoPorTipoVehiculoLeft(
    Worksheet $sheet,
    Carbon $inicio,
    Carbon $fin,
    int $startRow
): void {
    $colNo   = 'B';
    $colDesc = 'C';
    $colCant = 'D';

    $template = $this->templateHechosTransitoVehiculosLeft();
    $counts   = $this->contarHechosTransitoVehiculosLeft($inicio, $fin);

    $lastRow = $startRow + count($template);

    $this->unmergeRange($sheet, $colNo . $startRow . ':' . $colCant . $lastRow);
    $sheet->getStyle($colNo . $startRow . ':' . $colCant . $lastRow)->getFill()->setFillType(Fill::FILL_NONE);

    $sheet->setCellValue($colNo . $startRow, 'No.');
    $sheet->setCellValue($colDesc . $startRow, 'HECHOS DE TRÁNSITO');
    $sheet->setCellValue($colCant . $startRow, 'CANTIDAD');

    $sheet->getRowDimension($startRow)->setRowHeight(20);
    $sheet->getStyle($colNo . $startRow . ':' . $colCant . $startRow)->applyFromArray($this->styleHeaderBlue());

    $row = $startRow + 1;

    foreach ($template as $it) {
        $key = (string)($it['key'] ?? '');

        $sheet->setCellValue($colNo . $row, (int)$it['no']);
        $sheet->setCellValue($colDesc . $row, (string)$it['label']);
        $sheet->setCellValue($colCant . $row, (int)($counts[$key] ?? 0));

        $sheet->getRowDimension($row)->setRowHeight(18);

        $sheet->getStyle($colNo . $row . ':' . $colCant . $row)->applyFromArray($this->styleBodyThin());
        $sheet->getStyle($colNo . $row . ':' . $colCant . $row)->getFill()->setFillType(Fill::FILL_NONE);

        $sheet->getStyle($colNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colCant . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
    }
}

protected function templateHechosTransitoVehiculosLeft(): array
{
    return [
        ['no' => 1,  'label' => 'SERVICIO PÚBLICO FED',              'key' => 'SERVICIO_PUBLICO_FED'],
        ['no' => 2,  'label' => 'TRANSPORTE PÚBLICO',                'key' => 'TRANSPORTE_PUBLICO'],
        ['no' => 3,  'label' => 'AUTOMÓVIL',                         'key' => 'AUTOMOVIL'],
        ['no' => 4,  'label' => 'CAMIONETA',                         'key' => 'CAMIONETA'],
        ['no' => 5,  'label' => 'MICROBUS',                          'key' => 'MICROBUS'],
        ['no' => 6,  'label' => 'CAMIÓN URBANO DE PASAJEROS',        'key' => 'CAMION_URBANO'],
        ['no' => 7,  'label' => 'OMNIBUS',                           'key' => 'OMNIBUS'],
        ['no' => 8,  'label' => 'CAMIONETA DE CARGA',                'key' => 'CAMIONETA_CARGA'],
        ['no' => 9,  'label' => 'CAMION DE CARGA',                   'key' => 'CAMION_CARGA'],
        ['no' => 10, 'label' => 'TRACTOR',                           'key' => 'TRACTOR'],
        ['no' => 11, 'label' => 'FERROCARRIL',                       'key' => 'FERROCARRIL'],
        ['no' => 12, 'label' => 'MOTOCICLETA',                       'key' => 'MOTOCICLETA'],
        ['no' => 13, 'label' => 'BICICLETA',                         'key' => 'BICICLETA'],
        ['no' => 14, 'label' => 'OTRO',                              'key' => 'OTRO'],
        ['no' => 15, 'label' => 'SEMOVIENTE',                        'key' => 'SEMOVIENTE'],
    ];
}

protected function contarHechosTransitoVehiculosLeft(Carbon $inicio, Carbon $fin): array
{
    $keys = array_column($this->templateHechosTransitoVehiculosLeft(), 'key');
    $out = [];
    foreach ($keys as $k) {
        $out[$k] = 0;
    }
    return $out;
}

protected function buildHechosTransitoInvolucradosRight(
    Worksheet $sheet,
    Carbon $inicio,
    Carbon $fin,
    int $startRow
): void {
    $colNo   = 'F';
    $colDesc = 'G';
    $colCant = 'H';

    $items = [
        ['no' => 1, 'label' => 'VEHÍCULOS PARTICULARES INVOL.',   'key' => 'PARTICULARES'],
        ['no' => 2, 'label' => 'VEHÍCULOS SERV. PÚBLIC. INVOL.',  'key' => 'PUBLICO'],
        ['no' => 3, 'label' => 'MOTOS INVOLUCRADAS',              'key' => 'MOTOS'],
        ['no' => 4, 'label' => 'VEHÍCULOS OFICIALES INVOL',       'key' => 'OFICIALES'],
    ];

    $counts = $this->contarInvolucradosRight($inicio, $fin);

    $lastRow = $startRow + count($items);
    $this->unmergeRange($sheet, $colNo . $startRow . ':' . $colCant . $lastRow);

    $sheet->setCellValue($colNo . $startRow, 'No.');
    $sheet->setCellValue($colDesc . $startRow, 'HECHOS DE TRÁNSITO');
    $sheet->setCellValue($colCant . $startRow, 'CANTIDAD');

    $sheet->getRowDimension($startRow)->setRowHeight(20);
    $sheet->getStyle($colNo . $startRow . ':' . $colCant . $startRow)->applyFromArray($this->styleHeaderBlue());

    $row = $startRow + 1;

    foreach ($items as $it) {
        $key = (string)$it['key'];

        $sheet->setCellValue($colNo . $row, (int)$it['no']);
        $sheet->setCellValue($colDesc . $row, (string)$it['label']);
        $sheet->setCellValue($colCant . $row, (int)($counts[$key] ?? 0));

        $sheet->getRowDimension($row)->setRowHeight(18);
        $sheet->getStyle($colNo . $row . ':' . $colCant . $row)->applyFromArray($this->styleBodyThin());

        $sheet->getStyle($colNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colCant . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
    }
}

protected function contarInvolucradosRight(Carbon $inicio, Carbon $fin): array
{
    return [
        'PARTICULARES' => 0,
        'PUBLICO'      => 0,
        'MOTOS'        => 0,
        'OFICIALES'    => 0,
    ];
}

protected function buildLiberacionesRight(
    Worksheet $sheet,
    Carbon $inicio,
    Carbon $fin,
    int $startRow
): void {
    $colNo   = 'F';
    $colDesc = 'G';
    $colCant = 'H';

    $items = [
        ['no' => 1, 'label' => 'LIBERACIÓN MOTOCICLETAS', 'key' => 'MOTOCICLETAS'],
        ['no' => 2, 'label' => 'LIBERACIÓN VEHÍCULOS',    'key' => 'VEHICULOS'],
        ['no' => 3, 'label' => 'LIBERACIÓN CAMIONES',     'key' => 'CAMIONES'],
        ['no' => 4, 'label' => 'LIBERACIÓN REMOLQUES',    'key' => 'REMOLQUES'],
    ];

    $counts = $this->contarLiberacionesRight($inicio, $fin);

    $headerRow = $startRow;
    $firstRow  = $headerRow + 1;
    $lastRow   = $firstRow + count($items) - 1;
    $totalRow  = $lastRow + 1;

    $this->unmergeRange($sheet, $colNo . $headerRow . ':' . $colCant . $totalRow);

    $sheet->setCellValue($colNo . $headerRow, 'No.');
    $sheet->setCellValue($colDesc . $headerRow, 'LIBERACIONES');
    $sheet->setCellValue($colCant . $headerRow, 'CANTIDAD');

    $sheet->getRowDimension($headerRow)->setRowHeight(20);
    $sheet->getStyle($colNo . $headerRow . ':' . $colCant . $headerRow)->applyFromArray($this->styleHeaderBlue());

    $row = $firstRow;
    $sum = 0;

    foreach ($items as $it) {
        $key = (string)$it['key'];
        $val = (int)($counts[$key] ?? 0);

        $sheet->setCellValue($colNo . $row, (int)$it['no']);
        $sheet->setCellValue($colDesc . $row, (string)$it['label']);
        $sheet->setCellValue($colCant . $row, $val);

        $sheet->getRowDimension($row)->setRowHeight(18);
        $sheet->getStyle($colNo . $row . ':' . $colCant . $row)->applyFromArray($this->styleBodyThin());

        $sheet->getStyle($colNo . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($colCant . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sum += $val;
        $row++;
    }

    $this->unmergeRange($sheet, $colNo . $totalRow . ':' . $colDesc . $totalRow);
    $sheet->mergeCells($colNo . $totalRow . ':' . $colDesc . $totalRow);

    $sheet->setCellValue($colNo . $totalRow, 'TOTAL');
    $sheet->setCellValue($colCant . $totalRow, $sum);

    $sheet->getRowDimension($totalRow)->setRowHeight(20);
    $sheet->getStyle($colNo . $totalRow . ':' . $colCant . $totalRow)->applyFromArray($this->styleTotalBlue());

    $sheet->getStyle($colCant . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

protected function contarLiberacionesRight(Carbon $inicio, Carbon $fin): array
{
    return [
        'MOTOCICLETAS' => 0,
        'VEHICULOS'    => 0,
        'CAMIONES'     => 0,
        'REMOLQUES'    => 0,
    ];
}

protected function buildAreasAuxiliaresRight(
    Worksheet $sheet,
    Carbon $inicio,
    Carbon $fin,
    int $startRow
): void {
    $colNo   = 'F';
    $colDesc = 'G';
    $colCant = 'H';

    $headerRow = $startRow;
    $row1      = $headerRow + 1;

    $this->unmergeRange($sheet, $colNo . $headerRow . ':' . $colCant . $row1);

    $sheet->setCellValue($colNo . $headerRow, 'No.');
    $sheet->setCellValue($colDesc . $headerRow, 'ÁREAS AUXILIARES');
    $sheet->setCellValue($colCant . $headerRow, 'CANTIDAD');

    $sheet->getRowDimension($headerRow)->setRowHeight(20);
    $sheet->getStyle($colNo . $headerRow . ':' . $colCant . $headerRow)->applyFromArray($this->styleHeaderBlue());

    $examenTeorico = $this->contarExamenTeoricoEnRango($inicio, $fin);

    $sheet->setCellValue($colNo . $row1, 1);
    $sheet->setCellValue($colDesc . $row1, 'EXÁMEN TEÓRICO');
    $sheet->setCellValue($colCant . $row1, $examenTeorico);

    $sheet->getRowDimension($row1)->setRowHeight(18);
    $sheet->getStyle($colNo . $row1 . ':' . $colCant . $row1)->applyFromArray($this->styleBodyThin());

    $sheet->getStyle($colNo . $row1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($colCant . $row1)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

protected function contarExamenTeoricoEnRango(Carbon $inicio, Carbon $fin): int
{
    if (!$this->tablaExiste('modulo_examenes_diarios')) {
        return 0;
    }

    $ini = $inicio->toDateString();
    $end = $fin->copy()->subSecond()->toDateString();

    return (int) DB::table('modulo_examenes_diarios')
        ->whereBetween('fecha', [$ini, $end])
        ->sum(DB::raw('COALESCE(total,0)'));
}



















































}
