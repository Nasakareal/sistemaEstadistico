<?php

namespace App\Services\VialidadesUrbanas\Hojas;

use App\Models\Patrulla;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EstadoVehicularSheetService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    public function generar(Worksheet $sheet, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $blue = '0070C0';
        $outsideGray = 'A6A6A6';
        $doubleBorder = [
            'borderStyle' => Border::BORDER_DOUBLE,
            'color' => ['rgb' => '000000'],
        ];

        $styleTitle = [
            'font' => [
                'bold' => true,
                'italic' => true,
                'size' => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $blue],
            ],
            'borders' => [
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleFechaBar = $styleTitle;
        $styleFechaBar['font']['size'] = 10;

        $styleHeader = [
            'font' => [
                'bold' => true,
                'italic' => true,
                'size' => 10,
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
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleBody = [
            'font' => [
                'bold' => false,
                'italic' => true,
                'size' => 10,
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
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleAreaCell = $styleBody;
        $styleAreaCell['font']['bold'] = true;
        $styleAreaCell['font']['italic'] = false;

        $styleTotalCell = $styleBody;
        $styleTotalCell['font']['bold'] = true;
        $styleTotalCell['font']['italic'] = false;
        $styleTotalCell['font']['size'] = 14;

        $sheet->getStyle('A1:N13')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
        ]);

        $vehiculos = $this->vehiculosOficiales();
        $patrullas = $this->patrullasVialidadesUrbanas();
        $fechaCorte = Carbon::parse($fecha, 'America/Mexico_City');

        $this->renderSection(
            $sheet,
            2,
            $fechaCorte,
            'ESTADO DE FUERZA VEHICULAR ACTIVO',
            'TOTAL ACTIVAS',
            $vehiculos,
            $this->conteosPorEstado($patrullas, fn ($patrulla) => (bool) ($patrulla->activa ?? false)),
            $styleFechaBar,
            $styleTitle,
            $styleHeader,
            $styleBody,
            $styleAreaCell,
            $styleTotalCell
        );

        $this->renderSection(
            $sheet,
            6,
            $fechaCorte,
            'ESTADO DE FUERZA VEHICULAR INACTIVO',
            'TOTAL DE INACTIVAS',
            $vehiculos,
            $this->conteosPorEstado($patrullas, fn ($patrulla) => !(bool) ($patrulla->activa ?? false)),
            $styleFechaBar,
            $styleTitle,
            $styleHeader,
            $styleBody,
            $styleAreaCell,
            $styleTotalCell
        );

        $this->renderSection(
            $sheet,
            10,
            $fechaCorte,
            'TOTAL DE ESTADO DE FUERZA VEHICULAR',
            'TOTAL',
            $vehiculos,
            $this->conteosPorEstado($patrullas, fn () => true),
            $styleFechaBar,
            $styleTitle,
            $styleHeader,
            $styleBody,
            $styleAreaCell,
            $styleTotalCell
        );

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(18);

        foreach (range('C', 'L') as $column) {
            $sheet->getColumnDimension($column)->setWidth(14);
        }

        $sheet->getColumnDimension('M')->setWidth(16);
    }

    private function patrullasVialidadesUrbanas(): Collection
    {
        return Patrulla::query()
            ->where('unidad_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->orderBy('numero_economico')
            ->get();
    }

    private function vehiculosOficiales(): array
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

    private function conteosPorEstado(Collection $patrullas, callable $filter): array
    {
        $conteos = array_fill_keys($this->vehiculosOficiales(), 0);

        foreach ($patrullas as $patrulla) {
            if (!$filter($patrulla)) {
                continue;
            }

            $vehiculo = $this->vehiculoOficial($patrulla);
            $conteos[$vehiculo]++;
        }

        return $conteos;
    }

    private function renderSection(
        Worksheet $sheet,
        int $topRow,
        Carbon $fecha,
        string $titulo,
        string $totalLabel,
        array $vehiculos,
        array $conteos,
        array $styleFechaBar,
        array $styleTitle,
        array $styleHeader,
        array $styleBody,
        array $styleAreaCell,
        array $styleTotalCell
    ): void {
        $headerRow = $topRow + 1;
        $dataRow = $topRow + 2;

        $sheet->setCellValue("B{$topRow}", 'FECHA');
        $sheet->setCellValue("C{$topRow}", $fecha->format('d/m/Y'));
        $sheet->mergeCells("D{$topRow}:M{$topRow}");
        $sheet->setCellValue("D{$topRow}", $titulo);

        $sheet->getStyle("B{$topRow}:C{$topRow}")->applyFromArray($styleFechaBar);
        $sheet->getStyle("D{$topRow}:M{$topRow}")->applyFromArray($styleTitle);
        $sheet->getRowDimension($topRow)->setRowHeight(21);

        $sheet->setCellValue("B{$headerRow}", "ÁREA Y/O\nREGIÓN");
        $column = 'C';

        foreach ($vehiculos as $vehiculo) {
            $sheet->setCellValue("{$column}{$headerRow}", $vehiculo);
            $column++;
        }

        $sheet->setCellValue("M{$headerRow}", $totalLabel);
        $sheet->getStyle("B{$headerRow}:M{$headerRow}")->applyFromArray($styleHeader);
        $sheet->getStyle("C{$headerRow}:L{$headerRow}")->getAlignment()->setTextRotation(90);
        $sheet->getRowDimension($headerRow)->setRowHeight(84);

        $sheet->setCellValue("B{$dataRow}", "VIALIDADES\nURBANAS");
        $column = 'C';
        $total = 0;

        foreach ($vehiculos as $vehiculo) {
            $valor = (int) ($conteos[$vehiculo] ?? 0);
            $sheet->setCellValue("{$column}{$dataRow}", $valor);
            $total += $valor;
            $column++;
        }

        $sheet->setCellValue("M{$dataRow}", $total);

        $sheet->getStyle("B{$dataRow}")->applyFromArray($styleAreaCell);
        $sheet->getStyle("C{$dataRow}:L{$dataRow}")->applyFromArray($styleBody);
        $sheet->getStyle("M{$dataRow}")->applyFromArray($styleTotalCell);
        $sheet->getRowDimension($dataRow)->setRowHeight(25);
    }

    private function vehiculoOficial(Patrulla $patrulla): string
    {
        $marca = $this->norm($patrulla->marca ?? '');
        $linea = $this->norm($patrulla->linea ?? '');
        $tipo = $this->norm($patrulla->tipo ?? '');
        $texto = trim($tipo . ' ' . $marca . ' ' . $linea . ' ' . $this->norm($patrulla->numero_economico ?? ''));

        if (str_contains($texto, 'CHARGER')) {
            return 'CHARGER';
        }

        if (str_contains($texto, 'RAM')) {
            return 'RAM DODGE 1500';
        }

        if (str_contains($texto, 'F150') || str_contains($texto, 'F-150') || str_contains($texto, 'F 150')) {
            return 'FORD 150';
        }

        if (str_contains($texto, 'F250') || str_contains($texto, 'F-250') || str_contains($texto, 'F 250')) {
            return 'CAMIONETA F250';
        }

        if (str_contains($texto, 'ECOSPORT') || str_contains($texto, 'ECO SPORT')) {
            return 'ECO SPORT';
        }

        if (str_contains($texto, 'PATRIOT')) {
            return 'JEEP PATRIOT';
        }

        if (str_contains($texto, 'TSURU')) {
            return 'TSURU';
        }

        if (str_contains($texto, 'PLATINA')) {
            return 'PLATINA';
        }

        if (str_contains($texto, 'KLR650') || str_contains($texto, 'KLR 650')) {
            return 'KAWASAKY KLR650';
        }

        if (str_contains($texto, 'ER-6N') || str_contains($texto, 'ER6N') || str_contains($texto, 'ER 6N')) {
            return 'KAWASAKY ER-6N';
        }

        if ($marca === 'FORD') {
            if (in_array($linea, ['F150', '150', 'F-150', 'F 150'], true)) {
                return 'FORD 150';
            }

            if (in_array($linea, ['F250', '250', 'F-250', 'F 250'], true)) {
                return 'CAMIONETA F250';
            }

            if (in_array($linea, ['ECOSPORT', 'ECO SPORT'], true)) {
                return 'ECO SPORT';
            }
        }

        if ($marca === 'DODGE') {
            if ($linea === 'RAM' || str_contains($texto, 'RAM')) {
                return 'RAM DODGE 1500';
            }

            if ($linea === 'CHARGER' || str_contains($texto, 'CHARGER')) {
                return 'CHARGER';
            }
        }

        if ($marca === 'JEEP' && ($linea === 'PATRIOT' || str_contains($texto, 'PATRIOT'))) {
            return 'JEEP PATRIOT';
        }

        if ($marca === 'NISSAN') {
            if ($linea === 'TSURU' || str_contains($texto, 'TSURU')) {
                return 'TSURU';
            }

            if ($linea === 'PLATINA' || str_contains($texto, 'PLATINA')) {
                return 'PLATINA';
            }
        }

        if (in_array($marca, ['KAWASAKI', 'KAWASASKI', 'KAWASAKY'], true) || $tipo === 'MOTO') {
            if (in_array($linea, ['KLR650', 'KLR 650'], true) || str_contains($texto, 'KLR650') || str_contains($texto, 'KLR 650')) {
                return 'KAWASAKY KLR650';
            }

            if (in_array($linea, ['ER-6N', 'ER6N', 'ER 6N'], true) || str_contains($texto, 'ER-6N') || str_contains($texto, 'ER6N')) {
                return 'KAWASAKY ER-6N';
            }
        }

        return 'CHARGER';
    }

    private function norm($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = mb_strtoupper($value, 'UTF-8');

        return preg_replace('/\s+/', ' ', $value);
    }
}
