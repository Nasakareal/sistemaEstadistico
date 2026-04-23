<?php

namespace App\Services\Delegaciones\Hojas;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class NovRelSheetService
{
    public function generar($sheet, string $fecha): void
    {
        // Encabezados
        $sheet->setCellValue('A1', 'N°.');
        $sheet->setCellValue('B1', 'HORA');
        $sheet->setCellValue('C1', 'LUGAR');
        $sheet->setCellValue('D1', 'ASUNTO');
        $sheet->setCellValue('E1', 'RESOLUCIÓN');
        $sheet->setCellValue('F1', 'VEHÍCULOS TURNADOS, PERSONAS DETENIDAS, VEHÍCULOS RECUPERADOS (CANTIDAD Y DATOS GENERALES)');
        $sheet->setCellValue('G1', 'GRAFICA 1');
        $sheet->setCellValue('H1', 'GRAFICA 2');
        $sheet->setCellValue('I1', 'GRAFICA 3');

        // Altura encabezado
        $sheet->getRowDimension(1)->setRowHeight(60);

        // 10 filas numeradas con altura grande
        $fila = 2;

        for ($i = 1; $i <= 10; $i++) {
            $sheet->setCellValue('A' . $fila, $i);

            // 👇 AQUÍ le metes la altura perrona
            $sheet->getRowDimension($fila)->setRowHeight(319.5);

            $fila++;
        }

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(30);
        $sheet->getColumnDimension('F')->setWidth(50);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);

        // Estilo encabezado
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EDEDED'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CFCFCF'],
                ],
            ],
        ]);

        // Bordes tabla
        $sheet->getStyle('A2:I11')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9D9'],
                ],
            ],
        ]);

        // Alinear columna N°
        $sheet->getStyle('A2:A11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:I11')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    }
}
