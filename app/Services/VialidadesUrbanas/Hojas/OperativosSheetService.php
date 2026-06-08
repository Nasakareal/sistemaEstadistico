<?php

namespace App\Services\VialidadesUrbanas\Hojas;

use App\Models\Actividad;
use App\Models\Unidad;
use App\Models\VialidadDispositivo;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OperativosSheetService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    public function generar(Worksheet $sheet, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $rows = $this->resumenOperativos($inicio, $fin);
        $this->render($sheet, $fecha, $rows);
    }

    private function render(Worksheet $sheet, string $fecha, array $rows): void
    {
        $navy = '002060';
        $cyan = '00B0F0';
        $green = '00B050';
        $outsideGray = 'A6A6A6';

        $doubleBorder = [
            'borderStyle' => Border::BORDER_DOUBLE,
            'color' => ['rgb' => '000000'],
        ];

        $thinBorder = [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000'],
        ];

        $sheet->setShowGridlines(false);
        $sheet->getSheetView()->setZoomScale(80);

        $sheet->getStyle('A1:Q14')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(18);
        $sheet->getRowDimension(2)->setRowHeight(14);
        $sheet->getRowDimension(3)->setRowHeight(38);
        $sheet->getRowDimension(4)->setRowHeight(170);

        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(52);

        foreach (range('E', 'Q') as $column) {
            $sheet->getColumnDimension($column)->setWidth(in_array($column, ['J', 'M', 'N'], true) ? 10 : 8);
        }

        $sheet->setAutoFilter('A1:Q1');
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);

        $sheet->getStyle('B2:Q2')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $navy],
            ],
            'borders' => [
                'allBorders' => $doubleBorder,
            ],
        ]);

        $sheet->mergeCells('B3:C3');
        $sheet->setCellValue('B3', 'TABLA DE OPERATIVOS');
        $sheet->setCellValue('D3', Carbon::parse($fecha)->format('d/m/Y'));
        $sheet->mergeCells('E3:J3');
        $sheet->setCellValue('E3', 'OPERATIVIDAD');
        $sheet->mergeCells('K3:L3');
        $sheet->setCellValue('K3', 'CORRALÓN');
        $sheet->mergeCells('M3:M4');
        $sheet->setCellValue('M3', 'AMONESTACIONES VERBALES');
        $sheet->mergeCells('N3:N4');
        $sheet->setCellValue('N3', 'VEHÍCULOS RECUPERADOS');
        $sheet->mergeCells('O3:Q3');
        $sheet->setCellValue('O3', 'DETENIDOS');

        $sheet->setCellValue('B4', 'UNIDAD');
        $sheet->setCellValue('C4', 'LUGAR');
        $sheet->setCellValue('D4', 'OPERATIVOS');

        $headers = [
            'E' => 'DISPOSITIVOS REALIZADOS',
            'F' => 'DESPOLARIZADOS',
            'G' => "ANTECEDENTES REVISADOS\nA PERSONAS",
            'H' => "ANTECEDENTES REVISADOS\nA VEHÍCULOS",
            'I' => "ANTECEDENTES REVISADOS\nA MOTOCICLETAS",
            'J' => "TOTAL ANTECEDENTES\nREVISADOS",
            'K' => 'VEHÍCULOS',
            'L' => 'MOTOS',
            'O' => 'FUERO COMÚN',
            'P' => 'FUERO FEDERAL',
            'Q' => 'JURÍDICO',
        ];

        foreach ($headers as $column => $header) {
            $sheet->setCellValue("{$column}4", $header);
        }

        $titleStyle = [
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

        $groupStyle = [
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
            'borders' => [
                'allBorders' => $doubleBorder,
            ],
        ];

        $plainHeaderStyle = [
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

        $sheet->getStyle('B3:D3')->applyFromArray($titleStyle);
        $sheet->getStyle('E3:J3')->applyFromArray($groupStyle);
        $sheet->getStyle('E3:J3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($cyan);
        $sheet->getStyle('K3:L3')->applyFromArray($groupStyle);
        $sheet->getStyle('K3:L3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($green);
        $sheet->getStyle('M3:N4')->applyFromArray($plainHeaderStyle);
        $sheet->getStyle('O3:Q3')->applyFromArray($plainHeaderStyle);
        $sheet->getStyle('B4:Q4')->applyFromArray($plainHeaderStyle);
        $sheet->getStyle('E4:Q4')->getAlignment()->setTextRotation(90);
        $sheet->getStyle('M3:N4')->getAlignment()->setTextRotation(90);

        $bodyStyle = [
            'font' => [
                'bold' => true,
                'size' => 10,
                'color' => ['rgb' => '000000'],
            ],
            'alignment' => [
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

        $rowNumber = 5;

        foreach ($this->templateOperativos() as $templateRow) {
            $key = $templateRow['key'];
            $data = $rows[$key] ?? $this->filaResumenVacia();
            $totalAntecedentes = (int) $data['antecedentes_personas']
                + (int) $data['antecedentes_vehiculos']
                + (int) $data['antecedentes_motos'];

            $sheet->setCellValue("B{$rowNumber}", 'VIALIDADES URBANAS');
            $sheet->setCellValue("C{$rowNumber}", '');
            $sheet->setCellValue("D{$rowNumber}", $templateRow['label']);
            $sheet->setCellValue("E{$rowNumber}", (int) $data['dispositivos_realizados']);
            $sheet->setCellValue("F{$rowNumber}", (int) $data['despolarizados']);
            $sheet->setCellValue("G{$rowNumber}", (int) $data['antecedentes_personas']);
            $sheet->setCellValue("H{$rowNumber}", (int) $data['antecedentes_vehiculos']);
            $sheet->setCellValue("I{$rowNumber}", (int) $data['antecedentes_motos']);
            $sheet->setCellValue("J{$rowNumber}", $totalAntecedentes);
            $sheet->setCellValue("K{$rowNumber}", $this->valorVisible($data['corralon_vehiculos']));
            $sheet->setCellValue("L{$rowNumber}", $this->valorVisible($data['corralon_motos']));
            $sheet->setCellValue("M{$rowNumber}", $this->valorVisible($data['amonestaciones_verbales']));
            $sheet->setCellValue("N{$rowNumber}", $this->valorVisible($data['vehiculos_recuperados']));
            $sheet->setCellValue("O{$rowNumber}", $this->valorVisible($data['fuero_comun']));
            $sheet->setCellValue("P{$rowNumber}", $this->valorVisible($data['fuero_federal']));
            $sheet->setCellValue("Q{$rowNumber}", $this->valorVisible($data['juridico']));

            $sheet->getStyle("B{$rowNumber}:Q{$rowNumber}")->applyFromArray($bodyStyle);
            $sheet->getStyle("B{$rowNumber}:C{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$rowNumber}:Q{$rowNumber}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension($rowNumber)->setRowHeight(18);

            $rowNumber++;
        }

        $sheet->freezePane('B5');
    }

    private function resumenOperativos(Carbon $inicio, Carbon $fin): array
    {
        $rows = [];

        foreach ($this->templateOperativos() as $row) {
            $rows[$row['key']] = $this->filaResumenVacia();
        }

        foreach ($this->actividadesVialidadesUrbanas($inicio, $fin) as $actividad) {
            $texto = $this->textoActividad($actividad);
            $key = $this->keyOperativoDesdeTexto($texto);

            if ($key === null) {
                continue;
            }

            $this->sumarActividad($rows[$key], $actividad, $texto);
        }

        foreach ($this->dispositivosVialidadesUrbanas($inicio, $fin) as $dispositivo) {
            $texto = $this->textoDispositivo($dispositivo);
            $key = $this->keyOperativoDesdeTexto($texto);

            if ($key === null) {
                continue;
            }

            $this->sumarDispositivo($rows[$key], $dispositivo, $texto);
        }

        return $rows;
    }

    private function sumarActividad(array &$row, $actividad, string $texto): void
    {
        $cantidad = $this->cantidadActividad($actividad);
        $row['dispositivos_realizados'] += $cantidad;
        $row['despolarizados'] += $this->cantidadIndicador($texto, ['DESPOLARIZADO', 'DESPOLARIZADOS', 'DESPOLARIZACION', 'DESPOLARIZACIÓN'], $cantidad);
        $row['amonestaciones_verbales'] += $this->cantidadIndicador($texto, ['AMONESTACION', 'AMONESTACIÓN', 'AMONESTACIONES'], $cantidad);
        $row['vehiculos_recuperados'] += $this->cantidadIndicador($texto, ['VEHICULO RECUPERADO', 'VEHÍCULO RECUPERADO', 'VEHICULOS RECUPERADOS', 'VEHÍCULOS RECUPERADOS', 'RECUPERADO CON REPORTE DE ROBO'], $cantidad);

        $antecedentes = $this->extraerAntecedentes($texto);
        $antecedentesVehiculos = 0;
        $antecedentesMotos = 0;

        foreach (($actividad->vehiculos ?? collect()) as $vehiculo) {
            $bucket = $this->bucketVehiculo($vehiculo->tipo ?? '');

            if ((int) ($vehiculo->antecedente_vehiculo ?? 0) === 1) {
                if ($bucket === 'motos') {
                    $antecedentesMotos++;
                } else {
                    $antecedentesVehiculos++;
                }
            }

            if ($this->vehiculoEnCorralon($vehiculo)) {
                if ($bucket === 'motos') {
                    $row['corralon_motos']++;
                } else {
                    $row['corralon_vehiculos']++;
                }
            }
        }

        $row['antecedentes_personas'] += $antecedentes['personas'];
        $row['antecedentes_vehiculos'] += max($antecedentes['vehiculos'], $antecedentesVehiculos);
        $row['antecedentes_motos'] += max($antecedentes['motos'], $antecedentesMotos);

        $this->sumarDetenidos($row, $texto, (int) ($actividad->personas_detenidas ?? 0));
    }

    private function sumarDispositivo(array &$row, $dispositivo, string $texto): void
    {
        $row['dispositivos_realizados']++;
        $row['despolarizados'] += $this->cantidadIndicador($texto, ['DESPOLARIZADO', 'DESPOLARIZADOS', 'DESPOLARIZACION', 'DESPOLARIZACIÓN'], 1);
        $row['amonestaciones_verbales'] += $this->cantidadIndicador($texto, ['AMONESTACION', 'AMONESTACIÓN', 'AMONESTACIONES'], 1);
        $row['vehiculos_recuperados'] += $this->cantidadIndicador($texto, ['VEHICULO RECUPERADO', 'VEHÍCULO RECUPERADO', 'VEHICULOS RECUPERADOS', 'VEHÍCULOS RECUPERADOS', 'RECUPERADO CON REPORTE DE ROBO'], 1);

        $antecedentes = $this->extraerAntecedentes($texto);
        $row['antecedentes_personas'] += $antecedentes['personas'];
        $row['antecedentes_vehiculos'] += $antecedentes['vehiculos'];
        $row['antecedentes_motos'] += $antecedentes['motos'];

        $row['corralon_motos'] += $this->cantidadCorralonTexto($texto, true);
        $row['corralon_vehiculos'] += $this->cantidadCorralonTexto($texto, false);

        $this->sumarDetenidos($row, $texto, 0);
    }

    private function sumarDetenidos(array &$row, string $texto, int $personas): void
    {
        if ($personas <= 0) {
            $personas = $this->contarPersonasAseguradasTexto($texto);
        }

        if ($personas <= 0) {
            return;
        }

        if ($this->contiene($texto, ['FUERO COMUN', 'FUERO COMÚN'])) {
            $row['fuero_comun'] += $personas;
            return;
        }

        if ($this->contiene($texto, ['FUERO FEDERAL'])) {
            $row['fuero_federal'] += $personas;
            return;
        }

        $row['juridico'] += $personas;
    }

    private function actividadesVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return Actividad::query()
            ->with(['categoria', 'subcategoria', 'vehiculos'])
            ->whereIn('unidad_org_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function dispositivosVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return VialidadDispositivo::query()
            ->with(['catalogo', 'detalles'])
            ->whereIn('unidad_id', $this->unidadVialidadesUrbanasIds())
            ->whereRaw(
                "TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) >= ? AND TIMESTAMP(DATE(fecha), COALESCE(hora, '00:00:00')) < ?",
                [$inicio->toDateTimeString(), $fin->toDateTimeString()]
            )
            ->get();
    }

    private function templateOperativos(): array
    {
        return [
            ['key' => 'relampago', 'label' => 'RELÁMPAGO', 'keywords' => ['RELAMPAGO', 'RELÁMPAGO']],
            ['key' => 'carrusel', 'label' => 'CARRUSEL', 'keywords' => ['CARRUSEL', 'CARUSEL']],
            ['key' => 'blindaje', 'label' => 'BLINDAJE', 'keywords' => ['BLINDAJE']],
            ['key' => 'concientizacion_motociclistas', 'label' => 'CONCIENTIZACIÓN A MOTOCICLISTAS', 'keywords' => ['CONCIENTIZACION A MOTOCICLISTAS', 'CONCIENTIZACIÓN A MOTOCICLISTAS', 'MOTOCICLISTAS']],
            ['key' => 'puesto_revision', 'label' => 'PUESTO DE REVISIÓN', 'keywords' => ['PUESTO DE REVISION', 'PUESTO DE REVISIÓN']],
            ['key' => 'puesto_control', 'label' => 'PUESTO DE CONTROL', 'keywords' => ['PUESTO DE CONTROL']],
            ['key' => 'apoyo_cootra', 'label' => 'APOYO COOTRA', 'keywords' => ['APOYO COOTRA', 'COOTRA']],
            ['key' => 'blindaje_estados', 'label' => 'BLINDAJE CON ESTADOS COLINDANTES', 'keywords' => ['BLINDAJE CON ESTADOS COLINDANTES', 'ESTADOS COLINDANTES']],
            ['key' => 'bases_operaciones', 'label' => 'BASES DE OPERACIONES INTERINSTITUCIONAL', 'keywords' => ['BASES DE OPERACIONES INTERINSTITUCIONAL', 'BASE DE OPERACIONES INTERINSTITUCIONAL', 'BOI']],
            ['key' => 'usda', 'label' => 'USDA', 'keywords' => ['USDA']],
        ];
    }

    private function keyOperativoDesdeTexto(string $texto): ?string
    {
        $prioridad = [
            'blindaje_estados',
            'puesto_control',
            'puesto_revision',
            'concientizacion_motociclistas',
            'bases_operaciones',
            'apoyo_cootra',
            'relampago',
            'carrusel',
            'blindaje',
            'usda',
        ];

        $templates = collect($this->templateOperativos())->keyBy('key');

        foreach ($prioridad as $key) {
            $template = $templates[$key] ?? null;

            if (!$template) {
                continue;
            }

            if ($this->contiene($texto, $template['keywords'])) {
                return $key;
            }
        }

        return null;
    }

    private function filaResumenVacia(): array
    {
        return [
            'dispositivos_realizados' => 0,
            'despolarizados' => 0,
            'antecedentes_personas' => 0,
            'antecedentes_vehiculos' => 0,
            'antecedentes_motos' => 0,
            'corralon_vehiculos' => 0,
            'corralon_motos' => 0,
            'amonestaciones_verbales' => 0,
            'vehiculos_recuperados' => 0,
            'fuero_comun' => 0,
            'fuero_federal' => 0,
            'juridico' => 0,
        ];
    }

    private function cantidadActividad($actividad): int
    {
        $cantidad = (int) ($actividad->cantidad ?? 0);

        return $cantidad > 0 ? $cantidad : 1;
    }

    private function cantidadIndicador(string $texto, array $palabras, int $fallback): int
    {
        $texto = $this->normalizar($texto);

        foreach ($palabras as $palabra) {
            $palabra = $this->normalizar($palabra);

            if ($palabra === '' || !str_contains($texto, $palabra)) {
                continue;
            }

            $cantidad = $this->cantidadCerca($texto, [$palabra], []);

            return $cantidad > 0 ? $cantidad : max($fallback, 1);
        }

        return 0;
    }

    private function extraerAntecedentes(string $texto): array
    {
        $texto = $this->normalizar($texto);

        $personas = $this->cantidadCerca($texto, ['ANTECEDENTE'], ['PERSONA', 'PERSONAS', 'DETENIDO', 'DETENIDOS', 'CIUDADANO', 'CIUDADANOS']);
        $vehiculos = $this->cantidadCerca($texto, ['ANTECEDENTE'], ['VEHICULO', 'VEHICULOS', 'VEHÍCULO', 'VEHÍCULOS', 'AUTO', 'AUTOS', 'CAMIONETA', 'CAMIONETAS']);
        $motos = $this->cantidadCerca($texto, ['ANTECEDENTE'], ['MOTO', 'MOTOS', 'MOTOCICLETA', 'MOTOCICLETAS']);

        if ($personas === 0 && $vehiculos === 0 && $motos === 0 && $this->contiene($texto, ['ANTECEDENTE', 'ANTECEDENTES'])) {
            $personas = 1;
        }

        return [
            'personas' => $personas,
            'vehiculos' => $vehiculos,
            'motos' => $motos,
        ];
    }

    private function cantidadCerca(string $texto, array $anchors, array $qualifiers): int
    {
        $total = 0;
        $encontrado = false;
        $qualifiers = array_map(fn ($value) => $this->normalizar($value), $qualifiers);

        foreach ($anchors as $anchor) {
            $anchor = $this->normalizar($anchor);
            $offset = 0;

            while ($anchor !== '' && ($pos = mb_strpos($texto, $anchor, $offset, 'UTF-8')) !== false) {
                $windowStart = max(0, $pos - 70);
                $window = mb_substr($texto, $windowStart, 150, 'UTF-8');
                $offset = $pos + mb_strlen($anchor, 'UTF-8');

                if (!empty($qualifiers) && !$this->contiene($window, $qualifiers)) {
                    continue;
                }

                $encontrado = true;
                preg_match_all('/\d+/', $window, $matches);
                $numeros = array_map('intval', $matches[0] ?? []);
                $numeros = array_values(array_filter($numeros, fn ($numero) => $numero > 0 && $numero < 10000));

                if (!empty($numeros)) {
                    $total += array_sum($numeros);
                }
            }
        }

        return $total > 0 ? $total : ($encontrado ? 1 : 0);
    }

    private function cantidadCorralonTexto(string $texto, bool $motos): int
    {
        if (!$this->contiene($texto, ['CORRALON', 'CORRALÓN'])) {
            return 0;
        }

        $palabras = $motos
            ? ['MOTO', 'MOTOS', 'MOTOCICLETA', 'MOTOCICLETAS']
            : ['VEHICULO', 'VEHICULOS', 'VEHÍCULO', 'VEHÍCULOS', 'AUTO', 'AUTOS', 'CAMIONETA', 'CAMIONETAS'];

        return $this->cantidadCerca($texto, ['CORRALON', 'CORRALÓN'], $palabras);
    }

    private function contarPersonasAseguradasTexto(string $texto): int
    {
        $texto = $this->normalizar($texto);

        if ($texto === '') {
            return 0;
        }

        $palabrasPersona = 'PERSONAS?|DETENIDOS?|DETENIDAS?|PRESENTADOS?|PRESENTADAS?|REMITIDOS?|REMITIDAS?|MASCULINOS?|FEMENINAS?';
        $total = 0;

        foreach ([
            '/(\d+)\s+(?:' . $palabrasPersona . ')/u',
            '/(?:' . $palabrasPersona . ')\D{0,20}(\d+)/u',
        ] as $pattern) {
            preg_match_all($pattern, $texto, $matches);
            $total += array_sum(array_map('intval', $matches[1] ?? []));
        }

        if ($total > 0) {
            return $total;
        }

        return $this->contiene($texto, [
            'PERSONA DETENIDA',
            'PERSONA PRESENTADA',
            'DETENIDO',
            'DETENIDA',
            'PRESENTADO AL MP',
            'PRESENTADA AL MP',
            'REMITIDO',
            'REMITIDA',
            'BARANDILLA',
            'ANTECEDENTES PENALES',
        ]) ? 1 : 0;
    }

    private function textoActividad($actividad): string
    {
        return $this->normalizar(implode(' ', array_filter([
            optional($actividad->categoria ?? null)->nombre,
            optional($actividad->subcategoria ?? null)->nombre,
            $actividad->nombre ?? null,
            $actividad->lugar ?? null,
            $actividad->municipio ?? null,
            $actividad->tramo ?? null,
            $actividad->motivo ?? null,
            $actividad->narrativa ?? null,
            $actividad->acciones_realizadas ?? null,
            $actividad->observaciones ?? null,
        ])));
    }

    private function textoDispositivo($dispositivo): string
    {
        $detalles = '';

        if (!empty($dispositivo->detalles)) {
            $detalles = collect($dispositivo->detalles)->map(function ($detalle) {
                return trim(implode(' ', array_filter([
                    $detalle->tipo ?? null,
                    $detalle->titulo ?? null,
                    $detalle->contenido ?? null,
                    $detalle->ubicacion ?? null,
                ])));
            })->implode(' ');
        }

        return $this->normalizar(implode(' ', array_filter([
            optional($dispositivo->catalogo ?? null)->nombre,
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

    private function bucketVehiculo($tipo): string
    {
        return $this->tipoGeneralVehiculo($tipo) === 'motocicleta' ? 'motos' : 'vehiculos';
    }

    private function tipoGeneralVehiculo($tipo): string
    {
        $tipo = $this->normalizar($tipo);

        if ($tipo === '') {
            return 'otros';
        }

        if ($this->contiene($tipo, ['MOTO', 'SCOOTER', 'MOTONETA', 'ENDURO', 'NAKED', 'PISTA', 'DOBLE PROPOSITO', 'CRUISER', 'CHOPPER', 'CUATRIMOTO'])) {
            return 'motocicleta';
        }

        return 'vehiculo';
    }

    private function vehiculoEnCorralon($vehiculo): bool
    {
        $corralon = trim((string) ($vehiculo->corralon ?? ''));

        if ($corralon === '') {
            return false;
        }

        return !in_array($this->normalizar($corralon), [
            'N/A',
            'NA',
            'NO',
            'NO APLICA',
            'NO APLICA.',
            'NINGUNO',
            'NULL',
            'SIN CORRALON',
            'SIN CORRALÓN',
            'NO TIENE CORRALON',
            'NO TIENE CORRALÓN',
            '-',
        ], true);
    }

    private function valorVisible($value)
    {
        return (int) $value > 0 ? (int) $value : null;
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

    private function unidadVialidadesUrbanasIds(): array
    {
        $ids = Unidad::query()
            ->where('id', self::UNIDAD_VIALIDADES_URBANAS_ID)
            ->orWhere(function ($query) {
                $query->where('nombre', 'like', '%VIALIDADES%')
                    ->where('nombre', 'like', '%URBANAS%');
            })
            ->orWhere('slug', 'like', '%vialidades%')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return $ids ?: [self::UNIDAD_VIALIDADES_URBANAS_ID];
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
