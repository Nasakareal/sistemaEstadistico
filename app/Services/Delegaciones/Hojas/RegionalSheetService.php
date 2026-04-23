<?php

namespace App\Services\Delegaciones\Hojas;

class RegionalSheetService
{
    public function generar($sheet, string $fecha, string $scope): void
    {
        $sheet->setCellValue('A1', $scope);
        $sheet->setCellValue('A2', 'Fecha: ' . $fecha);
    }
}
