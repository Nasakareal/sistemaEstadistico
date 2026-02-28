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

        $subHechos = DB::table('hechos')
            ->select('id')
            ->whereRaw("STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') >= ?", [$inicioStr])
            ->whereRaw("STR_TO_DATE(CONCAT(fecha, ' ', hora), '%Y-%m-%d %H:%i:%s') < ?", [$finStr]);

        $candidatas = [
            ['table' => 'hecho_conductores', 'hecho_col' => 'hecho_id', 'conductor_col' => 'conductor_id'],
            ['table' => 'hechos_conductores', 'hecho_col' => 'hecho_id', 'conductor_col' => 'conductor_id'],
            ['table' => 'conductor_hecho', 'hecho_col' => 'hecho_id', 'conductor_col' => 'conductor_id'],
            ['table' => 'hecho_conductor', 'hecho_col' => 'hecho_id', 'conductor_col' => 'conductor_id'],
        ];

        foreach ($candidatas as $cand) {
            if ($this->tablaExiste($cand['table'])) {
                return (int) DB::table($cand['table'])
                    ->whereIn($cand['hecho_col'], $subHechos)
                    ->distinct()
                    ->count($cand['conductor_col']);
            }
        }

        if ($this->tablaExiste('vehiculos') && $this->tablaExiste('conductor_vehiculo')) {
            return (int) DB::table('conductor_vehiculo')
                ->join('vehiculos', 'vehiculos.id', '=', 'conductor_vehiculo.vehiculo_id')
                ->whereIn('vehiculos.hecho_id', $subHechos)
                ->distinct()
                ->count('conductor_vehiculo.conductor_id');
        }

        $hechos = (int) $this->contarHechosEnRango($inicio, $fin);
        return $hechos;
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
                                //                  HOJA 2                  //
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

        if (str_contains($t, 'MOTO')) return 'motocicleta';

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
            str_contains($t, 'MICROBUS') ||
            str_contains($t, 'AUTOBUS')
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

        return 'otros';
    }















































































}
