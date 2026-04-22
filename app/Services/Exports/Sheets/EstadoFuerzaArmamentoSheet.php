<?php

namespace App\Services\Exports\Sheets;

use App\Models\Personal;
use App\Services\EstadoFuerzaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EstadoFuerzaArmamentoSheet
{
    protected EstadoFuerzaService $estadoService;

    public function __construct(EstadoFuerzaService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    public function build(Spreadsheet $spreadsheet, Carbon $corte): Worksheet
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('ARM.');

        $startRow = 2;

        $fechaLabelCell = 'B' . $startRow;
        $fechaValueCell = 'C' . $startRow;
        $titleStartCell = 'D' . $startRow;
        $titleEndCell   = 'L' . $startRow;

        $groupHeaderRow = 3;
        $subHeaderRow   = 4;
        $dataStartRow   = 5;

        $colUnidad          = 'B';

        $colArmaCorta       = 'C';
        $colArmaLarga       = 'D';
        $colArmasTotal      = 'E';

        $colCargadores      = 'F';
        $colCartuchos9mm    = 'G';
        $colCartuchos223556 = 'H';
        $colCartuchos038    = 'I';

        $colAsignado        = 'J';
        $colEnDeposito      = 'K';
        $colTotalFinal      = 'L';

        $navy = '0B2A5B';
        $lightBlue = 'CFE2F3';

        $styleTopBar = [
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $navy],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $styleTitle = [
            'font' => [
                'bold' => true,
                'size' => 18,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $navy],
            ],
        ];

        $styleTitleBorders = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $styleHeader = [
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $styleBody = [
            'font' => [
                'bold' => false,
                'size' => 11,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $lightBlue],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $styleUnidadCell = $styleBody;
        $styleUnidadCell['font']['bold'] = true;

        $styleTotalCell = $styleBody;
        $styleTotalCell['font']['bold'] = true;
        $styleTotalCell['font']['size'] = 16;

        $sheet->setCellValue($fechaLabelCell, 'FECHA');
        $sheet->setCellValue($fechaValueCell, $corte->format('d/m/Y'));

        $sheet->mergeCells("{$titleStartCell}:{$titleEndCell}");
        $sheet->setCellValue($titleStartCell, 'ESTADO DE FUERZA DE ARMAMENTO');

        $sheet->getStyle("{$fechaLabelCell}:{$fechaValueCell}")->applyFromArray($styleTopBar);
        $sheet->getStyle("{$titleStartCell}:{$titleEndCell}")->applyFromArray($styleTitle);
        $sheet->getStyle("{$titleStartCell}:{$titleEndCell}")->applyFromArray($styleTitleBorders);

        $sheet->getRowDimension($startRow)->setRowHeight(32);

        $sheet->mergeCells("{$colUnidad}{$groupHeaderRow}:{$colUnidad}{$subHeaderRow}");
        $sheet->setCellValue("{$colUnidad}{$groupHeaderRow}", 'UNIDAD');

        $sheet->mergeCells("{$colArmaCorta}{$groupHeaderRow}:{$colArmasTotal}{$groupHeaderRow}");
        $sheet->setCellValue("{$colArmaCorta}{$groupHeaderRow}", 'ARMAS');

        $sheet->mergeCells("{$colCargadores}{$groupHeaderRow}:{$colCartuchos038}{$groupHeaderRow}");
        $sheet->setCellValue("{$colCargadores}{$groupHeaderRow}", 'MUNICIÓN');

        $sheet->mergeCells("{$colAsignado}{$groupHeaderRow}:{$colTotalFinal}{$groupHeaderRow}");
        $sheet->setCellValue("{$colAsignado}{$groupHeaderRow}", 'DONDE SE ENCUENTRAN');

        $sheet->setCellValue("{$colArmaCorta}{$subHeaderRow}", 'ARMA CORTA');
        $sheet->setCellValue("{$colArmaLarga}{$subHeaderRow}", 'ARMA LARGA');
        $sheet->setCellValue("{$colArmasTotal}{$subHeaderRow}", 'TOTAL');

        $sheet->setCellValue("{$colCargadores}{$subHeaderRow}", 'CARGADORES');
        $sheet->setCellValue("{$colCartuchos9mm}{$subHeaderRow}", 'CARTUCHOS 9mm');
        $sheet->setCellValue("{$colCartuchos223556}{$subHeaderRow}", "CARTUCHOS .223\nY/O 5.56");
        $sheet->setCellValue("{$colCartuchos038}{$subHeaderRow}", 'CARTUCHOS 0.38');

        $sheet->setCellValue("{$colAsignado}{$subHeaderRow}", 'ASIGNADO');
        $sheet->setCellValue("{$colEnDeposito}{$subHeaderRow}", 'EN DEPÓSITO');
        $sheet->setCellValue("{$colTotalFinal}{$subHeaderRow}", 'TOTAL');

        $sheet->getStyle("B{$groupHeaderRow}:L{$groupHeaderRow}")->applyFromArray($styleHeader);
        $sheet->getStyle("B{$subHeaderRow}:L{$subHeaderRow}")->applyFromArray($styleHeader);

        $sheet->getRowDimension($groupHeaderRow)->setRowHeight(24);
        $sheet->getRowDimension($subHeaderRow)->setRowHeight(44);

        $sheet->getColumnDimension($colUnidad)->setWidth(18);
        foreach (['C','D','E'] as $c) $sheet->getColumnDimension($c)->setWidth(12);
        foreach (['F','G','H','I'] as $c) $sheet->getColumnDimension($c)->setWidth(16);
        foreach (['J','K','L'] as $c) $sheet->getColumnDimension($c)->setWidth(14);

        $fecha = $corte->copy()->startOfDay()->toDateString();

        $personales = Personal::with(['turno', 'incidencias', 'unidad'])
            ->whereNull('deleted_at')
            ->where('estatus', 'ACTIVO')
            ->where('unidad_id', 1)
            ->get();

        $enServicioIds = [];
        foreach ($personales as $personal) {
            $estado = $this->estadoService->estado($personal, $corte);
            if ($estado === 'EN_SERVICIO') {
                $enServicioIds[] = (int)$personal->id;
            }
        }

        $armasPorUnidad = DB::table('armamentos')
            ->select([
                'unidad_id',
                DB::raw("SUM(CASE WHEN tipo = 'ARMA CORTA' THEN 1 ELSE 0 END) as arma_corta"),
                DB::raw("SUM(CASE WHEN tipo = 'ARMA LARGA' THEN 1 ELSE 0 END) as arma_larga"),
                DB::raw("COUNT(*) as total_armas"),
                DB::raw("COALESCE(SUM(cargadores_cantidad),0) as cargadores_total"),
                DB::raw("COALESCE(SUM(cartuchos_cantidad),0) as cartuchos_total_all"),
            ])
            ->whereNull('deleted_at')
            ->where('estatus', 'ACTIVO')
            ->where('unidad_id', 1)
            ->groupBy('unidad_id')
            ->get()
            ->keyBy('unidad_id');

        $municionPorUnidad = DB::table('armamentos')
            ->select(['unidad_id', 'calibre', DB::raw('COALESCE(SUM(cartuchos_cantidad),0) as cartuchos_sum')])
            ->whereNull('deleted_at')
            ->where('estatus', 'ACTIVO')
            ->where('unidad_id', 1)
            ->groupBy('unidad_id', 'calibre')
            ->get();

        $mun9 = [];
        $mun223556 = [];
        $mun038 = [];

        foreach ($municionPorUnidad as $rowMun) {
            $unidadId = (int)$rowMun->unidad_id;
            $cal = (string)($rowMun->calibre ?? '');
            $sum = (int)$rowMun->cartuchos_sum;

            $grupo = $this->grupoCalibre($cal);

            if ($grupo === '9mm') {
                $mun9[$unidadId] = ($mun9[$unidadId] ?? 0) + $sum;
            } elseif ($grupo === '223556') {
                $mun223556[$unidadId] = ($mun223556[$unidadId] ?? 0) + $sum;
            } elseif ($grupo === '038') {
                $mun038[$unidadId] = ($mun038[$unidadId] ?? 0) + $sum;
            }
        }

        $asignadoPorUnidad = [];
        if (!empty($enServicioIds)) {
            $asignadoQuery = DB::table('personal_asignacions as pa')
                ->join('armamentos as a', 'a.id', '=', 'pa.armamento_id')
                ->select([
                    'a.unidad_id',
                    DB::raw('COUNT(DISTINCT a.id) as asignado'),
                ])
                ->where('pa.activo', 1)
                ->whereNull('a.deleted_at')
                ->where('a.estatus', 'ACTIVO')
                ->whereIn('pa.personal_id', $enServicioIds)
                ->whereDate('pa.fecha_asignacion', '<=', $fecha)
                ->where(function ($q) use ($fecha) {
                    $q->whereNull('pa.fecha_fin')
                      ->orWhereDate('pa.fecha_fin', '>=', $fecha);
                })
                ->groupBy('a.unidad_id')
                ->get();

            foreach ($asignadoQuery as $aRow) {
                $asignadoPorUnidad[(int)$aRow->unidad_id] = (int)$aRow->asignado;
            }
        }

        $unidadIds = $armasPorUnidad->keys()->map(fn ($v) => (int)$v)->values()->all();
        sort($unidadIds);

        $unidadNombre = $this->mapUnidadesNombre($personales);

        $row = $dataStartRow;

        foreach ($unidadIds as $unidadId) {
            $info = $armasPorUnidad->get($unidadId);
            if (!$info) continue;

            $armaCorta = (int)$info->arma_corta;
            $armaLarga = (int)$info->arma_larga;
            $totalArmas = (int)$info->total_armas;

            $cargadores = (int)$info->cargadores_total;
            $cart9 = (int)($mun9[$unidadId] ?? 0);
            $cart223556 = (int)($mun223556[$unidadId] ?? 0);
            $cart038 = (int)($mun038[$unidadId] ?? 0);

            $asignado = (int)($asignadoPorUnidad[$unidadId] ?? 0);
            $enDeposito = max($totalArmas - $asignado, 0);

            $nombreUnidad = $unidadNombre[$unidadId] ?? ('UNIDAD_' . $unidadId);

            $sheet->setCellValue("{$colUnidad}{$row}", $nombreUnidad);

            $sheet->setCellValue("{$colArmaCorta}{$row}", $armaCorta);
            $sheet->setCellValue("{$colArmaLarga}{$row}", $armaLarga);
            $sheet->setCellValue("{$colArmasTotal}{$row}", $totalArmas);

            $sheet->setCellValue("{$colCargadores}{$row}", $cargadores);
            $sheet->setCellValue("{$colCartuchos9mm}{$row}", $cart9);
            $sheet->setCellValue("{$colCartuchos223556}{$row}", $cart223556);
            $sheet->setCellValue("{$colCartuchos038}{$row}", $cart038);

            $sheet->setCellValue("{$colAsignado}{$row}", $asignado);
            $sheet->setCellValue("{$colEnDeposito}{$row}", $enDeposito);
            $sheet->setCellValue("{$colTotalFinal}{$row}", $totalArmas);

            $sheet->getStyle("B{$row}:I{$row}")->applyFromArray($styleBody);
            $sheet->getStyle("{$colUnidad}{$row}")->applyFromArray($styleUnidadCell);
            $sheet->getStyle("J{$row}:L{$row}")->applyFromArray($styleBody);

            $sheet->getStyle("{$colTotalFinal}{$row}")->applyFromArray($styleTotalCell);
            $sheet->getRowDimension($row)->setRowHeight(24);

            $row++;
        }

        return $sheet;
    }

    private function grupoCalibre(string $calibre): ?string
    {
        $c = mb_strtolower(trim($calibre));
        $c = str_replace([' ', "\t"], '', $c);

        if ($c === '') return null;

        if (str_contains($c, '9mm') || str_contains($c, '9-mm') || str_contains($c, '9.0mm')) {
            return '9mm';
        }

        if (str_contains($c, '0.223') || str_contains($c, '.223')) {
            return '223556';
        }

        if (str_contains($c, '5.56') || str_contains($c, '5,56') || str_contains($c, '5.56x45') || str_contains($c, '5.56x45mm')) {
            return '223556';
        }

        if (str_contains($c, '0.38') || str_contains($c, '.38')) {
            return '038';
        }

        return null;
    }

    private function mapUnidadesNombre($personales): array
    {
        $map = [];

        foreach ($personales as $p) {
            $uid = (int)($p->unidad_id ?? 0);
            if ($uid <= 0) continue;
            if (isset($map[$uid])) continue;

            if ($p->unidad) {
                $nombre = (string)($p->unidad->nombre ?? $p->unidad->name ?? '');
                if ($nombre !== '') {
                    $map[$uid] = $nombre;
                }
            }
        }

        return $map;
    }
}
