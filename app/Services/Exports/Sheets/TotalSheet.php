<?php

namespace App\Services\Exports\Sheets;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class TotalSheet
{
    public function build(Spreadsheet $spreadsheet, Carbon $corte): void
    {
        $sheet = new Worksheet($spreadsheet, 'TOTAL');
        $spreadsheet->addSheet($sheet);

        $sheet->getColumnDimension('A')->setWidth(80);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(60);

        $sheet->setCellValue('A1', 'TOTAL');
        $sheet->setCellValue('A2', 'PENDIENTE: Esta hoja se arma mañana (contenido complejo).');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('A2')->getFont()->setSize(12);

        $sheet->getStyle('A1:A2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);

        $sheet->getStyle('A1:A2')->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->freezePane('A3');
        $sheet->getSheetView()->setZoomScale(110);
    }
}
