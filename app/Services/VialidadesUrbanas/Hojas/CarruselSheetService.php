<?php

namespace App\Services\VialidadesUrbanas\Hojas;

use App\Models\Actividad;
use App\Models\VialidadDispositivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CarruselSheetService
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

        $styleTitle = [
            'font' => [
                'bold' => true,
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
                'allBorders' => $doubleBorder,
            ],
        ];

        $styleHeader = [
            'font' => [
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
                'allBorders' => $doubleBorder,
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

        $styleRegion = $styleBody;
        $styleRegion['font']['bold'] = true;

        $styleTotal = [
            'font' => [
                'bold' => true,
                'italic' => true,
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
                'allBorders' => $doubleBorder,
            ],
        ];

        $sheet->getStyle('A1:V6')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
        ]);

        $sheet->mergeCells('B2:S2');
        $sheet->mergeCells('T2:V2');
        $sheet->setCellValue('B2', 'OPERATIVOS CARUSEL');
        $sheet->setCellValue('T2', 'DETENIDOS');
        $sheet->getStyle('B2:S2')->applyFromArray($styleTitle);
        $sheet->getStyle('T2:V2')->applyFromArray($styleTitle);
        $sheet->getRowDimension(2)->setRowHeight(32);

        $headers = [
            'B' => 'REGIÓN',
            'C' => 'UNIDAD',
            'D' => 'DISPOSITIVOS',
            'E' => 'PUESTOS DE CONTROL',
            'F' => 'UBICACIÓN',
            'G' => 'UNIDADES PARTICIPANTES',
            'H' => 'KILÓMETROS RECORRIDOS',
            'I' => 'CANTIDAD DE RECORRIDOS',
            'J' => 'TRAMO CARRETERO',
            'K' => 'ESTADO DE FUERZA',
            'L' => 'TIEMPO IMPLEMENTADO',
            'M' => 'APOYOS VIALES',
            'N' => 'APOYO A CARAVANAS',
            'O' => 'SERVICIO DE ESCOLTA',
            'P' => 'CONOCIMIENTO DE REPORTES DE ROBO',
            'Q' => 'ANTECEDENTES REVISADOS',
            'R' => 'AMONESTACIONES VERBALES',
            'S' => 'VEHÍCULOS RECUPERADOS',
            'T' => 'FUERO COMÚN',
            'U' => 'FUERO FEDERAL',
            'V' => 'JURÍDICO',
        ];

        foreach ($headers as $column => $label) {
            $sheet->setCellValue("{$column}3", $label);
        }

        $sheet->getStyle('B3:V3')->applyFromArray($styleHeader);
        $sheet->getStyle('D3:V3')->getAlignment()->setTextRotation(90);
        $sheet->getStyle('B3:C3')->getFont()->setSize(16);
        $sheet->getRowDimension(3)->setRowHeight(150);

        $resumen = $this->resumenCarrusel($inicio, $fin);

        $sheet->setCellValue('B4', 'MORELIA');
        $sheet->setCellValue('C4', 'VIALIDADES URBANAS');

        $columns = [
            'D' => 'dispositivos',
            'E' => 'puestos_control',
            'F' => 'ubicacion',
            'G' => 'unidades_participantes',
            'H' => 'kilometros_recorridos',
            'I' => 'cantidad_recorridos',
            'J' => 'tramo_carretero',
            'K' => 'estado_fuerza',
            'L' => 'tiempo_implementado',
            'M' => 'apoyos_viales',
            'N' => 'apoyo_caravanas',
            'O' => 'servicio_escolta',
            'P' => 'reportes_robo',
            'Q' => 'antecedentes_revisados',
            'R' => 'amonestaciones_verbales',
            'S' => 'vehiculos_recuperados',
            'T' => 'fuero_comun',
            'U' => 'fuero_federal',
            'V' => 'juridico',
        ];

        foreach ($columns as $column => $key) {
            $sheet->setCellValue("{$column}4", $resumen[$key] ?? 0);
        }

        $sheet->getStyle('B4:V4')->applyFromArray($styleBody);
        $sheet->getStyle('B4')->applyFromArray($styleRegion);
        $sheet->getRowDimension(4)->setRowHeight(34);

        $sheet->mergeCells('B5:C5');
        $sheet->setCellValue('B5', 'TOTAL');
        $sheet->setCellValue('D5', $resumen['dispositivos'] ?? 0);
        $sheet->getStyle('B5:D5')->applyFromArray($styleTotal);
        $sheet->getRowDimension(5)->setRowHeight(24);

        $this->renderReferencias($sheet, 7, $styleHeader, $styleBody);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(42);

        foreach (range('D', 'V') as $column) {
            $sheet->getColumnDimension($column)->setWidth($column === 'H' || $column === 'Q' ? 10 : 7);
        }

        $sheet->setShowGridlines(false);
    }

    private function renderReferencias(
        Worksheet $sheet,
        int $startRow,
        array $styleHeader,
        array $styleBody
    ): void {
        $sheet->setCellValue("B{$startRow}", 'UBICACIÓN');
        $sheet->setCellValue("C{$startRow}", 'NOMBRE');
        $sheet->getStyle("B{$startRow}:C{$startRow}")->applyFromArray($styleHeader);
        $sheet->getRowDimension($startRow)->setRowHeight(18);

        $row = $startRow + 1;

        foreach (range('A', 'L') as $ubicacion) {
            $sheet->setCellValue("B{$row}", $ubicacion);
            $sheet->setCellValue("C{$row}", '');
            $sheet->getStyle("B{$row}:C{$row}")->applyFromArray($styleBody);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        $sheet->setCellValue("B{$row}", 'TRAMO CARRETERO');
        $sheet->setCellValue("C{$row}", 'NOMBRE');
        $sheet->getStyle("B{$row}:C{$row}")->applyFromArray($styleHeader);
        $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(18);
        $row++;

        foreach (['Ñ', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'W', 'X', 'Y', 'Z'] as $tramo) {
            $sheet->setCellValue("B{$row}", $tramo);
            $sheet->setCellValue("C{$row}", '');
            $sheet->getStyle("B{$row}:C{$row}")->applyFromArray($styleBody);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }
    }

    private function resumenCarrusel(Carbon $inicio, Carbon $fin): array
    {
        $actividades = $this->actividadesCarrusel($inicio, $fin);
        $dispositivos = $this->dispositivosCarrusel($inicio, $fin);

        $resumen = [
            'dispositivos' => 0,
            'puestos_control' => 0,
            'ubicacion' => 0,
            'unidades_participantes' => 0,
            'kilometros_recorridos' => 0,
            'cantidad_recorridos' => 0,
            'tramo_carretero' => 0,
            'estado_fuerza' => 0,
            'tiempo_implementado' => 0,
            'apoyos_viales' => 0,
            'apoyo_caravanas' => 0,
            'servicio_escolta' => 0,
            'reportes_robo' => 0,
            'antecedentes_revisados' => 0,
            'amonestaciones_verbales' => 0,
            'vehiculos_recuperados' => 0,
            'fuero_comun' => 0,
            'fuero_federal' => 0,
            'juridico' => 0,
        ];

        foreach ($actividades as $actividad) {
            $cantidad = max((int) ($actividad->cantidad ?? 0), 1);
            $texto = $this->textoActividad($actividad);

            $resumen['dispositivos'] += $cantidad;
            $resumen['cantidad_recorridos'] += $cantidad;
            $resumen['kilometros_recorridos'] += (float) ($actividad->km_recorridos ?? 0);
            $resumen['unidades_participantes'] += $this->contarUnidadesTexto($actividad->patrullas_participantes_texto ?? '');
            $resumen['estado_fuerza'] += $this->contarCantidadTexto($actividad->elementos_participantes_texto ?? '');
            $resumen['tramo_carretero'] += trim((string) ($actividad->tramo ?? '')) !== '' ? 1 : 0;
            $resumen['puestos_control'] += $this->contiene($texto, ['PUESTO DE CONTROL', 'PUESTOS DE CONTROL']) ? $cantidad : 0;
            $resumen['apoyos_viales'] += $this->contiene($texto, ['APOYO VIAL', 'APOYOS VIALES']) ? $cantidad : 0;
            $resumen['apoyo_caravanas'] += $this->contiene($texto, ['CARAVANA', 'CARAVANAS']) ? $cantidad : 0;
            $resumen['servicio_escolta'] += $this->contiene($texto, ['ESCOLTA']) ? $cantidad : 0;
            $resumen['reportes_robo'] += $this->contiene($texto, ['REPORTE DE ROBO', 'REPORTES DE ROBO', 'ROBO']) ? $cantidad : 0;
            $resumen['antecedentes_revisados'] += $this->contiene($texto, ['ANTECEDENTE', 'ANTECEDENTES']) ? $cantidad : 0;
            $resumen['amonestaciones_verbales'] += $this->contiene($texto, ['AMONESTACION', 'AMONESTACIÓN', 'AMONESTACIONES']) ? $cantidad : 0;
            $resumen['vehiculos_recuperados'] += $this->contiene($texto, ['VEHICULO RECUPERADO', 'VEHÍCULO RECUPERADO', 'VEHICULOS RECUPERADOS', 'VEHÍCULOS RECUPERADOS']) ? $cantidad : 0;

            $detenidos = (int) ($actividad->personas_detenidas ?? 0);
            $resumen['fuero_comun'] += $this->contiene($texto, ['FUERO COMUN', 'FUERO COMÚN']) ? $detenidos : 0;
            $resumen['fuero_federal'] += $this->contiene($texto, ['FUERO FEDERAL']) ? $detenidos : 0;

            if ($detenidos > 0 && !$this->contiene($texto, ['FUERO COMUN', 'FUERO COMÚN', 'FUERO FEDERAL'])) {
                $resumen['juridico'] += $detenidos;
            } elseif ($this->contiene($texto, ['JURIDICO', 'JURÍDICO'])) {
                $resumen['juridico'] += $detenidos;
            }
        }

        foreach ($dispositivos as $dispositivo) {
            $texto = $this->textoDispositivo($dispositivo);

            $resumen['dispositivos']++;
            $resumen['unidades_participantes'] += (int) ($dispositivo->crp ?? 0)
                + (int) ($dispositivo->motopatrullas ?? 0)
                + (int) ($dispositivo->unidades_motorizadas ?? 0)
                + (int) ($dispositivo->patrullas ?? 0);
            $resumen['estado_fuerza'] += (int) ($dispositivo->elementos ?? 0);
            $resumen['puestos_control'] += $this->contiene($texto, ['PUESTO DE CONTROL', 'PUESTOS DE CONTROL']) ? 1 : 0;
            $resumen['apoyos_viales'] += $this->contiene($texto, ['APOYO VIAL', 'APOYOS VIALES']) ? 1 : 0;
            $resumen['apoyo_caravanas'] += $this->contiene($texto, ['CARAVANA', 'CARAVANAS']) ? 1 : 0;
            $resumen['servicio_escolta'] += $this->contiene($texto, ['ESCOLTA']) ? 1 : 0;
            $resumen['reportes_robo'] += $this->contiene($texto, ['REPORTE DE ROBO', 'REPORTES DE ROBO', 'ROBO']) ? 1 : 0;
            $resumen['antecedentes_revisados'] += $this->contiene($texto, ['ANTECEDENTE', 'ANTECEDENTES']) ? 1 : 0;
            $resumen['amonestaciones_verbales'] += $this->contiene($texto, ['AMONESTACION', 'AMONESTACIÓN', 'AMONESTACIONES']) ? 1 : 0;
            $resumen['vehiculos_recuperados'] += $this->contiene($texto, ['VEHICULO RECUPERADO', 'VEHÍCULO RECUPERADO', 'VEHICULOS RECUPERADOS', 'VEHÍCULOS RECUPERADOS']) ? 1 : 0;
        }

        $resumen['kilometros_recorridos'] = $this->numeroVisible($resumen['kilometros_recorridos']);

        return $resumen;
    }

    private function actividadesCarrusel(Carbon $inicio, Carbon $fin): Collection
    {
        return Actividad::query()
            ->with(['categoria', 'subcategoria'])
            ->where('unidad_org_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->whereHas('categoria', function ($query) {
                $query->whereRaw('UPPER(nombre) LIKE ?', ['%CARRUSEL%']);
            })
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function dispositivosCarrusel(Carbon $inicio, Carbon $fin): Collection
    {
        return VialidadDispositivo::query()
            ->with(['catalogo', 'detalles'])
            ->where('unidad_id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->where(function ($query) {
                $query->where('asunto', 'like', '%CARRUSEL%')
                    ->orWhere('descripcion', 'like', '%CARRUSEL%')
                    ->orWhere('narrativa', 'like', '%CARRUSEL%')
                    ->orWhereHas('catalogo', function ($catalogo) {
                        $catalogo->where('nombre', 'like', '%CARRUSEL%')
                            ->orWhere('slug', 'like', '%carrusel%');
                    });
            })
            ->get();
    }

    private function textoActividad($actividad): string
    {
        return $this->normalizar(implode(' ', array_filter([
            optional($actividad->categoria)->nombre,
            optional($actividad->subcategoria)->nombre,
            $actividad->nombre ?? null,
            $actividad->lugar ?? null,
            $actividad->tramo ?? null,
            $actividad->motivo ?? null,
            $actividad->narrativa ?? null,
            $actividad->acciones_realizadas ?? null,
            $actividad->observaciones ?? null,
        ])));
    }

    private function textoDispositivo($dispositivo): string
    {
        $detalles = $dispositivo->detalles
            ? $dispositivo->detalles->map(fn ($detalle) => trim(implode(' ', array_filter([
                $detalle->titulo ?? null,
                $detalle->contenido ?? null,
                $detalle->ubicacion ?? null,
            ]))))->implode(' ')
            : '';

        return $this->normalizar(implode(' ', array_filter([
            optional($dispositivo->catalogo)->nombre,
            $dispositivo->asunto ?? null,
            $dispositivo->lugar ?? null,
            $dispositivo->evento ?? null,
            $dispositivo->objetivo ?? null,
            $dispositivo->descripcion ?? null,
            $dispositivo->narrativa ?? null,
            $dispositivo->acciones_realizadas ?? null,
            $dispositivo->observaciones ?? null,
            $detalles,
        ])));
    }

    private function contiene(string $texto, array $palabras): bool
    {
        foreach ($palabras as $palabra) {
            if (str_contains($texto, $this->normalizar($palabra))) {
                return true;
            }
        }

        return false;
    }

    private function contarUnidadesTexto($texto): int
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return 0;
        }

        if (preg_match('/^\d+$/', $texto)) {
            return (int) $texto;
        }

        $partes = array_filter(array_map('trim', preg_split('/[\n,;|]+/', $texto) ?: []));

        if (count($partes) > 1) {
            return count($partes);
        }

        return 1;
    }

    private function contarCantidadTexto($texto): int
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return 0;
        }

        preg_match_all('/\d+/', $texto, $matches);

        if (!empty($matches[0])) {
            return array_sum(array_map('intval', $matches[0]));
        }

        return count(array_filter(preg_split('/[\s,;|]+/', $texto) ?: []));
    }

    private function numeroVisible($numero)
    {
        $numero = (float) $numero;

        if (floor($numero) === $numero) {
            return (int) $numero;
        }

        return round($numero, 2);
    }

    private function normalizar($texto): string
    {
        $texto = mb_strtoupper((string) $texto, 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/', ' ', trim($texto));
    }
}
