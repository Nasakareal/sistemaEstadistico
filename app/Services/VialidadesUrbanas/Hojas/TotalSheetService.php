<?php

namespace App\Services\VialidadesUrbanas\Hojas;

use App\Models\Actividad;
use App\Models\Unidad;
use App\Models\VialidadDispositivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TotalSheetService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    public function generar(Worksheet $sheet, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $actividades = $this->actividadesVialidadesUrbanas($inicio, $fin);
        $dispositivos = $this->dispositivosVialidadesUrbanas($inicio, $fin);

        $rows = $this->construirFilas($actividades, $dispositivos);

        $this->render(
            $sheet,
            $fecha,
            $rows,
            $this->controlesVehiculares($inicio, $fin),
            $this->controlAseguramientos($actividades, $dispositivos),
            $this->hechosTransito($inicio, $fin),
            $this->tiposHechosTransito($inicio, $fin),
            $this->choquesDanios($inicio, $fin),
            $this->clasificacionVehiculos($inicio, $fin)
        );
    }

    private function render(
        Worksheet $sheet,
        string $fecha,
        array $rows,
        ?array $controlVehicular = null,
        ?array $controlAseguramientos = null,
        ?array $hechosTransito = null,
        ?array $tiposHechosTransito = null,
        ?array $choquesDanios = null,
        ?array $clasificacionVehiculos = null
    ): void
    {
        $blue = '0070C0';
        $green = '00B050';
        $cyan = '00B0F0';
        $band = '9BC2E6';

        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $sheet->setShowGridlines(false);
        $sheet->getSheetView()->setZoomScale(85);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(66);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(18);
        $sheet->getColumnDimension('G')->setWidth(28);
        $sheet->getColumnDimension('H')->setWidth(19);
        $sheet->getColumnDimension('I')->setWidth(19);

        $sheet->setCellValue('C1', 'VIALIDADES URBANAS');
        $sheet->setCellValue('B2', 'FECHA');
        $sheet->setCellValue('C2', Carbon::parse($fecha)->format('d/m/Y'));

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getStyle('B1:C2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = [
            'A' => 'No.',
            'B' => 'CATEGORÍA',
            'C' => 'ACTIVIDAD',
            'D' => 'CANTIDAD',
            'E' => "ESTADO DE\nFUERZA\nPARTICIPANTE",
            'F' => "UNIDADES\nPARTICIPANTES",
            'G' => 'KILOMETROS RECORRIDOS',
            'H' => "PERSONAS\nALCANZADAS",
            'I' => 'RECOMENDACIONES',
        ];

        foreach ($headers as $column => $header) {
            $sheet->setCellValue("{$column}3", $header);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        $sheet->getRowDimension(3)->setRowHeight(50);
        $sheet->getStyle('A3:C3')->applyFromArray($headerStyle);
        $sheet->getStyle('D3:G3')->applyFromArray($headerStyle);
        $sheet->getStyle('H3:I3')->applyFromArray($headerStyle);
        $sheet->getStyle('A3:C3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($blue);
        $sheet->getStyle('D3:G3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($green);
        $sheet->getStyle('H3:I3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cyan);

        $bodyStyle = [
            'font' => [
                'color' => ['rgb' => '000000'],
                'size' => 10,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        $rowNumber = 4;

        foreach ($rows as $index => $row) {
            $sheet->setCellValue("A{$rowNumber}", $row['no']);
            $sheet->setCellValue("B{$rowNumber}", $row['categoria']);
            $sheet->setCellValue("C{$rowNumber}", $row['actividad']);
            $sheet->setCellValue("D{$rowNumber}", $this->valorVisible($row['cantidad']));
            $sheet->setCellValue("E{$rowNumber}", $this->valorVisible($row['estado_fuerza']));
            $sheet->setCellValue("F{$rowNumber}", $this->valorVisible($row['unidades']));
            $sheet->setCellValue("G{$rowNumber}", $this->valorVisible($row['kilometros']));
            $sheet->setCellValue("H{$rowNumber}", $this->valorVisible($row['personas']));
            $sheet->setCellValue("I{$rowNumber}", $this->valorVisible($row['recomendaciones']));

            $sheet->getStyle("A{$rowNumber}:I{$rowNumber}")->applyFromArray($bodyStyle);
            $sheet->getStyle("A{$rowNumber}:B{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowNumber}:I{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($row['band']) {
                $sheet->getStyle("A{$rowNumber}:I{$rowNumber}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($band);
            }

            $sheet->getRowDimension($rowNumber)->setRowHeight(18);
            $rows[$index]['excel_row'] = $rowNumber;
            $rowNumber++;
        }

        $this->mergeBloques($sheet, $rows);

        $totalRow = $rowNumber;
        $sheet->mergeCells("A{$totalRow}:B{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->setCellValue("C{$totalRow}", 'DISPOSITIVOS REALIZADOS');
        $sheet->setCellValue("D{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'cantidad'))));
        $sheet->setCellValue("E{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'estado_fuerza'))));
        $sheet->setCellValue("F{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'unidades'))));
        $sheet->setCellValue("G{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'kilometros'))));
        $sheet->setCellValue("H{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'personas'))));
        $sheet->setCellValue("I{$totalRow}", $this->numeroVisible(array_sum(array_column($rows, 'recomendaciones'))));

        $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
        $sheet->getStyle("C{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $controlAseguramientos = $controlAseguramientos ?? $this->controlAseguramientosVacios();

        $this->renderControlVehicular($sheet, 80, $controlVehicular ?? $this->controlesVehicularesVacios());
        $this->renderControlAseguramientos($sheet, 96, $controlAseguramientos);
        $this->renderOtrosAseguramientos(
            $sheet,
            112,
            $controlAseguramientos['otros'] ?? $this->otrosAseguramientosVacios()
        );
        $this->renderHechosTransito($sheet, 119, $hechosTransito ?? $this->hechosTransitoVacios());
        $this->renderTiposHechosTransito(
            $sheet,
            125,
            $tiposHechosTransito ?? $this->tiposHechosTransitoVacios()
        );
        $this->renderChoquesDanios($sheet, 145, $choquesDanios ?? $this->choquesDaniosVacios());
        $this->renderClasificacionVehiculos(
            $sheet,
            155,
            $clasificacionVehiculos ?? $this->clasificacionVehiculosVacios()
        );

        $sheet->freezePane('A4');
    }

    private function renderControlVehicular(Worksheet $sheet, int $startRow, array $counts): void
    {
        $blue = '0070C0';
        $cyan = '00B0F0';
        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $columns = [
            'B' => 'No.',
            'C' => 'CONTROL VEHÍCULAR',
            'D' => 'VEHÍCULOS',
            'E' => 'MOTOCICLETAS',
            'F' => 'CAMIONES',
            'G' => 'OTROS',
        ];

        foreach ($columns as $column => $label) {
            $sheet->setCellValue("{$column}{$startRow}", $label);
        }

        $sheet->getRowDimension($startRow)->setRowHeight(20);
        $sheet->getStyle("B{$startRow}:G{$startRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);

        $row = $startRow + 1;
        $totals = [
            'vehiculos' => 0,
            'motocicletas' => 0,
            'camiones' => 0,
            'otros' => 0,
        ];

        foreach ($this->templateControlVehicular() as $item) {
            $key = $item['key'];
            $values = $counts[$key] ?? ['vehiculos' => 0, 'motocicletas' => 0, 'camiones' => 0, 'otros' => 0];

            $sheet->setCellValue("B{$row}", $item['no']);
            $sheet->setCellValue("C{$row}", $item['label']);
            $sheet->setCellValue("D{$row}", $this->valorVisible($values['vehiculos'] ?? 0));
            $sheet->setCellValue("E{$row}", $this->valorVisible($values['motocicletas'] ?? 0));
            $sheet->setCellValue("F{$row}", $this->valorVisible($values['camiones'] ?? 0));
            $sheet->setCellValue("G{$row}", $this->valorVisible($values['otros'] ?? 0));

            $sheet->getRowDimension($row)->setRowHeight(18);
            $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'allBorders' => $thinBorder,
                ],
            ]);
            $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$row}:G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            foreach ($totals as $bucket => $_) {
                $totals[$bucket] += (int) ($values[$bucket] ?? 0);
            }

            $row++;
        }

        $sheet->mergeCells("B{$row}:C{$row}");
        $sheet->setCellValue("B{$row}", 'TOTAL');
        $sheet->setCellValue("D{$row}", $totals['vehiculos']);
        $sheet->setCellValue("E{$row}", $totals['motocicletas']);
        $sheet->setCellValue("F{$row}", $totals['camiones']);
        $sheet->setCellValue("G{$row}", $totals['otros']);

        $sheet->getRowDimension($row)->setRowHeight(20);
        $sheet->getStyle("B{$row}:G{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
    }

    private function renderControlAseguramientos(Worksheet $sheet, int $startRow, array $counts): void
    {
        $blue = '0070C0';
        $cyan = '00B0F0';
        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $headerRow = $startRow + 1;
        $firstBodyRow = $startRow + 2;

        $sheet->mergeCells("B{$startRow}:H{$startRow}");
        $sheet->setCellValue("B{$startRow}", 'CONTROL DE ASEGURAMIENTOS');
        $sheet->getRowDimension($startRow)->setRowHeight(24);
        $sheet->getStyle("B{$startRow}:H{$startRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 14,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);

        $headers = [
            'B' => 'No.',
            'C' => 'PERSONAS ASEGURADAS',
            'D' => 'TOTAL',
            'E' => 'ARMAS',
            'F' => 'TOTAL',
            'G' => 'DROGA',
            'H' => 'TOTAL',
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue("{$column}{$headerRow}", $label);
        }

        $sheet->getRowDimension($headerRow)->setRowHeight(20);
        $sheet->getStyle("B{$headerRow}:H{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);

        $totalPersonas = 0;

        foreach ($this->templatePersonasAseguradas() as $index => $item) {
            $row = $firstBodyRow + $index;
            $total = $counts['personas'][$item['key']] ?? 0;
            $totalPersonas += (int) $total;

            $sheet->setCellValue("B{$row}", $item['no']);
            $sheet->setCellValue("C{$row}", $item['label']);
            $sheet->setCellValue("D{$row}", $this->valorVisible($total));
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        $totalPersonasRow = $firstBodyRow + count($this->templatePersonasAseguradas());
        $sheet->mergeCells("B{$totalPersonasRow}:C{$totalPersonasRow}");
        $sheet->setCellValue("B{$totalPersonasRow}", 'TOTAL');
        $sheet->setCellValue("D{$totalPersonasRow}", $totalPersonas);

        $totalArmas = 0.0;

        foreach ($this->templateArmasAseguradas() as $index => $item) {
            $row = $firstBodyRow + $index;
            $total = $counts['armas'][$item['key']] ?? 0;
            $totalArmas += (float) $total;

            $sheet->setCellValue("E{$row}", $item['label']);
            $sheet->setCellValue("F{$row}", $this->valorVisible($total));
        }

        $totalArmasRow = $firstBodyRow + count($this->templateArmasAseguradas());
        $sheet->setCellValue("E{$totalArmasRow}", 'TOTAL');
        $sheet->setCellValue("F{$totalArmasRow}", $this->numeroVisible($totalArmas));

        $totalDrogas = 0.0;

        foreach ($this->templateDrogaAsegurada() as $index => $item) {
            $row = $firstBodyRow + $index;
            $total = $counts['drogas'][$item['key']] ?? 0;
            $totalDrogas += (float) $total;

            $sheet->setCellValue("G{$row}", $item['label']);
            $sheet->setCellValue("H{$row}", $this->valorVisible($total));
        }

        $totalDrogasRow = $firstBodyRow + count($this->templateDrogaAsegurada());
        $sheet->setCellValue("G{$totalDrogasRow}", 'TOTAL');
        $sheet->setCellValue("H{$totalDrogasRow}", $this->numeroVisible($totalDrogas));

        $sheet->getStyle("B{$firstBodyRow}:D{$totalPersonasRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
        $sheet->getStyle("E{$firstBodyRow}:H{$totalArmasRow}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);

        $sheet->getStyle("B{$firstBodyRow}:B" . ($totalPersonasRow - 1))->getFont()->setBold(true);
        $sheet->getStyle("B{$firstBodyRow}:B{$totalPersonasRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D{$firstBodyRow}:D{$totalPersonasRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$firstBodyRow}:F{$totalArmasRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H{$firstBodyRow}:H{$totalDrogasRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle("B{$totalPersonasRow}:D{$totalPersonasRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
        $sheet->getStyle("E{$totalArmasRow}:H{$totalArmasRow}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
    }

    private function renderOtrosAseguramientos(Worksheet $sheet, int $startRow, array $counts): void
    {
        $blue = '0070C0';
        $cyan = '00B0F0';
        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $headers = [
            'B' => 'No.',
            'C' => 'OTROS ASEGURAMIENTOS',
            'D' => 'TOTAL',
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue("{$column}{$startRow}", $label);
        }

        $sheet->getRowDimension($startRow)->setRowHeight(20);
        $sheet->getStyle("B{$startRow}:D{$startRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);

        $row = $startRow + 1;
        $total = 0.0;

        foreach ($this->templateOtrosAseguramientos() as $item) {
            $value = $counts[$item['key']] ?? 0;
            $total += (float) $value;

            $sheet->setCellValue("B{$row}", $item['no']);
            $sheet->setCellValue("C{$row}", $item['label']);
            $sheet->setCellValue("D{$row}", $this->valorVisible($value));
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        $sheet->mergeCells("B{$row}:C{$row}");
        $sheet->setCellValue("B{$row}", 'TOTAL');
        $sheet->setCellValue("D{$row}", $this->numeroVisible($total));

        $sheet->getStyle("B" . ($startRow + 1) . ":D{$row}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
        $sheet->getStyle("B" . ($startRow + 1) . ':B' . ($row - 1))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($startRow + 1) . ":B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D" . ($startRow + 1) . ":D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}:D{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
    }

    private function renderHechosTransito(Worksheet $sheet, int $startRow, array $counts): void
    {
        $blue = '0070C0';
        $cyan = '00B0F0';
        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $resumen = $counts['resumen'] ?? [];
        $involucrados = $counts['involucrados'] ?? [];

        foreach ([
            'B' => 'No.',
            'C' => 'HECHOS DE TRÁNSITO',
            'D' => 'CANTIDAD',
            'F' => 'No.',
            'G' => 'HECHOS DE TRÁNSITO',
            'H' => 'CANTIDAD',
        ] as $column => $label) {
            $sheet->setCellValue("{$column}{$startRow}", $label);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        $bodyStyle = [
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        $totalStyle = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        $sheet->getRowDimension($startRow)->setRowHeight(20);
        $sheet->getStyle("B{$startRow}:D{$startRow}")->applyFromArray($headerStyle);
        $sheet->getStyle("F{$startRow}:H{$startRow}")->applyFromArray($headerStyle);

        $totalResumen = 0;

        foreach ($this->templateHechosTransitoResumen() as $index => $item) {
            $row = $startRow + 1 + $index;
            $value = (int) ($resumen[$item['key']] ?? 0);
            $totalResumen += $value;

            $sheet->setCellValue("B{$row}", $item['no']);
            $sheet->setCellValue("C{$row}", $item['label']);
            $sheet->setCellValue("D{$row}", $this->valorVisible($value));
            $sheet->getRowDimension($row)->setRowHeight(18);
        }

        $totalResumenRow = $startRow + 1 + count($this->templateHechosTransitoResumen());
        $sheet->mergeCells("B{$totalResumenRow}:C{$totalResumenRow}");
        $sheet->setCellValue("B{$totalResumenRow}", 'TOTAL');
        $sheet->setCellValue("D{$totalResumenRow}", $totalResumen);

        $totalInvolucrados = 0;

        foreach ($this->templateHechosTransitoInvolucrados() as $index => $item) {
            $row = $startRow + 1 + $index;
            $value = (int) ($involucrados[$item['key']] ?? 0);
            $totalInvolucrados += $value;

            $sheet->setCellValue("F{$row}", $item['no']);
            $sheet->setCellValue("G{$row}", $item['label']);
            $sheet->setCellValue("H{$row}", $this->valorVisible($value));
        }

        $totalInvolucradosRow = $startRow + 1 + count($this->templateHechosTransitoInvolucrados());
        $sheet->mergeCells("F{$totalInvolucradosRow}:G{$totalInvolucradosRow}");
        $sheet->setCellValue("F{$totalInvolucradosRow}", 'TOTAL');
        $sheet->setCellValue("H{$totalInvolucradosRow}", $totalInvolucrados);

        $sheet->getStyle("B" . ($startRow + 1) . ":D{$totalResumenRow}")->applyFromArray($bodyStyle);
        $sheet->getStyle("F" . ($startRow + 1) . ":H{$totalInvolucradosRow}")->applyFromArray($bodyStyle);
        $sheet->getStyle("B" . ($startRow + 1) . ':B' . ($totalResumenRow - 1))->getFont()->setBold(true);
        $sheet->getStyle("F" . ($startRow + 1) . ':F' . ($totalInvolucradosRow - 1))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($startRow + 1) . ":B{$totalResumenRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D" . ($startRow + 1) . ":D{$totalResumenRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F" . ($startRow + 1) . ":F{$totalInvolucradosRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H" . ($startRow + 1) . ":H{$totalInvolucradosRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$totalResumenRow}:D{$totalResumenRow}")->applyFromArray($totalStyle);
        $sheet->getStyle("F{$totalInvolucradosRow}:H{$totalInvolucradosRow}")->applyFromArray($totalStyle);
        $sheet->getStyle('G' . ($startRow + 3))->getFont()->setItalic(true);
    }

    private function renderTiposHechosTransito(Worksheet $sheet, int $startRow, array $counts): void
    {
        $blue = '0070C0';
        $cyan = '00B0F0';
        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $headers = [
            'B' => 'No.',
            'C' => 'HECHOS DE TRÁNSITO',
            'D' => 'CANTIDAD',
            'E' => 'LESIONADOS',
            'F' => 'HERIDOS',
            'G' => 'DEFUNCIONES',
            'H' => 'FUERO COMÚN',
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue("{$column}{$startRow}", $label);
        }

        $sheet->getRowDimension($startRow)->setRowHeight(20);
        $sheet->getStyle("B{$startRow}:H{$startRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);

        $row = $startRow + 1;
        $totals = [
            'cantidad' => 0,
            'lesionados' => 0,
            'heridos' => 0,
            'defunciones' => 0,
            'fuero_comun' => 0,
        ];

        foreach ($this->templateTiposHechosTransito() as $item) {
            $values = $counts[$item['key']] ?? [
                'cantidad' => 0,
                'lesionados' => 0,
                'heridos' => 0,
                'defunciones' => 0,
                'fuero_comun' => 0,
            ];

            $sheet->setCellValue("B{$row}", $item['no']);
            $sheet->setCellValue("C{$row}", $item['label']);
            $sheet->setCellValue("D{$row}", $this->valorVisible($values['cantidad'] ?? 0));
            $sheet->setCellValue("E{$row}", $this->valorVisible($values['lesionados'] ?? 0));
            $sheet->setCellValue("F{$row}", $this->valorVisible($values['heridos'] ?? 0));
            $sheet->setCellValue("G{$row}", $this->valorVisible($values['defunciones'] ?? 0));
            $sheet->setCellValue("H{$row}", $this->valorVisible($values['fuero_comun'] ?? 0));
            $sheet->getRowDimension($row)->setRowHeight(18);

            foreach ($totals as $key => $_) {
                $totals[$key] += (int) ($values[$key] ?? 0);
            }

            $row++;
        }

        $sheet->mergeCells("B{$row}:C{$row}");
        $sheet->setCellValue("B{$row}", 'TOTAL');
        $sheet->setCellValue("D{$row}", $totals['cantidad']);
        $sheet->setCellValue("E{$row}", $totals['lesionados']);
        $sheet->setCellValue("F{$row}", $totals['heridos']);
        $sheet->setCellValue("G{$row}", $totals['defunciones']);
        $sheet->setCellValue("H{$row}", $totals['fuero_comun']);

        $sheet->getStyle("B" . ($startRow + 1) . ":H{$row}")->applyFromArray([
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
        $sheet->getStyle("B" . ($startRow + 1) . ':B' . ($row - 1))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($startRow + 1) . ":B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D" . ($startRow + 1) . ":H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}:H{$row}")->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);
    }

    private function renderChoquesDanios(Worksheet $sheet, int $startRow, array $counts): void
    {
        $blue = '0070C0';
        $cyan = '00B0F0';
        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        foreach ([
            'B' => 'No.',
            'C' => 'HECHOS DE TRÁNSITO',
            'D' => 'CANTIDAD',
            'F' => 'No.',
            'G' => 'HECHOS DE TRÁNSITO',
            'H' => 'CANTIDAD',
        ] as $column => $label) {
            $sheet->setCellValue("{$column}{$startRow}", $label);
        }

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];
        $bodyStyle = [
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];
        $totalStyle = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        $sheet->getRowDimension($startRow)->setRowHeight(20);
        $sheet->getStyle("B{$startRow}:D{$startRow}")->applyFromArray($headerStyle);
        $sheet->getStyle("F{$startRow}:H{$startRow}")->applyFromArray($headerStyle);

        $row = $startRow + 1;
        $totalChoques = 0;
        $tipos = $counts['tipos'] ?? [];

        foreach ($this->templateChoquesDanios() as $item) {
            $value = (int) ($tipos[$item['key']] ?? 0);
            $totalChoques += $value;

            $sheet->setCellValue("B{$row}", $item['no']);
            $sheet->setCellValue("C{$row}", $item['label']);
            $sheet->setCellValue("D{$row}", $this->valorVisible($value));
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        $sheet->mergeCells("B{$row}:C{$row}");
        $sheet->setCellValue("B{$row}", 'TOTAL');
        $sheet->setCellValue("D{$row}", $totalChoques);

        $sheet->getStyle("B" . ($startRow + 1) . ":D{$row}")->applyFromArray($bodyStyle);
        $sheet->getStyle("B" . ($startRow + 1) . ':B' . ($row - 1))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($startRow + 1) . ":B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D" . ($startRow + 1) . ":D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B{$row}:D{$row}")->applyFromArray($totalStyle);

        $danios = $counts['danios'] ?? [];
        $montoMateriales = (float) ($danios['materiales'] ?? 0);
        $montoVehiculos = (float) ($danios['vehiculos'] ?? 0);
        $montoOtros = (float) ($danios['otros'] ?? 0);

        $rightRow = $startRow + 1;

        foreach ($this->templateMontosDanios() as $item) {
            $value = (float) ($danios[$item['key']] ?? 0);

            $sheet->setCellValue("F{$rightRow}", $item['no']);
            $sheet->setCellValue("G{$rightRow}", $item['label']);
            $sheet->setCellValue("H{$rightRow}", $this->valorVisible($value));
            $sheet->getRowDimension($rightRow)->setRowHeight(18);
            $rightRow++;
        }

        $sheet->mergeCells("F{$rightRow}:G{$rightRow}");
        $sheet->setCellValue("F{$rightRow}", 'TOTAL');
        $sheet->setCellValue("H{$rightRow}", $this->numeroVisible($montoMateriales ?: ($montoVehiculos + $montoOtros)));

        $sheet->getStyle("F" . ($startRow + 1) . ":H{$rightRow}")->applyFromArray($bodyStyle);
        $sheet->getStyle("F" . ($startRow + 1) . ':F' . ($rightRow - 1))->getFont()->setBold(true);
        $sheet->getStyle("F" . ($startRow + 1) . ":F{$rightRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H" . ($startRow + 1) . ":H{$rightRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H" . ($startRow + 1) . ":H{$rightRow}")->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle("F{$rightRow}:H{$rightRow}")->applyFromArray($totalStyle);
    }

    private function renderClasificacionVehiculos(Worksheet $sheet, int $startRow, array $counts): void
    {
        $blue = '0070C0';
        $cyan = '00B0F0';
        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];
        $cyanHeaderStyle = $headerStyle;
        $cyanHeaderStyle['fill']['startColor']['rgb'] = $cyan;
        $bodyStyle = [
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];
        $totalStyle = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $cyan],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ];

        foreach ([
            'B' => 'No.',
            'C' => 'HECHOS DE TRÁNSITO',
            'D' => 'CANTIDAD',
            'F' => 'No.',
            'G' => 'HECHOS DE TRÁNSITO',
            'H' => 'CANTIDAD',
        ] as $column => $label) {
            $sheet->setCellValue("{$column}{$startRow}", $label);
        }

        $sheet->getStyle("B{$startRow}:D{$startRow}")->applyFromArray($headerStyle);
        $sheet->getStyle("F{$startRow}:H{$startRow}")->applyFromArray($headerStyle);
        $sheet->getRowDimension($startRow)->setRowHeight(20);

        $row = $startRow + 1;
        $clasificacion = $counts['clasificacion'] ?? [];

        foreach ($this->templateClasificacionVehiculos() as $item) {
            $value = (int) ($clasificacion[$item['key']] ?? 0);

            $sheet->setCellValue("B{$row}", $item['no']);
            $sheet->setCellValue("C{$row}", $item['label']);
            $sheet->setCellValue("D{$row}", $this->valorVisible($value));
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        $sheet->getStyle("B" . ($startRow + 1) . ':D' . ($row - 1))->applyFromArray($bodyStyle);
        $sheet->getStyle("B" . ($startRow + 1) . ':B' . ($row - 1))->getFont()->setBold(true);
        $sheet->getStyle("B" . ($startRow + 1) . ':B' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("D" . ($startRow + 1) . ':D' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rightRow = $startRow + 1;
        $resumen = $counts['resumen'] ?? [];

        foreach ($this->templateResumenVehiculosInvolucrados() as $item) {
            $value = (int) ($resumen[$item['key']] ?? 0);

            $sheet->setCellValue("F{$rightRow}", $item['no']);
            $sheet->setCellValue("G{$rightRow}", $item['label']);
            $sheet->setCellValue("H{$rightRow}", $this->valorVisible($value));
            $sheet->getRowDimension($rightRow)->setRowHeight(18);
            $rightRow++;
        }

        $sheet->getStyle("F" . ($startRow + 1) . ':H' . ($rightRow - 1))->applyFromArray($bodyStyle);
        $sheet->getStyle("F" . ($startRow + 1) . ':F' . ($rightRow - 1))->getFont()->setBold(true);
        $sheet->getStyle("F" . ($startRow + 1) . ':F' . ($rightRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("H" . ($startRow + 1) . ':H' . ($rightRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $liberacionesHeaderRow = $startRow + 6;
        $sheet->setCellValue("F{$liberacionesHeaderRow}", 'No.');
        $sheet->setCellValue("G{$liberacionesHeaderRow}", 'LIBERACIONES');
        $sheet->setCellValue("H{$liberacionesHeaderRow}", 'CANTIDAD');
        $sheet->getStyle("F{$liberacionesHeaderRow}:H{$liberacionesHeaderRow}")->applyFromArray($cyanHeaderStyle);
        $sheet->getRowDimension($liberacionesHeaderRow)->setRowHeight(20);

        $liberaciones = $counts['liberaciones'] ?? [];
        $liberacionesRow = $liberacionesHeaderRow + 1;
        $totalLiberaciones = 0;

        foreach ($this->templateLiberacionesVehiculos() as $item) {
            $value = (int) ($liberaciones[$item['key']] ?? 0);
            $totalLiberaciones += $value;

            $sheet->setCellValue("F{$liberacionesRow}", $item['no']);
            $sheet->setCellValue("G{$liberacionesRow}", $item['label']);
            $sheet->setCellValue("H{$liberacionesRow}", $this->valorVisible($value));
            $sheet->getRowDimension($liberacionesRow)->setRowHeight(18);
            $liberacionesRow++;
        }

        $sheet->mergeCells("F{$liberacionesRow}:G{$liberacionesRow}");
        $sheet->setCellValue("F{$liberacionesRow}", 'TOTAL');
        $sheet->setCellValue("H{$liberacionesRow}", $totalLiberaciones);
        $sheet->getStyle('F' . ($liberacionesHeaderRow + 1) . ":H{$liberacionesRow}")->applyFromArray($bodyStyle);
        $sheet->getStyle('F' . ($liberacionesHeaderRow + 1) . ':F' . ($liberacionesRow - 1))->getFont()->setBold(true);
        $sheet->getStyle('F' . ($liberacionesHeaderRow + 1) . ":F{$liberacionesRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . ($liberacionesHeaderRow + 1) . ":H{$liberacionesRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("F{$liberacionesRow}:H{$liberacionesRow}")->applyFromArray($totalStyle);

        $areasHeaderRow = $startRow + 13;
        $sheet->setCellValue("F{$areasHeaderRow}", 'No.');
        $sheet->setCellValue("G{$areasHeaderRow}", 'ÁREAS AUXILIARES');
        $sheet->setCellValue("H{$areasHeaderRow}", 'CANTIDAD');
        $sheet->getStyle("F{$areasHeaderRow}:H{$areasHeaderRow}")->applyFromArray($cyanHeaderStyle);
        $sheet->getRowDimension($areasHeaderRow)->setRowHeight(20);

        $areas = $counts['areas_auxiliares'] ?? [];
        $areaRow = $areasHeaderRow + 1;
        $sheet->setCellValue("F{$areaRow}", 1);
        $sheet->setCellValue("G{$areaRow}", 'EXÁMEN TEÓRICO');
        $sheet->setCellValue("H{$areaRow}", $this->valorVisible((int) ($areas['examen_teorico'] ?? 0)));
        $sheet->getRowDimension($areaRow)->setRowHeight(18);
        $sheet->getStyle("F{$areaRow}:H{$areaRow}")->applyFromArray($bodyStyle);
        $sheet->getStyle("F{$areaRow}")->getFont()->setBold(true);
        $sheet->getStyle("F{$areaRow}:H{$areaRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("G{$areaRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private function construirFilas(Collection $actividades, ?Collection $dispositivos = null): array
    {
        $rows = array_map(function (array $row): array {
            return $row + [
                'cantidad' => 0,
                'estado_fuerza' => 0,
                'unidades' => 0,
                'kilometros' => 0,
                'personas' => 0,
                'recomendaciones' => 0,
            ];
        }, $this->templateCompleto());

        foreach ($actividades as $actividad) {
            $index = $this->buscarFilaActividad($rows, $actividad);

            if ($index === null) {
                continue;
            }

            $rows[$index]['cantidad'] += $this->cantidadActividad($actividad);
            $rows[$index]['estado_fuerza'] += $this->contarCantidadTexto($actividad->elementos_participantes_texto ?? '');
            $rows[$index]['unidades'] += $this->contarUnidadesTexto($actividad->patrullas_participantes_texto ?? '');
            $rows[$index]['kilometros'] += (float) ($actividad->km_recorridos ?? 0);
            $rows[$index]['personas'] += $this->personasAlcanzadas($actividad);
            $rows[$index]['recomendaciones'] += $this->contarRecomendaciones($actividad);
        }

        foreach (($dispositivos ?? collect()) as $dispositivo) {
            $index = $this->buscarFilaDispositivo($rows, $dispositivo);

            if ($index === null) {
                continue;
            }

            $rows[$index]['cantidad']++;
            $rows[$index]['estado_fuerza'] += (int) ($dispositivo->elementos ?? 0);
            $rows[$index]['unidades'] += (int) ($dispositivo->crp ?? 0)
                + (int) ($dispositivo->motopatrullas ?? 0)
                + (int) ($dispositivo->fenix ?? 0)
                + (int) ($dispositivo->unidades_motorizadas ?? 0)
                + (int) ($dispositivo->patrullas ?? 0)
                + (int) ($dispositivo->gruas ?? 0)
                + (int) ($dispositivo->otros_apoyos ?? 0);
            $rows[$index]['recomendaciones'] += $this->contarRecomendaciones($dispositivo);
        }

        foreach ($rows as &$row) {
            $row['kilometros'] = $this->numeroVisible($row['kilometros']);
        }
        unset($row);

        return $rows;
    }

    private function actividadesVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return Actividad::query()
            ->with(['categoria', 'subcategoria', 'fomentoCulturaVialDetalle'])
            ->whereIn('unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function dispositivosVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return VialidadDispositivo::query()
            ->with(['catalogo', 'detalles'])
            ->whereIn('unidad_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function controlesVehiculares(Carbon $inicio, Carbon $fin): array
    {
        $counts = $this->controlesVehicularesVacios();

        $actividades = Actividad::query()
            ->with(['categoria', 'subcategoria', 'vehiculos'])
            ->whereIn('unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->whereHas('vehiculos')
            ->get();

        foreach ($actividades as $actividad) {
            foreach ($actividad->vehiculos as $vehiculo) {
                $bucket = $this->bucketControlVehicular($vehiculo->tipo ?? '');

                foreach ($this->clavesControlVehicular($actividad, $vehiculo) as $key) {
                    $counts[$key][$bucket]++;
                }
            }
        }

        return $counts;
    }

    private function controlAseguramientos(Collection $actividades, ?Collection $dispositivos = null): array
    {
        $counts = $this->controlAseguramientosVacios();

        foreach ($actividades as $actividad) {
            $texto = $this->textoActividad($actividad);
            $personas = (int) ($actividad->personas_detenidas ?? 0);

            if ($personas <= 0) {
                $personas = $this->contarPersonasAseguradasTexto($texto);
            }

            $this->sumarPersonasAseguradas($counts, $texto, $personas);
            $this->sumarObjetosAsegurados($counts, $texto);
            $this->sumarOtrosAseguramientos($counts, $texto);
        }

        foreach (($dispositivos ?? collect()) as $dispositivo) {
            $texto = $this->textoDispositivo($dispositivo);
            $personas = $this->contarPersonasAseguradasTexto($texto);

            $this->sumarPersonasAseguradas($counts, $texto, $personas);
            $this->sumarObjetosAsegurados($counts, $texto);
            $this->sumarOtrosAseguramientos($counts, $texto);
        }

        return $counts;
    }

    private function hechosTransito(Carbon $inicio, Carbon $fin): array
    {
        $counts = $this->hechosTransitoVacios();

        $hechos = DB::table('hechos as h')
            ->select('h.situacion')
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->whereIn('h.unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->get();

        foreach ($hechos as $hecho) {
            $situacion = $this->normalizar($hecho->situacion ?? '');

            if (in_array($situacion, ['RESUELTO', 'REPORTE'], true)) {
                $counts['resumen']['RESUELTOS']++;
            } elseif ($situacion === 'TURNADO') {
                $counts['resumen']['TURNADOS']++;
            } else {
                $counts['resumen']['PENDIENTES']++;
            }
        }

        $conductores = DB::table('hechos as h')
            ->join('hecho_vehiculo as hv', 'h.id', '=', 'hv.hecho_id')
            ->join('vehiculo_conductor as vc', 'hv.vehiculo_id', '=', 'vc.vehiculo_id')
            ->join('conductores as c', 'vc.conductor_id', '=', 'c.id')
            ->select([
                'c.id',
                'c.sexo',
                'c.edad',
            ])
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->whereIn('h.unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->distinct()
            ->get();

        foreach ($conductores as $conductor) {
            $sexo = $this->normalizar($conductor->sexo ?? '');
            $edad = is_numeric($conductor->edad) ? (int) $conductor->edad : null;

            if (in_array($sexo, ['MASCULINO', 'HOMBRE'], true)) {
                $counts['involucrados']['hombres']++;
            } elseif (in_array($sexo, ['FEMENINO', 'MUJER'], true)) {
                $counts['involucrados']['mujeres']++;
            }

            if ($edad !== null && $edad < 18) {
                $counts['involucrados']['menores']++;
            }
        }

        return $counts;
    }

    private function tiposHechosTransito(Carbon $inicio, Carbon $fin): array
    {
        $counts = $this->tiposHechosTransitoVacios();

        $hechos = DB::table('hechos as h')
            ->select([
                'h.id',
                'h.tipo_hecho',
            ])
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->whereIn('h.unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->get();

        $hechoIds = $hechos->pluck('id')->all();

        foreach ($hechos as $hecho) {
            $key = $this->claveTipoHechoTransito($hecho->tipo_hecho ?? '');

            if (isset($counts[$key])) {
                $counts[$key]['cantidad']++;
            }
        }

        if (!empty($hechoIds)) {
            $lesionados = DB::table('lesionados as l')
                ->join('hechos as h', 'l.hecho_id', '=', 'h.id')
                ->select([
                    'h.tipo_hecho',
                    'l.tipo_lesion',
                ])
                ->whereIn('l.hecho_id', $hechoIds)
                ->get();

            foreach ($lesionados as $lesionado) {
                $key = $this->claveTipoHechoTransito($lesionado->tipo_hecho ?? '');

                if (!isset($counts[$key])) {
                    continue;
                }

                $tipoLesion = $this->normalizar($lesionado->tipo_lesion ?? '');

                if ($tipoLesion === 'FALLECIDO') {
                    $counts[$key]['defunciones']++;
                } else {
                    $counts[$key]['lesionados']++;
                }

                if ($tipoLesion === 'GRAVE') {
                    $counts[$key]['heridos']++;
                }
            }
        }

        return $counts;
    }

    private function choquesDanios(Carbon $inicio, Carbon $fin): array
    {
        $counts = $this->choquesDaniosVacios();

        $hechos = DB::table('hechos as h')
            ->select([
                'h.id',
                'h.tipo_hecho',
                'h.monto_danos_patrimoniales',
            ])
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->whereIn('h.unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->get();

        $hechoIds = $hechos->pluck('id')->all();
        $vehiculosPorHecho = [];

        if (!empty($hechoIds)) {
            $vehiculos = DB::table('hecho_vehiculo as hv')
                ->join('vehiculos as v', 'hv.vehiculo_id', '=', 'v.id')
                ->select([
                    'hv.hecho_id',
                    'v.tipo',
                    'v.monto_danos',
                ])
                ->whereIn('hv.hecho_id', $hechoIds)
                ->get();

            foreach ($vehiculos as $vehiculo) {
                if (!isset($vehiculosPorHecho[$vehiculo->hecho_id])) {
                    $vehiculosPorHecho[$vehiculo->hecho_id] = [];
                }

                $vehiculosPorHecho[$vehiculo->hecho_id][] = $vehiculo;
                $counts['danios']['vehiculos'] += is_numeric($vehiculo->monto_danos)
                    ? (float) $vehiculo->monto_danos
                    : 0;
            }
        }

        foreach ($hechos as $hecho) {
            $counts['danios']['otros'] += is_numeric($hecho->monto_danos_patrimoniales)
                ? (float) $hecho->monto_danos_patrimoniales
                : 0;

            $key = $this->claveChoqueDanios($hecho->tipo_hecho ?? '', $vehiculosPorHecho[$hecho->id] ?? []);

            if (isset($counts['tipos'][$key])) {
                $counts['tipos'][$key]++;
            }
        }

        $counts['danios']['materiales'] = $counts['danios']['vehiculos'] + $counts['danios']['otros'];

        return $counts;
    }

    private function clasificacionVehiculos(Carbon $inicio, Carbon $fin): array
    {
        $counts = $this->clasificacionVehiculosVacios();
        $unidadIds = $this->unidadVialidadesUrbanasIds();

        $vehiculos = DB::table('hechos as h')
            ->join('hecho_vehiculo as hv', 'h.id', '=', 'hv.hecho_id')
            ->join('vehiculos as v', 'hv.vehiculo_id', '=', 'v.id')
            ->select([
                'v.tipo',
                'v.tipo_servicio',
            ])
            ->where('h.captura_completa', 1)
            ->whereNotNull('h.captura_completa_at')
            ->where('h.captura_completa_at', '>=', $inicio->toDateTimeString())
            ->where('h.captura_completa_at', '<', $fin->toDateTimeString())
            ->whereIn('h.unidad_org_id', $unidadIds)
            ->get();

        foreach ($vehiculos as $vehiculo) {
            $tipo = $this->normalizar($vehiculo->tipo ?? '');
            $servicio = $this->normalizar($vehiculo->tipo_servicio ?? '');
            $key = $this->claveClasificacionVehiculo($tipo);

            $counts['clasificacion'][$key] = ($counts['clasificacion'][$key] ?? 0) + 1;
            $this->sumarResumenVehiculo($counts['resumen'], $tipo, $servicio);
        }

        $fechaLiberacion = $fin->copy()->subSecond()->toDateString();
        $liberaciones = DB::table('liberaciones as l')
            ->join('vehiculos as v', 'l.vehiculo_id', '=', 'v.id')
            ->leftJoin('hechos as h', 'l.hecho_id', '=', 'h.id')
            ->select('v.tipo')
            ->whereDate('l.fecha_liberacion', $fechaLiberacion)
            ->where(function ($query) use ($unidadIds) {
                $query->whereNull('l.hecho_id')
                    ->orWhereIn('h.unidad_org_id', $unidadIds);
            })
            ->get();

        foreach ($liberaciones as $liberacion) {
            $key = $this->claveLiberacionVehiculo($liberacion->tipo ?? '');
            $counts['liberaciones'][$key]++;
        }

        return $counts;
    }

    private function controlesVehicularesVacios(): array
    {
        $counts = [];

        foreach ($this->templateControlVehicular() as $item) {
            $counts[$item['key']] = [
                'vehiculos' => 0,
                'motocicletas' => 0,
                'camiones' => 0,
                'otros' => 0,
            ];
        }

        return $counts;
    }

    private function controlAseguramientosVacios(): array
    {
        $counts = [
            'personas' => [],
            'armas' => [],
            'drogas' => [],
            'otros' => [],
        ];

        foreach ($this->templatePersonasAseguradas() as $item) {
            $counts['personas'][$item['key']] = 0;
        }

        foreach ($this->templateArmasAseguradas() as $item) {
            $counts['armas'][$item['key']] = 0;
        }

        foreach ($this->templateDrogaAsegurada() as $item) {
            $counts['drogas'][$item['key']] = 0;
        }

        $counts['otros'] = $this->otrosAseguramientosVacios();

        return $counts;
    }

    private function otrosAseguramientosVacios(): array
    {
        $counts = [];

        foreach ($this->templateOtrosAseguramientos() as $item) {
            $counts[$item['key']] = 0;
        }

        return $counts;
    }

    private function hechosTransitoVacios(): array
    {
        return [
            'resumen' => [
                'RESUELTOS' => 0,
                'PENDIENTES' => 0,
                'TURNADOS' => 0,
            ],
            'involucrados' => [
                'hombres' => 0,
                'mujeres' => 0,
                'menores' => 0,
            ],
        ];
    }

    private function tiposHechosTransitoVacios(): array
    {
        $counts = [];

        foreach ($this->templateTiposHechosTransito() as $item) {
            $counts[$item['key']] = [
                'cantidad' => 0,
                'lesionados' => 0,
                'heridos' => 0,
                'defunciones' => 0,
                'fuero_comun' => 0,
            ];
        }

        return $counts;
    }

    private function choquesDaniosVacios(): array
    {
        $counts = [
            'tipos' => [],
            'danios' => [
                'materiales' => 0.0,
                'vehiculos' => 0.0,
                'otros' => 0.0,
            ],
        ];

        foreach ($this->templateChoquesDanios() as $item) {
            $counts['tipos'][$item['key']] = 0;
        }

        return $counts;
    }

    private function clasificacionVehiculosVacios(): array
    {
        $counts = [
            'clasificacion' => [],
            'resumen' => [
                'particulares' => 0,
                'publicos' => 0,
                'motos' => 0,
                'oficiales' => 0,
            ],
            'liberaciones' => [
                'motos' => 0,
                'vehiculos' => 0,
                'camiones' => 0,
                'remolques' => 0,
            ],
            'areas_auxiliares' => [
                'examen_teorico' => 0,
            ],
        ];

        foreach ($this->templateClasificacionVehiculos() as $item) {
            $counts['clasificacion'][$item['key']] = 0;
        }

        return $counts;
    }

    private function templateControlVehicular(): array
    {
        return [
            ['no' => 1, 'label' => 'REVISIÓN DE ANTECEDENTES', 'key' => 'REVISION_ANTECEDENTES'],
            ['no' => 2, 'label' => 'VEHÍCULOS REVISADOS DE PROCEDENCIA EXTRANJERA', 'key' => 'PROC_EXTRANJERA'],
            ['no' => 3, 'label' => 'DESPOLARIZADO', 'key' => 'DESPOLARIZADO'],
            ['no' => 4, 'label' => 'CORRALON POR FALTAS ADMINISTRATIVAS', 'key' => 'CORRALON_ADMIN'],
            ['no' => 5, 'label' => 'CORRALÓN POR HECHOS DE TRANSITO', 'key' => 'CORRALON_TRANSITO'],
            ['no' => 6, 'label' => 'PUESTOS A DISPOSICIÓN DEL MP POR HECHO DE TRÁNSITO', 'key' => 'MP_TRANSITO'],
            ['no' => 7, 'label' => 'PRESENTADOS AL MP', 'key' => 'PRESENTADOS_MP'],
            ['no' => 8, 'label' => 'RESGUARDADOS POR ABANDONO', 'key' => 'ABANDONO'],
            ['no' => 9, 'label' => 'ASEGURADOS POR HECHOS DELICTIVOS', 'key' => 'DELICTIVOS'],
            ['no' => 10, 'label' => 'RECUPERADOS CON ALTERACIONES EN SUS MEDIOS DE IDENTIFICACIÓN', 'key' => 'ALTERACIONES_ID'],
            ['no' => 11, 'label' => 'RECUPERADOS CON REPORTE DE ROBO', 'key' => 'REC_ROBO'],
            ['no' => 12, 'label' => 'CONOCIMIENTO DE REPORTE DE ROBO', 'key' => 'CONOCIMIENTO_ROBO'],
            ['no' => 13, 'label' => 'ASEGURADOS POR OTROS MOTIVOS', 'key' => 'OTROS_MOTIVOS'],
        ];
    }

    private function templatePersonasAseguradas(): array
    {
        return [
            ['no' => 1, 'label' => 'CONSULTA DE ANTECEDENTES PENALES', 'key' => 'ANTECEDENTES_PENALES'],
            ['no' => 2, 'label' => 'PERSONAS A BARANDILLA', 'key' => 'BARANDILLA'],
            ['no' => 3, 'label' => 'POR ALCOHOLEMIA', 'key' => 'ALCOHOLEMIA'],
            ['no' => 4, 'label' => 'PERSONAS PRESENTADAS AL MP', 'key' => 'PRESENTADAS_MP'],
            ['no' => 5, 'label' => 'POR ROBOS DIVERSOS', 'key' => 'ROBOS_DIVERSOS'],
            ['no' => 6, 'label' => 'POR LESIONES', 'key' => 'LESIONES'],
            ['no' => 7, 'label' => 'POR HOMICIDIO CULPOSO', 'key' => 'HOMICIDIO_CULPOSO'],
            ['no' => 8, 'label' => 'POR HOMICIDIO DOLOSO', 'key' => 'HOMICIDIO_DOLOSO'],
            ['no' => 9, 'label' => 'PERSONAS AL MP POR VEHÍCULOS, MOTOS O CAMIONES ROBADOS', 'key' => 'MP_VEHICULOS_ROBADOS'],
            ['no' => 10, 'label' => 'PERSONAS AL MP POR PORTACION DE ARMAS', 'key' => 'MP_PORTACION_ARMAS'],
            ['no' => 11, 'label' => 'PERSONAS AL MP POR DROGA', 'key' => 'MP_DROGA'],
            ['no' => 12, 'label' => 'OTROS DELITOS', 'key' => 'OTROS_DELITOS'],
        ];
    }

    private function templateArmasAseguradas(): array
    {
        return [
            ['label' => 'ARMAS', 'key' => 'ARMAS'],
            ['label' => 'CORTAS', 'key' => 'CORTAS'],
            ['label' => 'LARGAS', 'key' => 'LARGAS'],
            ['label' => 'CARGADORES', 'key' => 'CARGADORES'],
            ['label' => 'CARTUCHOS', 'key' => 'CARTUCHOS'],
            ['label' => 'GRANADAS', 'key' => 'GRANADAS'],
            ['label' => "LANZA\nGRANADAS", 'key' => 'LANZA_GRANADAS'],
            ['label' => 'PUNZOCORTANTE', 'key' => 'PUNZOCORTANTE'],
        ];
    }

    private function templateDrogaAsegurada(): array
    {
        return [
            ['label' => 'DROGA', 'key' => 'DROGA'],
            ['label' => 'MARIHUANA GRS', 'key' => 'MARIHUANA_GRS'],
            ['label' => 'CRISTAL GRS', 'key' => 'CRISTAL_GRS'],
            ['label' => 'COCAINA GRS', 'key' => 'COCAINA_GRS'],
            ['label' => 'PASTILLAS', 'key' => 'PASTILLAS'],
            ['label' => 'PLANTIOS', 'key' => 'PLANTIOS'],
            ['label' => 'PLANTAS DE MARIHUANA', 'key' => 'PLANTAS_MARIHUANA'],
            ['label' => 'OTRAS DROGAS', 'key' => 'OTRAS_DROGAS'],
        ];
    }

    private function templateOtrosAseguramientos(): array
    {
        return [
            ['no' => 1, 'label' => 'AGUACATE', 'key' => 'AGUACATE'],
            ['no' => 2, 'label' => 'MADERA', 'key' => 'MADERA'],
            ['no' => 3, 'label' => 'DINERO', 'key' => 'DINERO'],
            ['no' => 4, 'label' => 'OTROS ASEGURAMIENTOS (AGREGARLOS)', 'key' => 'OTROS'],
        ];
    }

    private function templateHechosTransitoResumen(): array
    {
        return [
            ['no' => 1, 'label' => 'RESUELTOS', 'key' => 'RESUELTOS'],
            ['no' => 2, 'label' => 'PENDIENTES', 'key' => 'PENDIENTES'],
            ['no' => 3, 'label' => 'TURNADOS', 'key' => 'TURNADOS'],
        ];
    }

    private function templateHechosTransitoInvolucrados(): array
    {
        return [
            ['no' => 1, 'label' => 'HOMBRES INVOLUCRADOS', 'key' => 'hombres'],
            ['no' => 2, 'label' => 'MUJERES INVOLUCRADAS', 'key' => 'mujeres'],
            ['no' => 3, 'label' => 'MENORES INVOLUCRADOS', 'key' => 'menores'],
        ];
    }

    private function templateTiposHechosTransito(): array
    {
        return [
            ['no' => 1, 'label' => 'EXPLOSIÓN', 'key' => 'EXPLOSION'],
            ['no' => 2, 'label' => 'INCENDIO', 'key' => 'INCENDIO'],
            ['no' => 3, 'label' => 'DESBARRANCAMIENTO', 'key' => 'DESBARRANCAMIENTO'],
            ['no' => 4, 'label' => 'VOLCADURA', 'key' => 'VOLCADURA'],
            ['no' => 5, 'label' => 'SALIDA DE RODAMIENTO', 'key' => 'SALIDA_RODAMIENTO'],
            ['no' => 6, 'label' => 'SUBIDA A CAMELLÓN', 'key' => 'SUBIDA_CAMELLON'],
            ['no' => 7, 'label' => 'CAIDA DE MOTOCICLETA', 'key' => 'CAIDA_MOTOCICLETA'],
            ['no' => 8, 'label' => 'CHOQUE OBJETO FIJO', 'key' => 'CHOQUE_OBJETO_FIJO'],
            ['no' => 9, 'label' => 'COLISIÓN POR ALCANCE', 'key' => 'COLISION_ALCANCE'],
            ['no' => 10, 'label' => 'COLISIÓN POR NO RESPETAR SEMÁFORO', 'key' => 'COLISION_SEMAFORO'],
            ['no' => 11, 'label' => 'COLISIÓN POR INVASIÓN DE CARRIL', 'key' => 'COLISION_INVASION_CARRIL'],
            ['no' => 12, 'label' => 'COLISIÓN POR CAMBIO DE CARRIL', 'key' => 'COLISION_CAMBIO_CARRIL'],
            ['no' => 13, 'label' => 'COLISIÓN POR CORTE DE CIRCULACIÓN', 'key' => 'COLISION_CORTE_CIRCULACION'],
            ['no' => 14, 'label' => 'COLISIÓN POR MANIOBRA REVERSA', 'key' => 'COLISION_MANIOBRA_REVERSA'],
            ['no' => 15, 'label' => 'CAIDA A CUNETA', 'key' => 'CAIDA_CUNETA'],
            ['no' => 16, 'label' => 'CAIDA ACUÁTICA DE VEHÍCULO', 'key' => 'CAIDA_ACUATICA'],
            ['no' => 17, 'label' => 'COLISIÓN CON PEATÓN', 'key' => 'COLISION_PEATON'],
        ];
    }

    private function claveTipoHechoTransito($tipo): string
    {
        $tipo = $this->normalizar($tipo);

        $mapa = [
            'EXPLOSION' => 'EXPLOSION',
            'INCENDIO' => 'INCENDIO',
            'DESBARRANCAMIENTO' => 'DESBARRANCAMIENTO',
            'VOLCADURA' => 'VOLCADURA',
            'SALIDA DE SUPERFICIE DE RODAMIENTO' => 'SALIDA_RODAMIENTO',
            'SALIDA DE RODAMIENTO' => 'SALIDA_RODAMIENTO',
            'SUBIDA AL CAMELLON' => 'SUBIDA_CAMELLON',
            'SUBIDA A CAMELLON' => 'SUBIDA_CAMELLON',
            'CAIDA DE MOTOCICLETA' => 'CAIDA_MOTOCICLETA',
            'COLISION CONTRA OBJETO FIJO' => 'CHOQUE_OBJETO_FIJO',
            'CHOQUE OBJETO FIJO' => 'CHOQUE_OBJETO_FIJO',
            'COLISION POR ALCANCE' => 'COLISION_ALCANCE',
            'COLISION POR NO RESPETAR SEMAFORO' => 'COLISION_SEMAFORO',
            'COLISION POR INVASION DE CARRIL' => 'COLISION_INVASION_CARRIL',
            'COLISION POR CAMBIO DE CARRIL' => 'COLISION_CAMBIO_CARRIL',
            'COLISION POR CORTE DE CIRCULACION' => 'COLISION_CORTE_CIRCULACION',
            'COLISION CONTRA SEMOVIENTE' => 'COLISION_CORTE_CIRCULACION',
            'COLISION POR MANIOBRA DE REVERSA' => 'COLISION_MANIOBRA_REVERSA',
            'COLISION POR MANIOBRA REVERSA' => 'COLISION_MANIOBRA_REVERSA',
            'CAIDA A CUNETA' => 'CAIDA_CUNETA',
            'CAIDA ACUATICA DE VEHICULO' => 'CAIDA_ACUATICA',
            'COLISION CON PEATON' => 'COLISION_PEATON',
        ];

        return $mapa[$tipo] ?? $tipo;
    }

    private function templateChoquesDanios(): array
    {
        return [
            ['no' => 1, 'label' => 'CHOQUE ENTRE CAMIÓN Y MOTOCICLETA', 'key' => 'CAMION_MOTO'],
            ['no' => 2, 'label' => 'CHOQUE ENTRE CAMIÓN Y VEHÍCULO', 'key' => 'CAMION_VEHICULO'],
            ['no' => 3, 'label' => 'CHOQUE ENTRE MOTOCICLETAS', 'key' => 'MOTO_MOTO'],
            ['no' => 4, 'label' => 'CHOQUE ENTRE VEHÍCULOS', 'key' => 'VEHICULO_VEHICULO'],
            ['no' => 5, 'label' => 'CHOQUE ENTRE MOTOCICLETA Y VEHÍCULO', 'key' => 'MOTO_VEHICULO'],
            ['no' => 6, 'label' => 'CHOQUE ENTRE VEHÍCULO Y PEATÓN', 'key' => 'VEHICULO_PEATON'],
            ['no' => 7, 'label' => 'CHOQUE DE VEHÍCULO UNICO', 'key' => 'VEHICULO_UNICO'],
        ];
    }

    private function templateMontosDanios(): array
    {
        return [
            ['no' => 1, 'label' => 'MONTO DAÑOS MATERIALES ($)', 'key' => 'materiales'],
            ['no' => 2, 'label' => 'MONTO VEHÍCULOS', 'key' => 'vehiculos'],
            ['no' => 3, 'label' => 'MONTO OTROS', 'key' => 'otros'],
        ];
    }

    private function templateClasificacionVehiculos(): array
    {
        return [
            ['no' => 1, 'label' => 'SERVICIO PÚBLICO FED', 'key' => 'SERVICIO_PUBLICO_FED'],
            ['no' => 2, 'label' => 'TRANSPORTE PÚBLICO', 'key' => 'TRANSPORTE_PUBLICO'],
            ['no' => 3, 'label' => 'AUTOMÓVIL', 'key' => 'AUTOMOVIL'],
            ['no' => 4, 'label' => 'CAMIONETA', 'key' => 'CAMIONETA'],
            ['no' => 5, 'label' => 'MICROBUS', 'key' => 'MICROBUS'],
            ['no' => 6, 'label' => 'CAMIÓN URBANO DE PASAJEROS', 'key' => 'CAMION_URBANO'],
            ['no' => 7, 'label' => 'OMNIBUS', 'key' => 'OMNIBUS'],
            ['no' => 8, 'label' => 'CAMIONETA DE CARGA', 'key' => 'CAMIONETA_CARGA'],
            ['no' => 9, 'label' => 'CAMION DE CARGA', 'key' => 'CAMION_CARGA'],
            ['no' => 10, 'label' => 'TRACTOR', 'key' => 'TRACTOR'],
            ['no' => 11, 'label' => 'FERROCARRIL', 'key' => 'FERROCARRIL'],
            ['no' => 12, 'label' => 'MOTOCICLETA', 'key' => 'MOTOCICLETA'],
            ['no' => 13, 'label' => 'BICICLETA', 'key' => 'BICICLETA'],
            ['no' => 14, 'label' => 'OTRO', 'key' => 'OTRO'],
            ['no' => 15, 'label' => 'SEMOVIENTE', 'key' => 'SEMOVIENTE'],
        ];
    }

    private function templateResumenVehiculosInvolucrados(): array
    {
        return [
            ['no' => 1, 'label' => 'VEHÍCULOS PARTICULARES INVOL.', 'key' => 'particulares'],
            ['no' => 2, 'label' => 'VEHÍCULOS SERV. PÚBLIC. INVOL.', 'key' => 'publicos'],
            ['no' => 3, 'label' => 'MOTOS INVOLUCRADAS', 'key' => 'motos'],
            ['no' => 4, 'label' => 'VEHÍCULOS OFICIALES INVOL', 'key' => 'oficiales'],
        ];
    }

    private function templateLiberacionesVehiculos(): array
    {
        return [
            ['no' => 1, 'label' => 'LIBERACIÓN MOTOCICLETAS', 'key' => 'motos'],
            ['no' => 2, 'label' => 'LIBERACIÓN VEHÍCULOS', 'key' => 'vehiculos'],
            ['no' => 3, 'label' => 'LIBERACIÓN CAMIONES', 'key' => 'camiones'],
            ['no' => 4, 'label' => 'LIBERACIÓN REMOLQUES', 'key' => 'remolques'],
        ];
    }

    private function claveChoqueDanios($tipoHecho, array $vehiculos): string
    {
        if ($this->claveTipoHechoTransito($tipoHecho) === 'COLISION_PEATON') {
            return 'VEHICULO_PEATON';
        }

        $camiones = 0;
        $motocicletas = 0;
        $vehiculosNormales = 0;

        foreach ($vehiculos as $vehiculo) {
            $tipo = $this->clasificarVehiculoChoque($vehiculo->tipo ?? '');

            if ($tipo === 'camion') {
                $camiones++;
            } elseif ($tipo === 'motocicleta') {
                $motocicletas++;
            } else {
                $vehiculosNormales++;
            }
        }

        $totalVehiculos = $camiones + $motocicletas + $vehiculosNormales;

        if ($totalVehiculos <= 1) {
            return 'VEHICULO_UNICO';
        }

        if ($camiones > 0 && $motocicletas > 0) {
            return 'CAMION_MOTO';
        }

        if ($camiones > 0 && $vehiculosNormales > 0) {
            return 'CAMION_VEHICULO';
        }

        if ($motocicletas >= 2 && $vehiculosNormales === 0 && $camiones === 0) {
            return 'MOTO_MOTO';
        }

        if ($vehiculosNormales >= 2 && $motocicletas === 0 && $camiones === 0) {
            return 'VEHICULO_VEHICULO';
        }

        if ($motocicletas > 0 && $vehiculosNormales > 0) {
            return 'MOTO_VEHICULO';
        }

        return 'VEHICULO_UNICO';
    }

    private function clasificarVehiculoChoque($tipo): string
    {
        $tipoNormalizado = $this->normalizar($tipo);

        if ($this->contiene($tipoNormalizado, ['BICICLETA'])) {
            return 'motocicleta';
        }

        $general = $this->tipoGeneralVehiculo($tipoNormalizado);

        if (in_array($general, ['camion', 'remolque'], true)) {
            return 'camion';
        }

        if ($general === 'motocicleta') {
            return 'motocicleta';
        }

        return 'vehiculo';
    }

    private function claveClasificacionVehiculo($tipo): string
    {
        $tipo = $this->normalizar($tipo);

        if ($this->tipoVehiculoNoEspecificado($tipo)) {
            return 'OTRO';
        }

        if ($this->contiene($tipo, ['SERVICIO PUBLICO FED', 'SERV. PUBLICO FED', 'SERV PUB FED', 'PUBLICO FEDERAL'])) {
            return 'SERVICIO_PUBLICO_FED';
        }

        if ($this->contiene($tipo, ['TRANSPORTE PUBLICO', 'SERVICIO PUBLICO', 'TAXI'])) {
            return 'TRANSPORTE_PUBLICO';
        }

        if ($this->esBicicletaTipo($tipo)) {
            return 'BICICLETA';
        }

        if ($this->esMotocicletaTipo($tipo)) {
            return 'MOTOCICLETA';
        }

        if ($tipo === 'VAGON' || $this->contiene($tipo, ['FERROCARRIL', 'TREN', 'LOCOMOTORA', 'VAGON DE TREN'])) {
            return 'FERROCARRIL';
        }

        if ($this->contiene($tipo, ['SEMOVIENTE', 'CABALLO', 'BURRO', 'VACA', 'MULA', 'ANIMAL'])) {
            return 'SEMOVIENTE';
        }

        if ($this->contiene($tipo, ['TRACTOR', 'TRACTO'])) {
            return 'TRACTOR';
        }

        if ($this->contiene($tipo, ['MICROBUS'])) {
            return 'MICROBUS';
        }

        if ($this->contiene($tipo, ['OMNIBUS'])) {
            return 'OMNIBUS';
        }

        if ($this->contiene($tipo, ['AUTOBUS', 'CAMION URBANO'])) {
            return 'CAMION_URBANO';
        }

        if ($this->contiene($tipo, ['CAMIONETA DE CARGA', 'PICK UP CARGA', 'PICKUP CARGA', 'ESTAQUITAS'])) {
            return 'CAMIONETA_CARGA';
        }

        if ($this->contiene($tipo, ['PICK', 'CAMIONETA', 'VAN', 'PANEL', 'VAGONETA', 'MINIVAN'])) {
            return 'CAMIONETA';
        }

        if ($this->contiene($tipo, ['CAJA', 'PLATAFORMA', 'VOLTEO', 'PIPA', 'CISTERNA', 'REDILAS', 'REFRIGERADO', 'GRUA', 'GONDOLA', 'TORTON', 'RABON', 'CAMION'])) {
            return 'CAMION_CARGA';
        }

        if ($this->contiene($tipo, ['SEDAN', 'SUV', 'HATCHBACK', 'HATCH', 'COUPE', 'CONVERTIBLE', 'DEPORTIVO', 'AUTOMOVIL', 'AUTO', 'COMPACTO'])) {
            return 'AUTOMOVIL';
        }

        return 'OTRO';
    }

    private function sumarResumenVehiculo(array &$resumen, string $tipo, string $servicio): void
    {
        if ($this->esMotocicletaTipo($tipo)) {
            $resumen['motos']++;
            return;
        }

        if ($this->contiene($servicio, ['OFICIAL']) || $this->contiene($tipo, ['PATRULLA', 'OFICIAL'])) {
            $resumen['oficiales']++;
            return;
        }

        if ($this->contiene($servicio, ['PUBLIC']) || $this->contiene($tipo, ['TRANSPORTE PUBLICO', 'SERVICIO PUBLICO', 'TAXI'])) {
            $resumen['publicos']++;
            return;
        }

        $resumen['particulares']++;
    }

    private function claveLiberacionVehiculo($tipo): string
    {
        $tipo = $this->normalizar($tipo);

        if ($this->esMotocicletaTipo($tipo) || $this->esBicicletaTipo($tipo)) {
            return 'motos';
        }

        if ($this->esRemolqueTipo($tipo)) {
            return 'remolques';
        }

        if ($this->esCamionTipo($tipo)) {
            return 'camiones';
        }

        return 'vehiculos';
    }

    private function tipoVehiculoNoEspecificado(string $tipo): bool
    {
        return in_array($this->normalizar($tipo), [
            '',
            'N/A',
            'NA',
            'NO APLICA',
            'NO ESPECIFICADO',
            'SIN DATO',
            'SIN DATOS',
            'SE DESCONOCE',
            'DESCONOCIDO',
            'NULL',
        ], true);
    }

    private function esMotocicletaTipo(string $tipo): bool
    {
        return $this->contiene($tipo, [
            'MOTO',
            'MOTOCIC',
            'SCOOTER',
            'MOTONETA',
            'ENDURO',
            'NAKED',
            'PISTA',
            'CHOPPER',
            'CUATRIMOTO',
            'DOBLE PROPOSITO',
            'CRUISER',
            'CRUISIER',
            'TRABAJO',
        ]);
    }

    private function esBicicletaTipo(string $tipo): bool
    {
        return $this->contiene($tipo, [
            'BICICLETA',
            'BICI',
            'BMX',
            'MONTANA',
            'RUTA',
            'URBANA',
            'PLEGABLE',
        ]);
    }

    private function esCamionTipo(string $tipo): bool
    {
        return $this->contiene($tipo, [
            'CAMION',
            'AUTOBUS',
            'MICROBUS',
            'OMNIBUS',
            'TRACTO',
            'TORTON',
            'RABON',
            'CAJA',
            'PLATAFORMA',
            'VOLTEO',
            'PIPA',
            'CISTERNA',
            'REDILAS',
            'REFRIGERADO',
            'GRUA',
            'GONDOLA',
        ]);
    }

    private function esRemolqueTipo(string $tipo): bool
    {
        return $this->contiene($tipo, [
            'REMOLQUE',
            'SEMIRREM',
            'SEMIRREMOLQUE',
            'DOLLY',
            'PORTACONTENEDOR',
            'CAMA BAJA',
        ]);
    }

    private function clavesControlVehicular($actividad, $vehiculo): array
    {
        $texto = $this->textoControlVehicular($actividad);
        $keys = [];

        if ((int) ($vehiculo->antecedente_vehiculo ?? 0) === 1 || $this->contiene($texto, ['REVISION DE ANTECEDENTES', 'REVISIÓN DE ANTECEDENTES', 'ANTECEDENTE'])) {
            $keys[] = 'REVISION_ANTECEDENTES';
        }

        if ($this->contiene($texto, ['PROCEDENCIA EXTRANJERA', 'EXTRANJERA'])) {
            $keys[] = 'PROC_EXTRANJERA';
        }

        if ($this->contiene($texto, ['DESPOLARIZADO'])) {
            $keys[] = 'DESPOLARIZADO';
        }

        if ($this->vehiculoEnCorralon($vehiculo) && $this->contiene($texto, ['FALTA ADMINISTRATIVA', 'FALTAS ADMINISTRATIVAS'])) {
            $keys[] = 'CORRALON_ADMIN';
        }

        if ($this->vehiculoEnCorralon($vehiculo) && !$this->contiene($texto, ['FALTA ADMINISTRATIVA', 'FALTAS ADMINISTRATIVAS'])) {
            $keys[] = 'CORRALON_TRANSITO';
        }

        if ($this->contiene($texto, ['MP POR HECHO DE TRANSITO', 'MP POR HECHO DE TRÁNSITO', 'PUESTOS A DISPOSICION DEL MP', 'PUESTOS A DISPOSICIÓN DEL MP'])) {
            $keys[] = 'MP_TRANSITO';
        }

        if ($this->contiene($texto, ['PRESENTADOS AL MP', 'PRESENTADO AL MP'])) {
            $keys[] = 'PRESENTADOS_MP';
        }

        if ($this->contiene($texto, ['ABANDONO', 'RESGUARDADOS POR ABANDONO'])) {
            $keys[] = 'ABANDONO';
        }

        if ($this->contiene($texto, ['HECHO DELICTIVO', 'HECHOS DELICTIVOS', 'DELICTIVO'])) {
            $keys[] = 'DELICTIVOS';
        }

        if ($this->contiene($texto, ['ALTERACIONES EN SUS MEDIOS DE IDENTIFICACION', 'ALTERACIONES EN SUS MEDIOS DE IDENTIFICACIÓN', 'ALTERACION', 'ALTERACIÓN'])) {
            $keys[] = 'ALTERACIONES_ID';
        }

        if ($this->contiene($texto, ['RECUPERADOS CON REPORTE DE ROBO', 'RECUPERADO CON REPORTE DE ROBO'])) {
            $keys[] = 'REC_ROBO';
        }

        if ($this->contiene($texto, ['CONOCIMIENTO DE REPORTE DE ROBO'])) {
            $keys[] = 'CONOCIMIENTO_ROBO';
        }

        if ($this->contiene($texto, ['ASEGURADOS POR OTROS MOTIVOS', 'ASEGURADO POR OTRO MOTIVO', 'OTROS MOTIVOS'])) {
            $keys[] = 'OTROS_MOTIVOS';
        }

        return array_values(array_unique($keys));
    }

    private function textoControlVehicular($actividad): string
    {
        return $this->normalizar(implode(' ', array_filter([
            optional($actividad->categoria)->nombre,
            optional($actividad->subcategoria)->nombre,
            $actividad->nombre ?? null,
            $actividad->motivo ?? null,
            $actividad->narrativa ?? null,
            $actividad->acciones_realizadas ?? null,
            $actividad->observaciones ?? null,
        ])));
    }

    private function textoActividad($actividad): string
    {
        return $this->normalizar(implode(' ', array_filter([
            optional($actividad->categoria ?? null)->nombre,
            optional($actividad->subcategoria ?? null)->nombre,
            $actividad->nombre ?? null,
            $actividad->lugar ?? null,
            $actividad->tramo ?? null,
            $actividad->motivo ?? null,
            $actividad->narrativa ?? null,
            $actividad->acciones_realizadas ?? null,
            $actividad->observaciones ?? null,
        ])));
    }

    private function textoDispositivo($dispositivo): string
    {
        $detalles = '';

        if (!empty($dispositivo->detalles)) {
            $detalles = collect($dispositivo->detalles)->map(function ($detalle) {
                return trim(implode(' ', array_filter([
                    $detalle->tipo ?? null,
                    $detalle->titulo ?? null,
                    $detalle->contenido ?? null,
                    $detalle->ubicacion ?? null,
                ])));
            })->implode(' ');
        }

        return $this->normalizar(implode(' ', array_filter([
            optional($dispositivo->catalogo ?? null)->nombre,
            $dispositivo->asunto ?? null,
            $dispositivo->lugar ?? null,
            $dispositivo->evento ?? null,
            $dispositivo->objetivo ?? null,
            $dispositivo->descripcion ?? null,
            $dispositivo->narrativa ?? null,
            $dispositivo->acciones_realizadas ?? null,
            $dispositivo->observaciones ?? null,
            $detalles,
        ])));
    }

    private function sumarPersonasAseguradas(array &$counts, string $texto, int $cantidad): void
    {
        if ($cantidad <= 0) {
            return;
        }

        $key = $this->clavePersonaAsegurada($texto);
        $counts['personas'][$key] += $cantidad;
    }

    private function clavePersonaAsegurada(string $texto): string
    {
        if ($this->contiene($texto, ['ANTECEDENTES PENALES', 'CONSULTA DE ANTECEDENTES'])) {
            return 'ANTECEDENTES_PENALES';
        }

        if ($this->contiene($texto, ['BARANDILLA'])) {
            return 'BARANDILLA';
        }

        if ($this->contiene($texto, ['ALCOHOL', 'ALCOHOLEMIA', 'ALIENTO ETILICO', 'ALIENTO ETÍLICO'])) {
            return 'ALCOHOLEMIA';
        }

        if ($this->contiene($texto, ['HOMICIDIO CULPOSO'])) {
            return 'HOMICIDIO_CULPOSO';
        }

        if ($this->contiene($texto, ['HOMICIDIO DOLOSO'])) {
            return 'HOMICIDIO_DOLOSO';
        }

        if ($this->contiene($texto, ['ROBO'])
            && $this->contiene($texto, ['VEHICULO', 'VEHÍCULO', 'MOTO', 'CAMION', 'CAMIÓN'])) {
            return 'MP_VEHICULOS_ROBADOS';
        }

        if ($this->contiene($texto, ['ARMA', 'PISTOLA', 'REVOLVER', 'CUCHILLO', 'NAVAJA'])) {
            return 'MP_PORTACION_ARMAS';
        }

        if ($this->contiene($texto, ['DROGA', 'MARIHUANA', 'CRISTAL', 'COCAINA', 'COCAÍNA', 'NARCOTICO', 'NARCÓTICO'])) {
            return 'MP_DROGA';
        }

        if ($this->contiene($texto, ['ROBO'])) {
            return 'ROBOS_DIVERSOS';
        }

        if ($this->contiene($texto, ['LESION', 'LESIÓN'])) {
            return 'LESIONES';
        }

        if ($this->contiene($texto, ['PRESENTADO AL MP', 'PRESENTADOS AL MP', 'PRESENTADA AL MP', 'PRESENTADAS AL MP', 'MINISTERIO PUBLICO', 'MINISTERIO PÚBLICO'])) {
            return 'PRESENTADAS_MP';
        }

        return 'OTROS_DELITOS';
    }

    private function sumarObjetosAsegurados(array &$counts, string $texto): void
    {
        $armas = [
            'CORTAS' => ['ARMAS CORTAS', 'ARMA CORTA', 'CORTAS', 'PISTOLA', 'PISTOLAS', 'REVOLVER', 'REVOLVERES'],
            'LARGAS' => ['ARMAS LARGAS', 'ARMA LARGA', 'LARGAS', 'RIFLE', 'RIFLES', 'ESCOPETA', 'ESCOPETAS'],
            'CARGADORES' => ['CARGADOR', 'CARGADORES'],
            'CARTUCHOS' => ['CARTUCHO', 'CARTUCHOS', 'MUNICION', 'MUNICIÓN', 'MUNICIONES'],
            'LANZA_GRANADAS' => ['LANZA GRANADAS', 'LANZAGRANADAS', 'LANZA GRANADA', 'LANZAGRANADA'],
            'GRANADAS' => ['GRANADA', 'GRANADAS'],
            'PUNZOCORTANTE' => ['PUNZOCORTANTE', 'PUNZO CORTANTE', 'ARMA BLANCA', 'CUCHILLO', 'CUCHILLOS', 'NAVAJA', 'NAVAJAS'],
        ];

        $sumoArma = false;

        foreach ($armas as $key => $palabras) {
            if ($key === 'GRANADAS' && $this->contiene($texto, ['LANZA GRANADA', 'LANZAGRANADA'])) {
                continue;
            }

            $cantidad = $this->cantidadObjetoAsegurado($texto, $palabras);

            if ($cantidad > 0) {
                $counts['armas'][$key] += $cantidad;
                $sumoArma = true;
            }
        }

        if (!$sumoArma) {
            $cantidad = $this->cantidadObjetoAsegurado($texto, ['ARMAS ASEGURADAS', 'ARMA ASEGURADA', 'ARMAMENTO', 'ARMA', 'ARMAS']);

            if ($cantidad > 0) {
                $counts['armas']['ARMAS'] += $cantidad;
            }
        }

        $drogas = [
            'PLANTAS_MARIHUANA' => ['PLANTAS DE MARIHUANA', 'PLANTA DE MARIHUANA'],
            'MARIHUANA_GRS' => ['MARIHUANA', 'CANNABIS'],
            'CRISTAL_GRS' => ['CRISTAL'],
            'COCAINA_GRS' => ['COCAINA', 'COCAÍNA'],
            'PASTILLAS' => ['PASTILLA', 'PASTILLAS'],
            'PLANTIOS' => ['PLANTIO', 'PLANTÍO', 'PLANTIOS', 'PLANTÍOS'],
            'OTRAS_DROGAS' => ['OTRAS DROGAS', 'FENTANILO', 'HEROINA', 'HEROÍNA', 'METANFETAMINA'],
        ];

        $sumoDroga = false;

        foreach ($drogas as $key => $palabras) {
            if ($key === 'MARIHUANA_GRS' && $this->contiene($texto, ['PLANTA DE MARIHUANA', 'PLANTAS DE MARIHUANA'])) {
                continue;
            }

            $cantidad = $this->cantidadObjetoAsegurado($texto, $palabras);

            if ($cantidad > 0) {
                $counts['drogas'][$key] += $cantidad;
                $sumoDroga = true;
            }
        }

        if (!$sumoDroga) {
            $cantidad = $this->cantidadObjetoAsegurado($texto, ['DROGA ASEGURADA', 'DROGAS ASEGURADAS', 'NARCOTICO', 'NARCÓTICO', 'DROGA', 'DROGAS']);

            if ($cantidad > 0) {
                $counts['drogas']['DROGA'] += $cantidad;
            }
        }
    }

    private function sumarOtrosAseguramientos(array &$counts, string $texto): void
    {
        $otros = [
            'AGUACATE' => ['AGUACATE', 'AGUACATES'],
            'MADERA' => ['MADERA', 'MADERAS', 'TABLA', 'TABLAS', 'ROLLIZO', 'ROLLIZOS'],
            'DINERO' => ['DINERO', 'EFECTIVO', 'PESOS', 'PESO', '$'],
        ];

        $sumoOtro = false;

        foreach ($otros as $key => $palabras) {
            $cantidad = $this->cantidadObjetoAsegurado($texto, $palabras);

            if ($cantidad > 0) {
                $counts['otros'][$key] += $cantidad;
                $sumoOtro = true;
            }
        }

        if ($sumoOtro || $this->esObjetoArmaDroga($texto)) {
            return;
        }

        $cantidad = $this->cantidadObjetoAsegurado($texto, [
            'OTROS ASEGURAMIENTOS',
            'OBJETO ASEGURADO',
            'OBJETOS ASEGURADOS',
            'MERCANCIA ASEGURADA',
            'MERCANCÍA ASEGURADA',
            'BIEN ASEGURADO',
            'BIENES ASEGURADOS',
        ]);

        if ($cantidad > 0) {
            $counts['otros']['OTROS'] += $cantidad;
        }
    }

    private function esObjetoArmaDroga(string $texto): bool
    {
        return $this->contiene($texto, [
            'ARMA',
            'CORTA',
            'LARGA',
            'CARGADOR',
            'CARTUCHO',
            'GRANADA',
            'LANZA',
            'PUNZO',
            'CUCHILLO',
            'NAVAJA',
            'DROGA',
            'MARIHUANA',
            'CANNABIS',
            'CRISTAL',
            'COCAINA',
            'COCAÍNA',
            'PASTILLA',
            'PLANTIO',
            'PLANTÍO',
            'NARCOTICO',
            'NARCÓTICO',
        ]);
    }

    private function contarPersonasAseguradasTexto(string $texto): int
    {
        $texto = $this->normalizar($texto);

        if ($texto === '') {
            return 0;
        }

        $palabrasPersona = 'PERSONAS?|DETENIDOS?|DETENIDAS?|PRESENTADOS?|PRESENTADAS?|REMITIDOS?|REMITIDAS?|MASCULINOS?|FEMENINAS?';
        $total = 0;

        foreach ([
            '/(\d+)\s+(?:' . $palabrasPersona . ')/u',
            '/(?:' . $palabrasPersona . ')\D{0,20}(\d+)/u',
        ] as $pattern) {
            preg_match_all($pattern, $texto, $matches);
            $total += array_sum(array_map('intval', $matches[1] ?? []));
        }

        if ($total > 0) {
            return $total;
        }

        return $this->contiene($texto, [
            'PERSONA DETENIDA',
            'PERSONA PRESENTADA',
            'DETENIDO',
            'DETENIDA',
            'PRESENTADO AL MP',
            'PRESENTADA AL MP',
            'REMITIDO',
            'REMITIDA',
            'BARANDILLA',
            'ANTECEDENTES PENALES',
        ]) ? 1 : 0;
    }

    private function cantidadObjetoAsegurado(string $texto, array $palabras): float
    {
        $texto = $this->normalizar($texto);
        $encontrado = false;

        foreach ($palabras as $palabra) {
            $palabra = $this->normalizar($palabra);

            if ($palabra === '' || !str_contains($texto, $palabra)) {
                continue;
            }

            $encontrado = true;
            $quoted = preg_quote($palabra, '/');
            $cantidadAntes = 0.0;

            preg_match_all(
                '/(\d+(?:[\.,]\d+)?)\s*(?:GRS?|GRAMOS?|PIEZAS?|PZS?|UNIDADES?)?\D{0,25}' . $quoted . '/u',
                $texto,
                $matches
            );

            foreach (($matches[1] ?? []) as $match) {
                $cantidadAntes += (float) str_replace(',', '.', $match);
            }

            if ($cantidadAntes > 0) {
                return $this->numeroVisible($cantidadAntes);
            }

            $cantidadDespues = 0.0;

            preg_match_all(
                '/' . $quoted . '\D{0,25}(\d+(?:[\.,]\d+)?)/u',
                $texto,
                $matches
            );

            foreach (($matches[1] ?? []) as $match) {
                $cantidadDespues += (float) str_replace(',', '.', $match);
            }

            if ($cantidadDespues > 0) {
                return $this->numeroVisible($cantidadDespues);
            }
        }

        return $encontrado ? 1.0 : 0.0;
    }

    private function bucketControlVehicular($tipo): string
    {
        $general = $this->tipoGeneralVehiculo($tipo);

        if (in_array($general, ['automovil', 'camioneta'], true)) {
            return 'vehiculos';
        }

        if ($general === 'motocicleta') {
            return 'motocicletas';
        }

        if (in_array($general, ['camion', 'remolque'], true)) {
            return 'camiones';
        }

        return 'otros';
    }

    private function tipoGeneralVehiculo($tipo): string
    {
        $tipo = $this->normalizar($tipo);

        if ($tipo === '') {
            return 'otros';
        }

        if ($this->contiene($tipo, ['MOTO', 'SCOOTER', 'MOTONETA', 'ENDURO', 'NAKED', 'PISTA', 'DOBLE PROPOSITO', 'CRUISER', 'CHOPPER', 'CUATRIMOTO'])) {
            return 'motocicleta';
        }

        if ($this->contiene($tipo, ['PICK', 'CAMIONETA', 'SUV', 'VAN', 'MINIVAN', 'PANEL', 'URVAN', 'FURGON', 'VAGONETA'])) {
            return 'camioneta';
        }

        if ($this->contiene($tipo, ['CAMION', 'TRACTO', 'TRAILER', 'VOLTEO', 'PIPA', 'TORTON', 'RABON'])) {
            return 'camion';
        }

        if ($this->contiene($tipo, ['REMOLQUE', 'SEMIRREM', 'SEMIRREMOLQUE', 'PLATAFORMA', 'DOLLY'])) {
            return 'remolque';
        }

        if ($this->contiene($tipo, ['AUTO', 'SEDAN', 'HATCH', 'COUPE', 'CONVERTIBLE', 'VOCHO', 'TSURU'])) {
            return 'automovil';
        }

        return 'otros';
    }

    private function vehiculoEnCorralon($vehiculo): bool
    {
        $corralon = trim((string) ($vehiculo->corralon ?? ''));

        if ($corralon === '') {
            return false;
        }

        return !in_array($this->normalizar($corralon), [
            'N/A',
            'NA',
            'NO',
            'NO APLICA',
            'NO APLICA.',
            'NINGUNO',
            'NULL',
            'SIN CORRALON',
            'SIN CORRALÓN',
            'NO TIENE CORRALON',
            'NO TIENE CORRALÓN',
            '-',
        ], true);
    }

    private function unidadVialidadesUrbanasIds(): array
    {
        $ids = Unidad::query()
            ->where('id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->orWhere(function ($query) {
                $query->where('nombre', 'like', '%VIALIDADES%')
                    ->where('nombre', 'like', '%URBANAS%');
            })
            ->orWhere('slug', 'like', '%vialidades%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $ids ?: [self::UNIDAD_VIALIDADES_URBANAS_ID];
    }

    private function buscarFilaActividad(array $rows, $actividad): ?int
    {
        $categoria = $this->normalizar(optional($actividad->categoria)->nombre ?? '');
        $subcategoria = $this->normalizar(optional($actividad->subcategoria)->nombre ?? $actividad->nombre ?? '');

        foreach ($rows as $index => $row) {
            if ($this->normalizar($row['categoria_real']) === $categoria
                && $this->normalizar($row['actividad']) === $subcategoria) {
                return $index;
            }
        }

        return $this->buscarFilaOtros($rows, $categoria);
    }

    private function buscarFilaDispositivo(array $rows, $dispositivo): ?int
    {
        $texto = $this->textoDispositivo($dispositivo);

        foreach ($rows as $index => $row) {
            if ($this->normalizar($row['categoria_real']) !== 'OPERATIVOS') {
                continue;
            }

            $actividad = $this->normalizar($row['actividad']);

            if ($actividad !== '' && str_contains($texto, $actividad)) {
                return $index;
            }
        }

        return $this->buscarFilaOtros($rows, 'OPERATIVOS');
    }

    private function buscarFilaOtros(array $rows, string $categoria): ?int
    {
        foreach ($rows as $index => $row) {
            if ($this->normalizar($row['categoria_real']) === $categoria
                && str_starts_with($this->normalizar($row['actividad']), 'OTRO')) {
                return $index;
            }
        }

        return null;
    }

    private function mergeBloques(Worksheet $sheet, array $rows): void
    {
        $start = null;
        $lastCategoria = null;

        foreach ($rows as $row) {
            $categoria = $row['categoria_real'];

            if ($categoria !== $lastCategoria) {
                if ($start !== null) {
                    $this->mergeBloque($sheet, $start, ((int) $row['excel_row']) - 1);
                }

                $start = (int) $row['excel_row'];
                $lastCategoria = $categoria;
            }
        }

        if ($start !== null && !empty($rows)) {
            $this->mergeBloque($sheet, $start, (int) end($rows)['excel_row']);
        }
    }

    private function mergeBloque(Worksheet $sheet, int $start, int $end): void
    {
        if ($end <= $start) {
            return;
        }

        $sheet->mergeCells("A{$start}:A{$end}");
        $sheet->mergeCells("B{$start}:B{$end}");
        $sheet->getStyle("A{$start}:B{$end}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$start}:B{$start}")->getFont()->setBold(true);
    }

    private function cantidadActividad($actividad): int
    {
        $cantidad = (int) ($actividad->cantidad ?? 0);

        return $cantidad > 0 ? $cantidad : 1;
    }

    private function personasAlcanzadas($actividad): int
    {
        $detalle = $actividad->fomentoCulturaVialDetalle ?? null;
        $detalleTotal = (int) ($detalle->total_poblacion_atendida ?? 0);

        return $detalleTotal > 0
            ? $detalleTotal
            : (int) ($actividad->personas_alcanzadas ?? 0);
    }

    private function valorVisible($value)
    {
        if ($value === null || $value === '' || (float) $value == 0.0) {
            return null;
        }

        return $value;
    }

    private function contarUnidadesTexto($texto): int
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return 0;
        }

        if (preg_match('/^\d+$/', $texto)) {
            $numero = (int) $texto;

            if ($numero === 0) {
                return 0;
            }

            return $numero <= 100 ? $numero : 1;
        }

        $partes = array_filter(array_map('trim', preg_split('/[\n,;|]+/', $texto) ?: []));

        if (count($partes) > 1) {
            return count($partes);
        }

        return 1;
    }

    private function contarCantidadTexto($texto): int
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return 0;
        }

        preg_match_all('/\d+/', $texto, $matches);

        if (!empty($matches[0])) {
            return array_sum(array_map('intval', $matches[0]));
        }

        return count(array_filter(preg_split('/[\s,;|]+/', $texto) ?: []));
    }

    private function contarRecomendaciones($registro): int
    {
        $texto = $this->normalizar(implode(' ', array_filter([
            $registro->motivo ?? null,
            $registro->narrativa ?? null,
            $registro->acciones_realizadas ?? null,
            $registro->observaciones ?? null,
        ])));

        if ($texto === '') {
            return 0;
        }

        preg_match_all('/RECOMENDACION(?:ES)?\D{0,20}(\d+)/u', $texto, $matches);

        if (!empty($matches[1])) {
            return array_sum(array_map('intval', $matches[1]));
        }

        return 0;
    }

    private function numeroVisible($numero)
    {
        $numero = (float) $numero;

        if (floor($numero) === $numero) {
            return (int) $numero;
        }

        return round($numero, 2);
    }

    private function contiene(string $texto, array $palabras): bool
    {
        foreach ($palabras as $palabra) {
            if (str_contains($texto, $this->normalizar($palabra))) {
                return true;
            }
        }

        return false;
    }

    private function normalizar($texto): string
    {
        $texto = mb_strtoupper((string) $texto, 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/', ' ', trim($texto));
    }

    private function templateCompleto(): array
    {
        return [
            ['no' => 1, 'categoria' => 'INSTITUCIONES', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS PÚBLICOS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS DEPORTIVOS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS CULTURALES', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYO A EVENTOS RELIGIOSOS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'ESCUELAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'DILIGENCIAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'INSTITUCIONES', 'actividad' => 'OTROS TIPOS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 2, 'categoria' => 'REPORTES C5i', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'OBSTRUCCIÓN DE COCHERAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'OTROS TIPOS DE OBSTRUCCIÓN', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'ACTOS DELICTIVOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'SINIESTROS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'HECHOS DE TRÁNSITO', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'CONSENTRACION PERSONAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'REPORTES C5i', 'actividad' => 'OTROS REPORTES (Especificar en las novedades relevantes)', 'band' => true],

            ['no' => 3, 'categoria' => 'ABANDERAMIENTOS', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'CORTES DE CIRCULACIÓN', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'ACCIDENTES', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'MARCHAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'MÍTINES', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'OBRAS PÚBLICAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'ACOMPAÑAMIENTO A CARAVANAS U OTROS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'ABANDERAMIENTOS', 'actividad' => 'OTROS ABANDERAMIENTOS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 4, 'categoria' => 'OPERATIVOS', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'ESCUELA SEGURA', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'CONEXIÓN INSTITUCIONAL', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'RESPUESTA VIAL INMEDIATA', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'ABANDERAMIENTO ACTIVO', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'PASO CONTINUO', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => 'OTROS OPERATIVOS (Especificar en las novedades relevantes)', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'OPERATIVOS', 'actividad' => '', 'band' => true],

            ['no' => 5, 'categoria' => 'PROGRAMAS', 'categoria_real' => 'PROGRAMAS', 'actividad' => 'CONDUCE SIN ALCOHOL (ALCOHOLÍMETRO)', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROGRAMAS', 'actividad' => 'OTROS PROGRAMAS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 6, 'categoria' => 'MONITOREOS', 'categoria_real' => 'MONITOREOS', 'actividad' => 'VÍAS FÉRREAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'PERIFÉRICOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'AVENIDAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'TIENDAS DEPARTAMENTALES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'BANCOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'GASOLINERAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'OFICINAS GUBERNAMENTALES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'MANIFESTACIONES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'MONITOREOS', 'actividad' => 'OTROS MONITOREOS (Especificar en las novedades relevantes)', 'band' => true],

            ['no' => 7, 'categoria' => 'AUXILIO VIAL A CONDUCTORES', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'FALLAS MECÁNICAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'PEATÓN', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'ESCOLTA EN SITUACIONES DE EMERGENCIA', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'AGRICOLAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'AUXILIO VIAL A CONDUCTORES', 'actividad' => 'OTROS AUXILIOS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 8, 'categoria' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'APOYO A LA VIALIDAD', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'PASO LIBRE DE FUNCIONARIOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'ZONAS DE MAYOR PASE DE TRANSEÚNTES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'PASOS PEATONALES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'MEDIDAS DE PROTECCIÓN', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'PATRULLAJES', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'SERVICIOS DE ESCOLTAS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'DISPOSITIVOS DE SEGURIDAD VIAL', 'actividad' => 'OTROS (Especificar en las novedades relevantes)', 'band' => true],

            ['no' => 9, 'categoria' => 'CAPACITACIONES', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'TALLER EDUCACIÓN SEGURIDAD VIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'MÓDULOS EDUCACIÓN SEGURIDAD VIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'SSP', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'CALEA', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAPACITACIONES', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => false],

            ['no' => 10, 'categoria' => 'CAMPAÑAS', 'categoria_real' => 'CAMPAÑAS', 'actividad' => 'CONCIENTIZACIÓN Y PREVENCIÓN', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAMPAÑAS', 'actividad' => 'REPARTICIÓN DE TRÍPTICOS', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAMPAÑAS', 'actividad' => 'ESTACIONALES (SEMANA SANTA, NAVIDAD ETC.)', 'band' => true],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'CAMPAÑAS', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => true],

            ['no' => 11, 'categoria' => 'PROXIMIDAD SOCIAL', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'PREVENCIÓN SOCIAL', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'RECORRIDOS DE PROXIMIDAD', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'APOYO A TURISTAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'APOYO A PERSONAS DE LA TERCERA EDAD', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'APOYO A PERSONAS PERDIDAS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'RECUPERACIÓN DE ESPACIOS', 'band' => false],
            ['no' => '', 'categoria' => '', 'categoria_real' => 'PROXIMIDAD SOCIAL', 'actividad' => 'OTRAS (Especificar en las novedades relevantes)', 'band' => false],
        ];
    }
}
