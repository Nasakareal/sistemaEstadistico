<?php

namespace App\Services\Exports\Sheets;

use App\Models\Patrulla;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EstadoFuerzaVehicularSheet
{
    public function build(Spreadsheet $spreadsheet, Carbon $corte): Worksheet
    {
        $sheet = new Worksheet($spreadsheet, 'EST. VEH');
        $spreadsheet->addSheet($sheet);

        $navy = '0B2A5B';
        $lightBlue = 'CFE2F3';

        $styleTitle = [
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
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

        $styleAreaCell = $styleBody;
        $styleAreaCell['font']['bold'] = true;

        $styleTotalCell = $styleBody;
        $styleTotalCell['font']['bold'] = true;

        $patrullas = Patrulla::with('unidad')->get();

        $vehiculos = [];
        foreach ($patrullas as $p) {
            $vehiculos[$this->vehiculoLabel($p)] = true;
        }
        $vehiculos = array_keys($vehiculos);
        sort($vehiculos);

        $startRow = 2;

        $r1 = $this->renderSection(
            sheet: $sheet,
            topRow: $startRow,
            corte: $corte,
            titulo: 'ESTADO DE FUERZA VEHICULAR ACTIVO',
            totalLabel: 'TOTAL ACTIVAS',
            vehiculos: $vehiculos,
            rows: $this->buildRows($patrullas, $vehiculos, fn($p) => (int)($p->activa ?? 0) === 1),
            styleFechaBar: $styleFechaBar,
            styleTitle: $styleTitle,
            styleHeader: $styleHeader,
            styleBody: $styleBody,
            styleAreaCell: $styleAreaCell,
            styleTotalCell: $styleTotalCell
        );

        $r2 = $this->renderSection(
            sheet: $sheet,
            topRow: $r1 + 2,
            corte: $corte,
            titulo: 'ESTADO DE FUERZA VEHICULAR INACTIVO',
            totalLabel: 'TOTAL DE INACTIVAS',
            vehiculos: $vehiculos,
            rows: $this->buildRows($patrullas, $vehiculos, fn($p) => (int)($p->activa ?? 0) === 0),
            styleFechaBar: $styleFechaBar,
            styleTitle: $styleTitle,
            styleHeader: $styleHeader,
            styleBody: $styleBody,
            styleAreaCell: $styleAreaCell,
            styleTotalCell: $styleTotalCell
        );

        $this->renderSection(
            sheet: $sheet,
            topRow: $r2 + 2,
            corte: $corte,
            titulo: 'TOTAL DE ESTADO DE FUERZA VEHICULAR',
            totalLabel: 'TOTAL',
            vehiculos: $vehiculos,
            rows: $this->buildRows($patrullas, $vehiculos, fn($p) => true),
            styleFechaBar: $styleFechaBar,
            styleTitle: $styleTitle,
            styleHeader: $styleHeader,
            styleBody: $styleBody,
            styleAreaCell: $styleAreaCell,
            styleTotalCell: $styleTotalCell
        );

        $sheet->getColumnDimension('B')->setWidth(20);
        $col = 3; // C
        foreach ($vehiculos as $_) {
            $sheet->getColumnDimension($this->colLetter($col))->setWidth(14);
            $col++;
        }
        $sheet->getColumnDimension($this->colLetter($col))->setWidth(18);

        return $sheet;
    }

    protected function buildRows(array $patrullas, array $vehiculos, callable $filter): array
    {
        $rows = [];

        foreach ($patrullas as $p) {
            if (!$filter($p)) continue;

            $area = 'SIN_AREA';
            if ($p->unidad) {
                $area = (string)($p->unidad->nombre ?? $p->unidad->name ?? 'SIN_AREA');
            }

            $veh = $this->vehiculoLabel($p);

            if (!isset($rows[$area])) {
                $rows[$area] = array_fill_keys($vehiculos, 0);
            }

            if (!array_key_exists($veh, $rows[$area])) {
                $rows[$area][$veh] = 0;
            }

            $rows[$area][$veh] += 1;
        }

        ksort($rows);

        return $rows;
    }

    protected function renderSection(
        Worksheet $sheet,
        int $topRow,
        Carbon $corte,
        string $titulo,
        string $totalLabel,
        array $vehiculos,
        array $rows,
        array $styleFechaBar,
        array $styleTitle,
        array $styleHeader,
        array $styleBody,
        array $styleAreaCell,
        array $styleTotalCell
    ): int {
        $fechaLabelCell = 'B' . $topRow;
        $fechaValueCell = 'C' . $topRow;

        $firstVehColIndex = 3; // C
        $totalColIndex = $firstVehColIndex + count($vehiculos); // despues de vehiculos
        $totalColLetter = $this->colLetter($totalColIndex);

        $titleStart = 'D' . $topRow;
        $titleStartIndex = 4; // D
        $titleEndIndex = max($titleStartIndex, $totalColIndex);
        $titleEnd = $this->colLetter($titleEndIndex) . $topRow;

        $sheet->setCellValue($fechaLabelCell, 'FECHA');
        $sheet->setCellValue($fechaValueCell, $corte->format('d/m/Y'));

        $sheet->mergeCells("{$titleStart}:{$titleEnd}");
        $sheet->setCellValue($titleStart, $titulo);

        $sheet->getStyle("{$fechaLabelCell}:{$fechaValueCell}")->applyFromArray($styleFechaBar);
        $sheet->getStyle("{$titleStart}:{$titleEnd}")->applyFromArray($styleTitle);

        $sheet->getRowDimension($topRow)->setRowHeight(26);

        $headerRow = $topRow + 1;
        $dataRowStart = $topRow + 2;

        $sheet->setCellValue('B' . $headerRow, "ÁREA Y/O\nREGIÓN");

        $colIndex = $firstVehColIndex;
        foreach ($vehiculos as $v) {
            $sheet->setCellValue($this->colLetter($colIndex) . $headerRow, $v);
            $colIndex++;
        }
        $sheet->setCellValue($totalColLetter . $headerRow, $totalLabel);

        $sheet->getStyle('B' . $headerRow . ':' . $totalColLetter . $headerRow)->applyFromArray($styleHeader);
        $sheet->getRowDimension($headerRow)->setRowHeight(44);

        $r = $dataRowStart;

        if (empty($rows)) {
            $sheet->setCellValue('B' . $r, 'SIN DATOS');
            $sheet->getStyle('B' . $r)->applyFromArray($styleAreaCell);
            $sheet->getStyle('C' . $r . ':' . $totalColLetter . $r)->applyFromArray($styleBody);
            $sheet->setCellValue($totalColLetter . $r, 0);
            $sheet->getStyle($totalColLetter . $r)->applyFromArray($styleTotalCell);
            $sheet->getRowDimension($r)->setRowHeight(24);
            return $r;
        }

        foreach ($rows as $area => $conteos) {
            $sheet->setCellValue('B' . $r, $area);
            $sheet->getStyle('B' . $r)->applyFromArray($styleAreaCell);

            $colIndex = $firstVehColIndex;
            $total = 0;

            foreach ($vehiculos as $v) {
                $val = (int)($conteos[$v] ?? 0);
                $sheet->setCellValue($this->colLetter($colIndex) . $r, $val);
                $total += $val;
                $colIndex++;
            }

            $sheet->setCellValue($totalColLetter . $r, $total);

            $sheet->getStyle('C' . $r . ':' . $this->colLetter($totalColIndex - 1) . $r)->applyFromArray($styleBody);
            $sheet->getStyle($totalColLetter . $r)->applyFromArray($styleTotalCell);
            $sheet->getRowDimension($r)->setRowHeight(24);

            $r++;
        }

        return $r - 1;
    }

    protected function vehiculoLabel($p): string
    {
        $tipo = trim((string)($p->tipo ?? ''));
        if ($tipo !== '') return mb_strtoupper($tipo);

        $marca = trim((string)($p->marca ?? ''));
        $linea = trim((string)($p->linea ?? ''));
        $modelo = trim((string)($p->modelo ?? ''));

        $mix = trim(($marca ? $marca . ' ' : '') . ($linea ? $linea . ' ' : '') . $modelo);
        if ($mix !== '') return mb_strtoupper($mix);

        $num = trim((string)($p->numero_economico ?? ''));
        if ($num !== '') return mb_strtoupper($num);

        return 'SIN_TIPO';
    }

    protected function colLetter(int $index): string
    {
        $s = '';
        while ($index > 0) {
            $m = ($index - 1) % 26;
            $s = chr(65 + $m) . $s;
            $index = intdiv($index - 1, 26);
        }
        return $s;
    }
}
