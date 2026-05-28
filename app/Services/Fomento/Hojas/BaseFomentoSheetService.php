<?php

namespace App\Services\Fomento\Hojas;

use App\Models\Personal;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

abstract class BaseFomentoSheetService
{
    protected function titulo(Worksheet $sheet, string $range, string $text): void
    {
        $sheet->mergeCells($range);
        $sheet->setCellValue(explode(':', $range)[0], $text);
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F4E79'],
            ],
        ]);
    }

    protected function tabla(Worksheet $sheet, int $row, array $headers, array $rows): void
    {
        $sheet->fromArray($headers, null, 'A' . $row);

        if (!empty($rows)) {
            $sheet->fromArray($rows, null, 'A' . ($row + 1));
        }

        $lastColumn = chr(ord('A') + count($headers) - 1);
        $lastRow = $row + max(count($rows), 1);

        $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '44546A'],
            ],
        ]);

        $sheet->getStyle('A' . $row . ':' . $lastColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'BFBFBF'],
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_TOP,
                'wrapText' => true,
            ],
        ]);

        $sheet->setAutoFilter('A' . $row . ':' . $lastColumn . $lastRow);
    }

    protected function estiloBase(Worksheet $sheet, string $lastColumn): void
    {
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('A1:' . $lastColumn . $sheet->getHighestRow())
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP);
    }

    protected function periodoTexto(Carbon $inicio, Carbon $fin): string
    {
        return $inicio->format('d/m/Y H:i') . ' - ' . $fin->format('d/m/Y H:i');
    }

    protected function nombrePersonal($item): string
    {
        return Personal::formarNombreCompleto(
            $item->nombre ?? null,
            $item->ap_paterno ?? null,
            $item->ap_materno ?? null
        );
    }

    protected function valorTexto($value): string
    {
        $value = trim((string) $value);

        return $value === '' ? 'NO ESPECIFICADO' : $value;
    }

    protected function poblacionAtendida($actividad): int
    {
        $fomento = (int) ($actividad->total_poblacion_atendida ?? 0);

        return $fomento > 0 ? $fomento : (int) ($actividad->personas_alcanzadas ?? 0);
    }

    protected function horaCorta($hora): string
    {
        if (!$hora) {
            return '';
        }

        return substr((string) $hora, 0, 5);
    }
}
