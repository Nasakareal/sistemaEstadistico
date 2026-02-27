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

        $vehiculos = $this->vehiculosOficiales();

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
        $col = 3;
        foreach ($vehiculos as $_) {
            $sheet->getColumnDimension($this->colLetter($col))->setWidth(14);
            $col++;
        }
        $sheet->getColumnDimension($this->colLetter($col))->setWidth(18);

        return $sheet;
    }

    protected function vehiculosOficiales(): array
    {
        return [
            'CHARGER',
            'FORD 150',
            'CAMIONETA F250',
            'RAM DODGE 1500',
            'JEEP PATRIOT',
            'ECO SPORT',
            'TSURU',
            'PLATINA',
            'KAWASAKY KLR650',
            'KAWASAKY ER-6N',
        ];
    }

    protected function buildRows(iterable $patrullas, array $vehiculos, callable $filter): array
    {
        $rows = [];

        foreach ($patrullas as $p) {
            if (!$filter($p)) continue;

            $area = 'SIN_AREA';
            if ($p->unidad) {
                $area = (string)($p->unidad->nombre ?? $p->unidad->name ?? 'SIN_AREA');
            }
            $area = $this->norm($area);

            $veh = $this->vehiculoOficial($p);
            if ($veh === null) {
                continue;
            }

            if (!isset($rows[$area])) {
                $rows[$area] = array_fill_keys($vehiculos, 0);
            }

            $rows[$area][$veh] = (int)($rows[$area][$veh] ?? 0) + 1;
        }

        ksort($rows);

        return $rows;
    }

    protected function vehiculoOficial($p): ?string
    {
        $marca = $this->norm($p->marca ?? '');
        $linea = $this->norm($p->linea ?? '');
        $modelo = $this->norm($p->modelo ?? '');
        $tipo  = $this->norm($p->tipo ?? '');

        if ($marca === 'DODGE' && $linea === 'CHARGER') {
            return 'CHARGER';
        }

        if ($marca === 'DODGE' && $linea === 'RAM') {
            return 'RAM DODGE 1500';
        }

        if ($marca === 'JEEP' && $linea === 'PATRIOT') {
            return 'JEEP PATRIOT';
        }

        if ($marca === 'FORD') {
            if ($linea === 'F150' || $linea === '150' || $linea === 'F-150' || $linea === 'F 150') {
                return 'FORD 150';
            }
            if ($linea === 'F250' || $linea === '250' || $linea === 'F-250' || $linea === 'F 250') {
                return 'CAMIONETA F250';
            }
            if ($linea === 'ECOSPORT' || $linea === 'ECO SPORT') {
                return 'ECO SPORT';
            }
        }

        if ($marca === 'NISSAN') {
            if ($linea === 'TSURU') return 'TSURU';
            if ($linea === 'PLATINA') return 'PLATINA';
        }

        if ($marca === 'KAWASAKI' || $marca === 'KAWASASKI') {
            if ($linea === 'KLR650' || $linea === 'KLR 650') return 'KAWASAKY KLR650';
            if ($linea === 'ER-6N' || $linea === 'ER6N' || $linea === 'ER 6N') return 'KAWASAKY ER-6N';
        }

        if ($tipo === 'MOTO') {
            if ($linea === 'KLR650' || $linea === 'KLR 650') return 'KAWASAKY KLR650';
            if ($linea === 'ER-6N' || $linea === 'ER6N' || $linea === 'ER 6N') return 'KAWASAKY ER-6N';
        }

        return null;
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

        $firstVehColIndex = 3;
        $totalColIndex = $firstVehColIndex + count($vehiculos);
        $totalColLetter = $this->colLetter($totalColIndex);

        $titleStart = 'D' . $topRow;
        $titleStartIndex = 4;
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

    protected function norm($s): string
    {
        $s = trim((string)$s);
        if ($s === '') return '';
        $s = mb_strtoupper($s);
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
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
