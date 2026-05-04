<?php

namespace App\Services\Exports\Sheets;

use App\Models\Personal;
use App\Services\EstadoFuerzaService;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EstadoFuerzaSheet
{
    private const UNIDAD_SINIESTROS_ID = 1;

    protected EstadoFuerzaService $estadoService;

    public function __construct(EstadoFuerzaService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    public function build(Spreadsheet $spreadsheet, Carbon $corte): Worksheet
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('EST. FUR');

        $startCol = 'B';
        $startRow = 2;

        $fechaLabelCell = $startCol . $startRow;
        $fechaValueCell = 'C' . $startRow;
        $titleStartCell = 'D' . $startRow;
        $titleEndCell   = 'M' . $startRow;

        $headerRow = 3;
        $dataStartRow = 4;

        $colUnidad       = 'B';
        $colCategoria    = 'C';
        $colPresentes    = 'D';
        $colFrancos      = 'E';
        $colFaltando     = 'F';
        $colCursos       = 'G';
        $colVacaciones   = 'H';
        $colComisionados = 'I';
        $colIncapacidad  = 'J';
        $colPermiso      = 'K';
        $colOtros        = 'L';
        $colTotal        = 'M';

        $navy = '0B2A5B';
        $lightBlue = 'CFE2F3';

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

        $styleFechaBar = [
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

        $styleTitleBarBorders = [
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
        $sheet->setCellValue($titleStartCell, 'ESTADO DE FUERZA DE PERSONAL');

        $sheet->getStyle("{$fechaLabelCell}:{$fechaValueCell}")->applyFromArray($styleFechaBar);
        $sheet->getStyle("{$titleStartCell}:{$titleEndCell}")->applyFromArray($styleTitle);
        $sheet->getStyle("{$titleStartCell}:{$titleEndCell}")->applyFromArray($styleTitleBarBorders);

        $sheet->getRowDimension($startRow)->setRowHeight(32);

        $sheet->setCellValue("{$colUnidad}{$headerRow}", 'UNIDAD');
        $sheet->setCellValue("{$colCategoria}{$headerRow}", 'CATEGORIA');
        $sheet->setCellValue("{$colPresentes}{$headerRow}", 'PRESENTES');
        $sheet->setCellValue("{$colFrancos}{$headerRow}", 'FRANCOS');
        $sheet->setCellValue("{$colFaltando}{$headerRow}", 'FALTANDO');
        $sheet->setCellValue("{$colCursos}{$headerRow}", 'CURSOS');
        $sheet->setCellValue("{$colVacaciones}{$headerRow}", 'VACACIONES');
        $sheet->setCellValue("{$colComisionados}{$headerRow}", 'COMISIONADOS');
        $sheet->setCellValue("{$colIncapacidad}{$headerRow}", 'INCAPACIDAD');
        $sheet->setCellValue("{$colPermiso}{$headerRow}", 'PERMISO');
        $sheet->setCellValue("{$colOtros}{$headerRow}", 'OTROS');
        $sheet->setCellValue("{$colTotal}{$headerRow}", "TOTAL, POR\nAGRUPAMIENTO");

        $sheet->getStyle("{$colUnidad}{$headerRow}:{$colTotal}{$headerRow}")->applyFromArray($styleHeader);
        $sheet->getRowDimension($headerRow)->setRowHeight(44);

        $sheet->getColumnDimension($colUnidad)->setWidth(18);
        $sheet->getColumnDimension($colCategoria)->setWidth(18);
        foreach ([$colPresentes,$colFrancos,$colFaltando,$colCursos,$colVacaciones,$colComisionados,$colIncapacidad,$colPermiso,$colOtros] as $c) {
            $sheet->getColumnDimension($c)->setWidth(12);
        }
        $sheet->getColumnDimension($colTotal)->setWidth(20);

        $personales = Personal::with(['turno', 'incidencias', 'unidad'])
            ->where('estatus', 'ACTIVO')
            ->where('unidad_id', self::UNIDAD_SINIESTROS_ID)
            ->get();

        $agrupado = [];

        foreach ($personales as $personal) {
            $estado = $this->estadoService->estado($personal, $corte);

            $unidad = 'SIN_UNIDAD';
            if ($personal->unidad) {
                $unidad = (string)($personal->unidad->nombre ?? $personal->unidad->name ?? 'SIN_UNIDAD');
            }

            $categoria = (string)($personal->categoria ?? 'SIN_CATEGORIA');

            if (!isset($agrupado[$unidad][$categoria])) {
                $agrupado[$unidad][$categoria] = [
                    'PRESENTES' => 0,
                    'FRANCOS' => 0,
                    'FALTANDO' => 0,
                    'CURSOS' => 0,
                    'VACACIONES' => 0,
                    'COMISIONADOS' => 0,
                    'INCAPACIDAD' => 0,
                    'PERMISO' => 0,
                    'OTROS' => 0,
                ];
            }

            switch ($estado) {
                case 'EN_SERVICIO':
                    $agrupado[$unidad][$categoria]['PRESENTES']++;
                    break;
                case 'FRANCO':
                    $agrupado[$unidad][$categoria]['FRANCOS']++;
                    break;
                case 'FALTANDO':
                    $agrupado[$unidad][$categoria]['FALTANDO']++;
                    break;
                case 'CURSOS':
                    $agrupado[$unidad][$categoria]['CURSOS']++;
                    break;
                case 'VACACIONES':
                    $agrupado[$unidad][$categoria]['VACACIONES']++;
                    break;
                case 'COMISIONADOS':
                    $agrupado[$unidad][$categoria]['COMISIONADOS']++;
                    break;
                case 'INCAPACIDAD':
                    $agrupado[$unidad][$categoria]['INCAPACIDAD']++;
                    break;
                case 'PERMISO':
                    $agrupado[$unidad][$categoria]['PERMISO']++;
                    break;
                default:
                    $agrupado[$unidad][$categoria]['OTROS']++;
                    break;
            }
        }

        ksort($agrupado);
        foreach ($agrupado as $u => $cats) {
            ksort($agrupado[$u]);
        }

        $row = $dataStartRow;

        foreach ($agrupado as $unidad => $categorias) {
            $unidadRowStart = $row;
            $unidadRowEnd = $row + count($categorias) - 1;

            foreach ($categorias as $categoria => $conteos) {
                $sheet->setCellValue("{$colCategoria}{$row}", $categoria);

                $sheet->setCellValue("{$colPresentes}{$row}", $conteos['PRESENTES']);
                $sheet->setCellValue("{$colFrancos}{$row}", $conteos['FRANCOS']);
                $sheet->setCellValue("{$colFaltando}{$row}", $conteos['FALTANDO']);
                $sheet->setCellValue("{$colCursos}{$row}", $conteos['CURSOS']);
                $sheet->setCellValue("{$colVacaciones}{$row}", $conteos['VACACIONES']);
                $sheet->setCellValue("{$colComisionados}{$row}", $conteos['COMISIONADOS']);
                $sheet->setCellValue("{$colIncapacidad}{$row}", $conteos['INCAPACIDAD']);
                $sheet->setCellValue("{$colPermiso}{$row}", $conteos['PERMISO']);
                $sheet->setCellValue("{$colOtros}{$row}", $conteos['OTROS']);

                $total = array_sum($conteos);
                $sheet->setCellValue("{$colTotal}{$row}", $total);

                $sheet->getStyle("{$colCategoria}{$row}:{$colOtros}{$row}")->applyFromArray($styleBody);
                $sheet->getStyle("{$colTotal}{$row}")->applyFromArray($styleTotalCell);

                $sheet->getRowDimension($row)->setRowHeight(24);

                $row++;
            }

            if ($unidadRowEnd > $unidadRowStart) {
                $sheet->mergeCells("{$colUnidad}{$unidadRowStart}:{$colUnidad}{$unidadRowEnd}");
            }

            $sheet->setCellValue("{$colUnidad}{$unidadRowStart}", $unidad);
            $sheet->getStyle("{$colUnidad}{$unidadRowStart}:{$colUnidad}{$unidadRowEnd}")->applyFromArray($styleUnidadCell);

            for ($r = $unidadRowStart; $r <= $unidadRowEnd; $r++) {
                $sheet->getStyle("{$colUnidad}{$r}")->applyFromArray($styleUnidadCell);
            }
        }

        $sheet->getStyle("{$titleStartCell}:{$titleEndCell}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        return $sheet;
    }
}
