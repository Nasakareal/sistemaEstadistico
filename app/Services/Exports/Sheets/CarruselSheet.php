<?php

namespace App\Services\Exports\Sheets;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CarruselSheet
{
    public function build(Spreadsheet $spreadsheet, Carbon $corte): void
    {
        $sheet = new Worksheet($spreadsheet, 'CARRUSEL');
        $spreadsheet->addSheet($sheet);

        // Columnas A..U (21 columnas)
        $cols = range('A', 'U');

        // Anchos (A y B más amplias, el resto angostas por encabezado vertical)
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(22);
        foreach (range('C', 'U') as $c) {
            $sheet->getColumnDimension($c)->setWidth(6);
        }

        // Estilos base
        $blueHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B2D5B'], // azul oscuro
            ],
        ];

        $subHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];

        $thinBorders = [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];

        $bandBlue = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B2D5B'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ];

        // =========================
        // TITULOS SUPERIORES
        // =========================
        // Row 1: OPERATIVOS CARUSEL (A..R) y DETENIDOS (S..U)
        $sheet->mergeCells('A1:R1');
        $sheet->mergeCells('S1:U1');

        $sheet->setCellValue('A1', 'OPERATIVOS CARUSEL');
        $sheet->setCellValue('S1', 'DETENIDOS');

        $sheet->getStyle('A1:R1')->applyFromArray($blueHeader);
        $sheet->getStyle('S1:U1')->applyFromArray($blueHeader);

        $sheet->getRowDimension(1)->setRowHeight(22);

        // =========================
        // ENCABEZADOS DE COLUMNAS
        // =========================
        $headers = [
            'A' => 'REGIÓN',
            'B' => 'UNIDAD',
            'C' => 'DISPOSITIVOS',
            'D' => 'PUESTOS DE CONTROL',
            'E' => 'UBICACIÓN',
            'F' => 'UNIDADES PARTICIPANTES',
            'G' => 'KILOMETROS RECORRIDOS',
            'H' => 'CANTIDAD DE RECORIDOS',
            'I' => 'TRAMO CARRETERO',
            'J' => 'ESTADO DE FUERZA',
            'K' => 'TIEMPO IMPLEMENTADO',
            'L' => 'APOYOS VIALES',
            'M' => 'APOYO A CARAVANAS',
            'N' => 'SERVICIO DE ESCOLTA',
            'O' => 'CONOCIMIENTO DE REPORTES DE ROBO',
            'P' => 'ANTECEDENTES REVISADOS',
            'Q' => 'AMONESTACIONES VERBALES',
            'R' => 'VEHÍCULOS RECUPERADOS',
            'S' => 'FUERO COMÚN',
            'T' => 'FUERO FEDERAL',
            'U' => 'JURÍDICO',
        ];

        foreach ($headers as $col => $text) {
            $sheet->setCellValue($col . '2', $text);
        }

        $sheet->getStyle('A2:U2')->applyFromArray($subHeader);

        // Rotación vertical para C..U (para que quede como el formato de tu imagen)
        foreach (range('C', 'U') as $c) {
            $sheet->getStyle($c . '2')->getAlignment()->setTextRotation(90);
        }

        $sheet->getRowDimension(2)->setRowHeight(120);

        // =========================
        // FILA DE CONTENIDO (sin datos, solo placeholders)
        // =========================
        $sheet->setCellValue('A3', 'MORELIA');
        $sheet->setCellValue('B3', 'SINIESTROS');
        $sheet->getStyle('A3:U3')->applyFromArray($thinBorders);
        $sheet->getRowDimension(3)->setRowHeight(20);

        // =========================
        // BANDA "VERDADERO TOTAL" + "VERDADERO" (detenidos)
        // =========================
        $sheet->mergeCells('A4:B4');
        $sheet->setCellValue('A4', 'VERDADERO    TOTAL');
        $sheet->getStyle('A4:B4')->applyFromArray($bandBlue);

        $sheet->mergeCells('S4:U4');
        $sheet->setCellValue('S4', 'VERDADERO');
        $sheet->getStyle('S4:U4')->applyFromArray($bandBlue);

        // Bordes del resto de celdas en fila 4 (C4..R4) para mantener grilla
        $sheet->getStyle('C4:R4')->applyFromArray($thinBorders);
        $sheet->getRowDimension(4)->setRowHeight(20);

        // =========================
        // TABLA DE UBICACIONES
        // =========================
        $start = 6;

        $sheet->setCellValue("A{$start}", 'UBICACIÓN');
        $sheet->setCellValue("B{$start}", 'NOMBRE');
        $sheet->getStyle("A{$start}:B{$start}")->applyFromArray($subHeader);
        $sheet->getRowDimension($start)->setRowHeight(18);

        $ubicaciones = ['A','B','C','D','E','F','G','H','I','J','K','L'];
        $r = $start + 1;
        foreach ($ubicaciones as $u) {
            $sheet->setCellValue("A{$r}", $u);
            $sheet->setCellValue("B{$r}", '');
            $sheet->getStyle("A{$r}:B{$r}")->applyFromArray($thinBorders);
            $sheet->getRowDimension($r)->setRowHeight(18);
            $r++;
        }

        // =========================
        // TABLA TRAMO CARRETERO
        // =========================
        $sheet->setCellValue("A{$r}", 'TRAMO CARRETERO');
        $sheet->setCellValue("B{$r}", 'NOMBRE');
        $sheet->getStyle("A{$r}:B{$r}")->applyFromArray($subHeader);
        $sheet->getRowDimension($r)->setRowHeight(18);
        $r++;

        $tramos = ['Ñ','O','P','Q','R','S','T','U','W','X','Y','X'];
        foreach ($tramos as $t) {
            $sheet->setCellValue("A{$r}", $t);
            $sheet->setCellValue("B{$r}", '');
            $sheet->getStyle("A{$r}:B{$r}")->applyFromArray($thinBorders);
            $sheet->getRowDimension($r)->setRowHeight(18);
            $r++;
        }

        // Congelar encabezados (opcional, pero útil)
        $sheet->freezePane('C3');

        // Vista agradable
        $sheet->setShowGridlines(false);
    }
}
