<?php

namespace App\Services\Exports\Sheets;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class OperativosSheet
{
    public function build(Spreadsheet $spreadsheet, Carbon $corte): void
    {
        $sheet = new Worksheet($spreadsheet, 'OPERATIVOS');
        $spreadsheet->addSheet($sheet);

        // Columnas A..P (16 columnas)
        $colEnd = 'P';

        // Anchos
        $widths = [
            'A' => 16, // UNIDAD
            'B' => 22, // LUGAR
            'C' => 48, // OPERATIVOS
            'D' => 14,
            'E' => 14,
            'F' => 18,
            'G' => 18,
            'H' => 20,
            'I' => 18,
            'J' => 12,
            'K' => 12,
            'L' => 18,
            'M' => 18,
            'N' => 12,
            'O' => 12,
            'P' => 12,
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Alturas
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(90);

        // Encabezados fila 1
        $fecha = $corte->copy()->format('d/m/Y');

        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'TABLA DE OPERATIVOS');

        $sheet->mergeCells('D1:F1');
        $sheet->setCellValue('D1', $fecha);

        $sheet->mergeCells('G1:I1');
        $sheet->setCellValue('G1', 'OPERATIVIDAD');

        $sheet->mergeCells('J1:K1');
        $sheet->setCellValue('J1', 'CORRALÓN');

        $sheet->setCellValue('L1', 'AMONESTACIONES VERBALES');
        $sheet->setCellValue('M1', 'VEHÍCULOS RECUPERADOS');

        $sheet->mergeCells('N1:P1');
        $sheet->setCellValue('N1', 'DETENIDOS');

        // Subencabezados fila 2
        $sheet->setCellValue('A2', 'UNIDAD');
        $sheet->setCellValue('B2', 'LUGAR');
        $sheet->setCellValue('C2', 'OPERATIVOS');

        $sheet->setCellValue('D2', 'DISPOSITIVOS REALIZADOS');
        $sheet->setCellValue('E2', 'DESPOLARIZADOS');
        $sheet->setCellValue('F2', 'ANTECEDENTES REVISADOS A PERSONAS');
        $sheet->setCellValue('G2', 'ANTECEDENTES REVISADOS A VEHÍCULOS');
        $sheet->setCellValue('H2', 'ANTECEDENTES REVISADOS A MOTOCICLETAS');
        $sheet->setCellValue('I2', 'TOTAL ANTECEDENTES REVISADOS');

        $sheet->setCellValue('J2', 'VEHÍCULOS');
        $sheet->setCellValue('K2', 'MOTOS');

        $sheet->setCellValue('N2', 'FUERO COMÚN');
        $sheet->setCellValue('O2', 'FUERO FEDERAL');
        $sheet->setCellValue('P2', 'JURÍDICO');

        // Datos (solo plantilla)
        $operativos = [
            'RELÁMPAGO',
            'CARRUSEL',
            'BLINDAJE',
            'CONCIENTIZACIÓN A MOTOCICLISTAS',
            'PUESTO DE REVISIÓN',
            'PUESTO DE CONTROL',
            'APOYO COCOTRA',
            'BLINDAJE CON ESTADOS COLINDANTES',
            'BASES DE OPERACIONES INTERINSTITUCIONAL',
            'OTROS OPERATIVOS (Especificar en las novedades relevantes)',
        ];

        $startRow = 3;
        foreach ($operativos as $idx => $nombreOperativo) {
            $r = $startRow + $idx;
            $sheet->setCellValue("A{$r}", 'SINIESTROS');
            $sheet->setCellValue("C{$r}", $nombreOperativo);
            $sheet->setCellValue("I{$r}", 0);
            $sheet->getRowDimension($r)->setRowHeight(20);
        }

        // Estilos base
        $sheet->getStyle("A1:{$colEnd}2")->getFont()->setBold(true);

        // Colores similares a tu captura (aprox)
        $darkBlue = '0B2E5B';
        $cyan = '00AEEF';
        $green = '00A651';
        $white = 'FFFFFF';

        // A1:C1 (título) + D1:F1 (fecha) en azul
        $sheet->getStyle('A1:C1')->applyFromArray($this->styleHeaderFill($darkBlue, $white, 18));
        $sheet->getStyle('D1:F1')->applyFromArray($this->styleHeaderFill($darkBlue, $white, 18));

        // G1:I1 operatividad (cian)
        $sheet->getStyle('G1:I1')->applyFromArray($this->styleHeaderFill($cyan, '000000', 12));

        // J1:K1 corralón (verde)
        $sheet->getStyle('J1:K1')->applyFromArray($this->styleHeaderFill($green, '000000', 12));

        // L1 y M1 en blanco con negrita centrado
        $sheet->getStyle('L1:M1')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => '000000'],
            ],
        ]);

        // N1:P1 DETENIDOS (blanco)
        $sheet->getStyle('N1:P1')->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '000000'],
            ],
        ]);

        // Centrar fila 1
        $sheet->getStyle("A1:{$colEnd}1")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Fila 2: centrado, wrap
        $sheet->getStyle("A2:{$colEnd}2")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        // Rotación vertical en columnas tipo “tiras”
        foreach (['D','E','F','G','H','I','J','K','N','O','P'] as $col) {
            $sheet->getStyle($col.'2')->getAlignment()->setTextRotation(90);
        }

        // Encabezados A2:C2 sin rotación, más grandes
        $sheet->getStyle('A2:C2')->getFont()->setSize(11);
        $sheet->getStyle('A2:C2')->getAlignment()->setTextRotation(0);

        // Bordes a toda la tabla (encabezados + filas plantilla)
        $lastRow = $startRow + count($operativos) - 1;
        $sheet->getStyle("A1:{$colEnd}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Formato texto en general (evitar cosas raras)
        $sheet->getStyle("A3:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("A3:C{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("C3:C{$lastRow}")->getAlignment()->setWrapText(true);

        // Congelar encabezados
        $sheet->freezePane('A3');

        // Zoom agradable
        $sheet->getSheetView()->setZoomScale(85);
    }

    private function styleHeaderFill(string $bgRgb, string $fontRgb, int $fontSize): array
    {
        return [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'color' => ['rgb' => $bgRgb],
            ],
            'font' => [
                'bold' => true,
                'size' => $fontSize,
                'color' => ['rgb' => $fontRgb],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
    }
}
