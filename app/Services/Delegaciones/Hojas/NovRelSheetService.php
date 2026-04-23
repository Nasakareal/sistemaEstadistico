<?php

namespace App\Services\Delegaciones\Hojas;

class NovRelSheetService
{
    public function generar($sheet, string $fecha): void
    {
        $sheet->setCellValue('A1', 'NOV_REL');
        $sheet->setCellValue('A2', 'Fecha: ' . $fecha);
    }
}
