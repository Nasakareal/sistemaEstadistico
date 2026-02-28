<?php

namespace App\Services\Exports\Sheets;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CampanasSheet
{
    public function build(Spreadsheet $spreadsheet, Carbon $corte): void
    {
        $sheet = $this->createOrGetSheet($spreadsheet, 'CAMPAÑAS');

        $startCol = 'B';
        $endCol   = 'J';

        $rowTitle  = 2;
        $rowHeader = 3;
        $rowData   = 4;

        // ===== TÍTULO (B2:J2) =====
        $sheet->setCellValue($startCol . $rowTitle, $corte->format('d/m/Y'));
        $sheet->setCellValue('D' . $rowTitle, 'CAMPAÑAS'); // centrado visualmente dentro del merge

        $sheet->mergeCells($startCol . $rowTitle . ':C' . $rowTitle);
        $sheet->mergeCells('D' . $rowTitle . ':' . $endCol . $rowTitle);

        $sheet->getStyle($startCol . $rowTitle . ':' . $endCol . $rowTitle)->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '000000']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getRowDimension($rowTitle)->setRowHeight(26);

        // ===== ENCABEZADOS (B3:J3) =====
        $headers = [
            'B' => 'UNIDAD',
            'C' => "CAMPAÑA\nCONCIENTIZACION\nY PREVENCIÓN",
            'D' => "REPARTICIÓN\nDE TRIPTICOS",
            'E' => "ESTACIONALES\n(SEMANA SANTA,\nNAVIDAD ETC.)",
            'F' => "OTRAS (Especificar en\nlas novedades\nrelevantes)",
            'G' => 'ELEMENTOS',
            'H' => 'UNIDADES',
            'I' => "TOTAL, DE\nPERSONAS\nSENCIBILIZADAS",
            'J' => 'RECOMENDACIONES',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . $rowHeader, $text);
        }

        $sheet->getStyle($startCol . $rowHeader . ':' . $endCol . $rowHeader)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
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
        ]);

        $sheet->getRowDimension($rowHeader)->setRowHeight(58);

        // ===== FILA DATOS (B4:J4) =====
        $sheet->setCellValue('B' . $rowData, 'SINIESTROS');
        $sheet->setCellValue('C' . $rowData, 0);
        $sheet->setCellValue('D' . $rowData, 0);
        $sheet->setCellValue('E' . $rowData, 0);
        $sheet->setCellValue('F' . $rowData, 0);
        $sheet->setCellValue('G' . $rowData, 0);
        $sheet->setCellValue('H' . $rowData, 0);
        $sheet->setCellValue('I' . $rowData, 0);
        $sheet->setCellValue('J' . $rowData, 0);

        $sheet->getStyle($startCol . $rowData . ':' . $endCol . $rowData)->applyFromArray([
            'font' => ['size' => 10],
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

        $sheet->getStyle('B' . $rowData)->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'font' => ['bold' => true],
        ]);

        $sheet->getRowDimension($rowData)->setRowHeight(20);

        // ===== ANCHOS EXACTOS (B:J) =====
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(22);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getColumnDimension('F')->setWidth(26);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(22);
        $sheet->getColumnDimension('J')->setWidth(18);

        // Nada más. (No placeholder, no títulos extra)
    }

    protected function createOrGetSheet(Spreadsheet $spreadsheet, string $title): Worksheet
    {
        foreach ($spreadsheet->getAllSheets() as $s) {
            if ($s->getTitle() === $title) {
                return $s;
            }
        }

        $sheet = new Worksheet($spreadsheet, $title);
        $spreadsheet->addSheet($sheet);

        return $sheet;
    }
}
