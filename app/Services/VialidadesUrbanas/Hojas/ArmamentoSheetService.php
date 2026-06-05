<?php

namespace App\Services\VialidadesUrbanas\Hojas;

use App\Models\Armamento;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ArmamentoSheetService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    public function generar(Worksheet $sheet, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $navy = '002060';
        $outsideGray = 'A6A6A6';
        $doubleBorder = [
            'borderStyle' => Border::BORDER_DOUBLE,
            'color' => ['rgb' => '000000'],
        ];

        $styleTopBar = [
            'font' => [
                'bold' => true,
                'italic' => true,
                'size' => 13,
                'color' => ['rgb' => 'FFFFFF'],
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
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleTitle = $styleTopBar;
        $styleTitle['font']['size'] = 14;

        $styleHeader = [
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

        $styleUnidadHeader = $styleHeader;
        $styleUnidadHeader['font']['bold'] = true;
        $styleUnidadHeader['font']['italic'] = false;

        $styleBody = [
            'font' => [
                'bold' => false,
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

        $styleUnidadCell = $styleBody;
        $styleUnidadCell['font']['bold'] = true;

        $styleTotalCell = $styleBody;
        $styleTotalCell['font']['bold'] = true;

        $sheet->getStyle('A1:L6')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
        ]);

        $sheet->setCellValue('B2', 'FECHA');
        $sheet->setCellValue('C2', Carbon::parse($fecha, 'America/Mexico_City')->format('d/m/Y'));
        $sheet->mergeCells('D2:L2');
        $sheet->setCellValue('D2', 'ESTADO DE FUERZA DE ARMAMENTO');

        $sheet->getStyle('B2:C2')->applyFromArray($styleTopBar);
        $sheet->getStyle('D2:L2')->applyFromArray($styleTitle);
        $sheet->getRowDimension(2)->setRowHeight(25);

        $sheet->mergeCells('B3:B4');
        $sheet->setCellValue('B3', 'UNIDAD');

        $sheet->mergeCells('C3:E3');
        $sheet->setCellValue('C3', 'ARMAS');

        $sheet->mergeCells('F3:I3');
        $sheet->setCellValue('F3', 'MUNICIÓN');

        $sheet->mergeCells('J3:L3');
        $sheet->setCellValue('J3', 'DONDE SE ENCUENTRAN');

        $sheet->setCellValue('C4', 'ARMA CORTA');
        $sheet->setCellValue('D4', 'ARMA LARGA');
        $sheet->setCellValue('E4', 'TOTAL');
        $sheet->setCellValue('F4', 'CARGADORES');
        $sheet->setCellValue('G4', "CARTUCHOS\n9mm");
        $sheet->setCellValue('H4', "CARTUCHOS\n.223 Y/O 5.56");
        $sheet->setCellValue('I4', "CARTUCHOS\n0.38");
        $sheet->setCellValue('J4', 'ASIGNADO');
        $sheet->setCellValue('K4', 'EN DEPÓSITO');
        $sheet->setCellValue('L4', 'TOTAL');

        $sheet->getStyle('B3:B4')->applyFromArray($styleUnidadHeader);
        $sheet->getStyle('C3:L4')->applyFromArray($styleHeader);
        $sheet->getStyle('C4:L4')->getAlignment()->setTextRotation(90);
        $sheet->getStyle('E4:L4')->getFont()->setBold(true);
        $sheet->getRowDimension(3)->setRowHeight(48);
        $sheet->getRowDimension(4)->setRowHeight(78);

        $resumen = $this->resumenArmamento($fecha);

        $sheet->setCellValue('B5', 'VIALIDADES URBANAS');
        $sheet->setCellValue('C5', $resumen['arma_corta']);
        $sheet->setCellValue('D5', $resumen['arma_larga']);
        $sheet->setCellValue('E5', $resumen['total_armas']);
        $sheet->setCellValue('F5', $resumen['cargadores']);
        $sheet->setCellValue('G5', $resumen['cartuchos_9mm']);
        $sheet->setCellValue('H5', $resumen['cartuchos_223_556']);
        $sheet->setCellValue('I5', $resumen['cartuchos_038']);
        $sheet->setCellValue('J5', $resumen['asignado']);
        $sheet->setCellValue('K5', $resumen['en_deposito']);
        $sheet->setCellValue('L5', $resumen['total_armas']);

        $sheet->getStyle('B5')->applyFromArray($styleUnidadCell);
        $sheet->getStyle('C5:L5')->applyFromArray($styleBody);
        $sheet->getStyle('E5')->applyFromArray($styleTotalCell);
        $sheet->getStyle('L5')->applyFromArray($styleTotalCell);
        $sheet->getRowDimension(5)->setRowHeight(22);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(28);

        foreach (range('C', 'L') as $column) {
            $sheet->getColumnDimension($column)->setWidth(14);
        }
    }

    private function resumenArmamento(string $fecha): array
    {
        $armamentos = $this->armamentosVialidadesUrbanas($fecha);

        $resumen = [
            'arma_corta' => 0,
            'arma_larga' => 0,
            'total_armas' => $armamentos->count(),
            'cargadores' => 0,
            'cartuchos_9mm' => 0,
            'cartuchos_223_556' => 0,
            'cartuchos_038' => 0,
            'asignado' => 0,
            'en_deposito' => 0,
        ];

        foreach ($armamentos as $armamento) {
            $tipo = $this->norm($armamento->tipo ?? '');

            if ($tipo === 'ARMA CORTA') {
                $resumen['arma_corta']++;
            } elseif ($tipo === 'ARMA LARGA') {
                $resumen['arma_larga']++;
            }

            $resumen['cargadores'] += (int) ($armamento->cargadores_cantidad ?? 0);

            $cartuchos = (int) ($armamento->cartuchos_cantidad ?? 0);
            $grupoCalibre = $this->grupoCalibre((string) ($armamento->calibre ?? ''));

            if ($grupoCalibre === '9mm') {
                $resumen['cartuchos_9mm'] += $cartuchos;
            } elseif ($grupoCalibre === '223_556') {
                $resumen['cartuchos_223_556'] += $cartuchos;
            } elseif ($grupoCalibre === '038') {
                $resumen['cartuchos_038'] += $cartuchos;
            }

            if ($this->estaAsignado($armamento)) {
                $resumen['asignado']++;
            }
        }

        $resumen['en_deposito'] = max($resumen['total_armas'] - $resumen['asignado'], 0);

        return $resumen;
    }

    private function armamentosVialidadesUrbanas(string $fecha): Collection
    {
        $fechaCorte = Carbon::parse($fecha, 'America/Mexico_City')->toDateString();

        return Armamento::query()
            ->with(['asignaciones' => function ($query) use ($fechaCorte) {
                $query->where('activo', 1)
                    ->whereDate('fecha_asignacion', '<=', $fechaCorte)
                    ->where(function ($q) use ($fechaCorte) {
                        $q->whereNull('fecha_fin')
                            ->orWhereDate('fecha_fin', '>=', $fechaCorte);
                    });
            }])
            ->where('unidad_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->whereRaw("UPPER(TRIM(COALESCE(estatus, ''))) = ?", ['ACTIVO'])
            ->get();
    }

    private function estaAsignado(Armamento $armamento): bool
    {
        return $armamento->asignaciones->isNotEmpty();
    }

    private function grupoCalibre(string $calibre): ?string
    {
        $calibre = $this->norm($calibre);
        $calibre = str_replace([' ', "\t"], '', $calibre);

        if ($calibre === '') {
            return null;
        }

        if (str_contains($calibre, '9MM') || str_contains($calibre, '9-MM')) {
            return '9mm';
        }

        if (
            str_contains($calibre, '.223') ||
            str_contains($calibre, '0.223') ||
            str_contains($calibre, '5.56') ||
            str_contains($calibre, '5,56')
        ) {
            return '223_556';
        }

        if (str_contains($calibre, '.38') || str_contains($calibre, '0.38')) {
            return '038';
        }

        return null;
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
