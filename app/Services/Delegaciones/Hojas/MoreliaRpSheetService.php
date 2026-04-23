<?php

namespace App\Services\Delegaciones\Hojas;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class MoreliaRpSheetService
{
    public function generar($sheet, string $fecha): void
    {
        $filas = $this->obtenerDatos($fecha);

        $sheet->setCellValue('A1', 'ACTIVIDAD');
        $sheet->setCellValue('B1', 'Cuenta de ACTIVIDAD');
        $sheet->setCellValue('C1', 'Suma de Numero de Agentes Participantes:');
        $sheet->setCellValue('D1', 'Suma de Cantidad de Unidades Oficiales de Seguridad Vial:');
        $sheet->setCellValue('E1', 'Suma de Kilómetros recorridos.');
        $sheet->setCellValue('F1', 'Suma de Cantidad de Personas alcanzadas.');

        $filaExcel = 2;

        foreach ($filas as $fila) {
            $sheet->setCellValue('A' . $filaExcel, $fila['actividad']);
            $sheet->setCellValue('B' . $filaExcel, $fila['cuenta_actividad']);
            $sheet->setCellValue('C' . $filaExcel, $fila['suma_agentes']);
            $sheet->setCellValue('D' . $filaExcel, $fila['suma_unidades']);
            $sheet->setCellValue('E' . $filaExcel, $fila['suma_kilometros']);
            $sheet->setCellValue('F' . $filaExcel, $fila['suma_personas_alcanzadas']);
            $filaExcel++;
        }

        $sheet->setCellValue('A' . $filaExcel, 'Suma total');
        $sheet->setCellValue('B' . $filaExcel, '=SUM(B2:B' . ($filaExcel - 1) . ')');
        $sheet->setCellValue('C' . $filaExcel, '=SUM(C2:C' . ($filaExcel - 1) . ')');
        $sheet->setCellValue('D' . $filaExcel, '=SUM(D2:D' . ($filaExcel - 1) . ')');
        $sheet->setCellValue('E' . $filaExcel, '=SUM(E2:E' . ($filaExcel - 1) . ')');
        $sheet->setCellValue('F' . $filaExcel, '=SUM(F2:F' . ($filaExcel - 1) . ')');

        $sheet->getColumnDimension('A')->setWidth(42);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);

        $sheet->getRowDimension(1)->setRowHeight(150);

        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_BOTTOM,
                'textRotation' => 90,
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

        $sheet->getStyle('A1')->getAlignment()->setTextRotation(0);
        $sheet->getStyle('A2:A' . $filaExcel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('B2:F' . $filaExcel)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:F' . $filaExcel)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A2:F' . $filaExcel)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D9D9D9'],
                ],
            ],
        ]);

        $sheet->getStyle('A' . $filaExcel . ':F' . $filaExcel)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F5F5F5'],
            ],
        ]);
    }

    protected function obtenerDatos(string $fecha): array
    {
        $orden = [
            'APOYO A EVENTOS PÚBLICOS',
            'APOYO A LA VIALIDAD',
            'APOYOS A OTRAS DEPENDENCIAS (Publicas o privadas)',
            'BANCOS',
            'CORTES DE CIRCULACIÓN',
            'DILIGENCIAS',
            'ESCUELAS',
            'OFICINAS GUBERNAMENTALES',
            'PASOS PEATONALES',
            'PATRULLAJES',
            'RECORRIDOS DE PROXIMIDAD',
            'SEGURIDAD EN CARRETERAS - SALAMANCA - MORELIA',
            'SEGURIDAD EN CARRETERAS - ZINAPECUARO - MORELIA',
            'TIENDAS DEPARTAMENTALES',
            'VÍAS FÉRREAS',
        ];

        $actividades = DB::table('actividades as a')
            ->leftJoin('actividad_subcategorias as s', 'a.actividad_subcategoria_id', '=', 's.id')
            ->select([
                'a.id',
                'a.fecha',
                'a.municipio',
                'a.personas_alcanzadas',
                'a.personas_participantes',
                'a.patrullas_participantes_texto',
                's.nombre as subcategoria',
            ])
            ->whereDate('a.fecha', $fecha)
            ->where(function ($query) {
                $query->where('a.municipio', 'MORELIA')
                    ->orWhere('a.municipio', 'Morelia');
            })
            ->whereIn('s.nombre', $orden)
            ->orderBy('a.id')
            ->get();

        $agrupado = [];

        foreach ($orden as $nombre) {
            $agrupado[$nombre] = [
                'actividad' => $nombre,
                'cuenta_actividad' => 0,
                'suma_agentes' => 0,
                'suma_unidades' => 0,
                'suma_kilometros' => 0,
                'suma_personas_alcanzadas' => 0,
            ];
        }

        foreach ($actividades as $actividad) {
            $subcategoria = $actividad->subcategoria;

            if (!isset($agrupado[$subcategoria])) {
                continue;
            }

            $agrupado[$subcategoria]['cuenta_actividad']++;
            $agrupado[$subcategoria]['suma_agentes'] += (int) ($actividad->personas_participantes ?? 0);
            $agrupado[$subcategoria]['suma_unidades'] += $this->contarUnidades($actividad->patrullas_participantes_texto);
            $agrupado[$subcategoria]['suma_personas_alcanzadas'] += (int) ($actividad->personas_alcanzadas ?? 0);
        }

        return array_values($agrupado);
    }

    protected function contarUnidades(?string $texto): int
    {
        if ($texto === null) {
            return 0;
        }

        $texto = trim($texto);

        if ($texto === '') {
            return 0;
        }

        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = str_replace(';', ',', $texto);
        $texto = preg_replace('/\s*\n\s*/', ',', $texto);
        $partes = array_filter(array_map('trim', explode(',', $texto)), fn ($valor) => $valor !== '');

        if (count($partes) > 0) {
            return count($partes);
        }

        return 1;
    }
}
