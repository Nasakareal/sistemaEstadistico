<?php

namespace App\Services\Siniestros\Hojas;

use App\Services\EstadoFuerzaService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EstadoFuerzaSheetService extends BaseSiniestrosSheetService
{
    private EstadoFuerzaService $estadoService;

    public function __construct(EstadoFuerzaService $estadoService)
    {
        $this->estadoService = $estadoService;
    }

    public function generar(
        Worksheet $sheet,
        Collection $personal,
        string $fecha,
        Carbon $inicio,
        Carbon $fin
    ): void {
        $navy = '0B2A5B';
        $lightBlue = 'BDD7EE';
        $outsideGray = 'A6A6A6';

        $doubleBorder = [
            'borderStyle' => Border::BORDER_DOUBLE,
            'color' => ['rgb' => '000000'],
        ];

        $styleTitle = [
            'font' => [
                'bold' => false,
                'size' => 20,
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
                'allBorders' => [
                    'borderStyle' => $doubleBorder['borderStyle'],
                    'color' => $doubleBorder['color'],
                ],
            ],
        ];

        $styleFechaBar = [
            'font' => [
                'bold' => true,
                'size' => 11,
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
                'allBorders' => [
                    'borderStyle' => $doubleBorder['borderStyle'],
                    'color' => $doubleBorder['color'],
                ],
            ],
        ];

        $styleHeader = [
            'font' => [
                'italic' => true,
                'bold' => true,
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
                'allBorders' => [
                    'borderStyle' => $doubleBorder['borderStyle'],
                    'color' => $doubleBorder['color'],
                ],
            ],
        ];

        $styleBody = [
            'font' => [
                'bold' => false,
                'size' => 11,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $lightBlue],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => $doubleBorder['borderStyle'],
                    'color' => $doubleBorder['color'],
                ],
            ],
        ];

        $styleTotalCell = $styleBody;
        $styleTotalCell['font']['bold'] = true;
        $styleTotalCell['font']['size'] = 16;

        $sheet->getStyle('A1:N9')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
        ]);

        $sheet->setCellValue('B2', 'FECHA');
        $sheet->setCellValue('C2', Carbon::parse($fecha)->format('d/m/Y'));

        $sheet->mergeCells('D2:M2');
        $sheet->setCellValue('D2', 'ESTADO DE FUERZA DE PERSONAL');

        $sheet->getStyle('B2:C2')->applyFromArray($styleFechaBar);
        $sheet->getStyle('D2:M2')->applyFromArray($styleTitle);
        $sheet->getRowDimension(2)->setRowHeight(34);

        $headers = [
            'B' => 'UNIDAD',
            'C' => 'CATEGORIA',
            'D' => 'PRESENTES',
            'E' => 'FRANCOS',
            'F' => 'FALTANDO',
            'G' => 'CURSOS',
            'H' => 'VACACIONES',
            'I' => 'COMISIONADOS',
            'J' => 'INCAPACIDAD',
            'K' => 'PERMISO',
            'L' => 'OTROS',
            'M' => "TOTAL, POR\nAGRUPAMIENTO",
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue($column . '3', $label);
        }

        $sheet->getStyle('B3:M3')->applyFromArray($styleHeader);
        $sheet->getStyle('D3:L3')->getAlignment()->setTextRotation(90);
        $sheet->getStyle('B3')->getFont()->setItalic(false);
        $sheet->getStyle('M3')->getFont()->setBold(true);
        $sheet->getRowDimension(3)->setRowHeight(96);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(18);

        foreach (range('D', 'L') as $column) {
            $sheet->getColumnDimension($column)->setWidth(12);
        }

        $sheet->getColumnDimension('M')->setWidth(20);

        $conteos = $this->conteosPorCategoriaFija($personal, $fin);

        $filas = [
            4 => [
                'label' => 'OPERATIVOS',
                'conteos' => $conteos['OPERATIVOS'],
            ],
            5 => [
                'label' => 'ADMINISTRATIVOS',
                'conteos' => $conteos['ADMINISTRATIVOS'],
            ],
        ];

        $sheet->mergeCells('B4:B5');
        $sheet->setCellValue('B4', 'SINIESTROS');

        foreach ($filas as $row => $fila) {
            $sheet->setCellValue("C{$row}", $fila['label']);
            $sheet->setCellValue("D{$row}", $this->visibleNumero($fila['conteos']['PRESENTES']));
            $sheet->setCellValue("E{$row}", $this->visibleNumero($fila['conteos']['FRANCOS']));
            $sheet->setCellValue("F{$row}", $this->visibleNumero($fila['conteos']['FALTANDO']));
            $sheet->setCellValue("G{$row}", $this->visibleNumero($fila['conteos']['CURSOS']));
            $sheet->setCellValue("H{$row}", $this->visibleNumero($fila['conteos']['VACACIONES']));
            $sheet->setCellValue("I{$row}", $this->visibleNumero($fila['conteos']['COMISIONADOS']));
            $sheet->setCellValue("J{$row}", $this->visibleNumero($fila['conteos']['INCAPACIDAD']));
            $sheet->setCellValue("K{$row}", $this->visibleNumero($fila['conteos']['PERMISO']));
            $sheet->setCellValue("L{$row}", $this->visibleNumero($fila['conteos']['OTROS']));
            $sheet->setCellValue("M{$row}", array_sum($fila['conteos']));

            $sheet->getRowDimension($row)->setRowHeight(24);
        }

        $sheet->getStyle('B4:M5')->applyFromArray($styleBody);
        $sheet->getStyle('B4')->getFont()->setBold(true);
        $sheet->getStyle('M4:M5')->applyFromArray($styleTotalCell);

        $sheet->getStyle('B2:M5')->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => $doubleBorder['borderStyle'],
                    'color' => $doubleBorder['color'],
                ],
            ],
        ]);

        $sheet->getStyle('B2:M5')
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->freezePane('D4');
    }

    private function conteosPorCategoriaFija(
        Collection $personal,
        Carbon $corte
    ): array {
        $conteos = [
            'OPERATIVOS' => $this->conteosVacios(),
            'ADMINISTRATIVOS' => $this->conteosVacios(),
        ];

        foreach ($personal as $elemento) {
            $categoria = $this->categoriaEstadoFuerza(
                $elemento->categoria ?? null
            );

            $estado = $this->estadoService->estado(
                $elemento,
                $corte
            );

            switch ($estado) {
                case 'EN_SERVICIO':
                    $conteos[$categoria]['PRESENTES']++;
                    break;

                case 'FRANCO':
                    $conteos[$categoria]['FRANCOS']++;
                    break;

                case 'FALTANDO':
                    $conteos[$categoria]['FALTANDO']++;
                    break;

                case 'CURSOS':
                    $conteos[$categoria]['CURSOS']++;
                    break;

                case 'VACACIONES':
                    $conteos[$categoria]['VACACIONES']++;
                    break;

                case 'COMISIONADOS':
                    $conteos[$categoria]['COMISIONADOS']++;
                    break;

                case 'INCAPACIDAD':
                    $conteos[$categoria]['INCAPACIDAD']++;
                    break;

                case 'PERMISO':
                    $conteos[$categoria]['PERMISO']++;
                    break;

                default:
                    $conteos[$categoria]['OTROS']++;
                    break;
            }
        }

        return $conteos;
    }

    private function conteosVacios(): array
    {
        return [
            'PRESENTES' => 0,
            'FRANCOS' => 0,
            'FALTANDO' => 0,
            'CURSOS' => 0,
            'VACACIONES' => 0,
            'COMISIONADOS' => 0,
            'INCAPACIDAD' => 0,
            'PERMISO' => 0,
            'OTROS' => 0,
        ];
    }

    private function categoriaEstadoFuerza($categoria): string
    {
        $categoria = mb_strtoupper(
            trim((string) $categoria)
        );

        return mb_strpos($categoria, 'ADMIN') !== false
            ? 'ADMINISTRATIVOS'
            : 'OPERATIVOS';
    }

    private function visibleNumero(int $valor)
    {
        return $valor > 0 ? $valor : '';
    }
}
