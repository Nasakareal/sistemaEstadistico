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

class CampanasSheetService
{
    private const UNIDAD_VIALIDADES_URBANAS_ID = 5;

    public function generar(Worksheet $sheet, string $fecha, Carbon $inicio, Carbon $fin): void
    {
        $this->render($sheet, $fecha, $this->resumenCampanas($inicio, $fin));
    }

    private function render(Worksheet $sheet, string $fecha, array $resumen): void
    {
        $navy = '002060';
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
        $sheet->getSheetView()->setZoomScale(100);

        $sheet->getStyle('A1:Q25')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(18);
        $sheet->getRowDimension(2)->setRowHeight(28);
        $sheet->getRowDimension(3)->setRowHeight(60);
        $sheet->getRowDimension(4)->setRowHeight(20);

        $widths = [
            'A' => 5,
            'B' => 24,
            'C' => 16,
            'D' => 14,
            'E' => 18,
            'F' => 22,
            'G' => 14,
            'H' => 14,
            'I' => 18,
            'J' => 18,
        ];

        foreach ($widths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->setAutoFilter('A1:J1');
        $sheet->getStyle('A1:J1')->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $outsideGray],
            ],
            'borders' => [
                'allBorders' => $thinBorder,
            ],
        ]);

        $sheet->setCellValue('B2', Carbon::parse($fecha)->format('d/m/Y'));
        $sheet->mergeCells('C2:J2');
        $sheet->setCellValue('C2', 'CAMPAÑAS');

        $sheet->getStyle('B2:J2')->applyFromArray([
            'font' => [
                'bold' => true,
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
        ]);
        $sheet->getStyle('B2')->getFont()->setSize(12);
        $sheet->getStyle('C2:J2')->getFont()->setSize(20);

        $headers = [
            'B' => 'UNIDAD',
            'C' => "CAMPAÑA\nCONCIENTIZACION\nY PREVENCION",
            'D' => "REPARTICIÓN\nDE TRIPTICOS",
            'E' => "ESTACIONALES\n(SEMANA SANTA,\nNAVIDAD ETC.)",
            'F' => "OTRAS (Especificar en\nlas novedades\nrelevantes)",
            'G' => 'ELEMENTOS',
            'H' => 'UNIDADES',
            'I' => "TOTAL, DE\nPERSONAS\nSENCIBILIZADAS",
            'J' => 'RECOMENDACIONES',
        ];

        foreach ($headers as $column => $header) {
            $sheet->setCellValue("{$column}3", $header);
        }

        $sheet->getStyle('B3:J3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 8,
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
        ]);

        $sheet->setCellValue('B4', 'VIALIDADES URBANAS');

        $values = [
            'C' => $resumen['concientizacion_prevencion'],
            'D' => $resumen['reparticion_tripticos'],
            'E' => $resumen['estacionales'],
            'F' => $resumen['otras'],
            'G' => $resumen['elementos'],
            'H' => $resumen['unidades'],
            'I' => $resumen['personas_sensibilizadas'],
            'J' => $resumen['recomendaciones'],
        ];

        foreach ($values as $column => $value) {
            $sheet->setCellValue("{$column}4", $this->numeroVisible($value));
        }

        $sheet->getStyle('B4:J4')->applyFromArray([
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
        ]);
    }

    private function resumenCampanas(Carbon $inicio, Carbon $fin): array
    {
        $resumen = $this->resumenVacio();

        foreach ($this->actividadesVialidadesUrbanas($inicio, $fin) as $actividad) {
            $texto = $this->textoActividad($actividad);

            if (!$this->esCampana($texto)) {
                continue;
            }

            $bucket = $this->bucketCampanaDesdeTexto($texto);
            $cantidad = $this->cantidadActividad($actividad);

            $resumen[$bucket] += $cantidad;
            $resumen['elementos'] += $this->contarCantidadTexto($actividad->elementos_participantes_texto ?? '');
            $resumen['unidades'] += $this->contarUnidadesTexto($actividad->patrullas_participantes_texto ?? '');
            $resumen['personas_sensibilizadas'] += $this->personasAlcanzadas($actividad)
                ?: $this->cantidadIndicador($texto, [
                    'PERSONAS SENSIBILIZADAS',
                    'PERSONAS SENCIBILIZADAS',
                    'PERSONAS ALCANZADAS',
                    'POBLACION ATENDIDA',
                    'POBLACIÓN ATENDIDA',
                ], 0);
            $resumen['recomendaciones'] += $this->contarRecomendaciones($actividad);
        }

        foreach ($this->dispositivosVialidadesUrbanas($inicio, $fin) as $dispositivo) {
            $texto = $this->textoDispositivo($dispositivo);

            if (!$this->esCampana($texto)) {
                continue;
            }

            $resumen[$this->bucketCampanaDesdeTexto($texto)]++;
            $resumen['elementos'] += (int) ($dispositivo->elementos ?? 0);
            $resumen['unidades'] += $this->unidadesDispositivo($dispositivo);
            $resumen['personas_sensibilizadas'] += $this->cantidadIndicador($texto, [
                'PERSONAS SENSIBILIZADAS',
                'PERSONAS SENCIBILIZADAS',
                'PERSONAS ALCANZADAS',
                'POBLACION ATENDIDA',
                'POBLACIÓN ATENDIDA',
            ], 0);
            $resumen['recomendaciones'] += $this->contarRecomendaciones($dispositivo);
        }

        return $resumen;
    }

    private function resumenVacio(): array
    {
        return [
            'concientizacion_prevencion' => 0,
            'reparticion_tripticos' => 0,
            'estacionales' => 0,
            'otras' => 0,
            'elementos' => 0,
            'unidades' => 0,
            'personas_sensibilizadas' => 0,
            'recomendaciones' => 0,
        ];
    }

    private function actividadesVialidadesUrbanas(Carbon $inicio, Carbon $fin): Collection
    {
        return Actividad::query()
            ->with(['categoria', 'subcategoria', 'fomentoCulturaVialDetalle.programa'])
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

    private function esCampana(string $texto): bool
    {
        return $this->contiene($texto, [
            'CAMPANA',
            'CAMPANAS',
            'CAMPAÑA',
            'CAMPAÑAS',
            'CONCIENTIZACION',
            'CONCIENTIZACIÓN',
            'PREVENCION',
            'PREVENCIÓN',
            'TRIPTICO',
            'TRÍPTICO',
            'ESTACIONAL',
            'SEMANA SANTA',
            'NAVIDAD',
        ]);
    }

    private function bucketCampanaDesdeTexto(string $texto): string
    {
        if ($this->contiene($texto, ['TRIPTICO', 'TRÍPTICO', 'TRIPTICOS', 'TRÍPTICOS'])) {
            return 'reparticion_tripticos';
        }

        if ($this->contiene($texto, ['ESTACIONAL', 'SEMANA SANTA', 'NAVIDAD', 'VACACIONAL', 'DIA DE MUERTOS', 'DÍA DE MUERTOS'])) {
            return 'estacionales';
        }

        if ($this->contiene($texto, ['CONCIENTIZACION', 'CONCIENTIZACIÓN', 'PREVENCION', 'PREVENCIÓN'])) {
            return 'concientizacion_prevencion';
        }

        return 'otras';
    }

    private function cantidadActividad($actividad): int
    {
        $cantidad = (int) ($actividad->cantidad ?? 0);

        return $cantidad > 0 ? $cantidad : 1;
    }

    private function personasAlcanzadas($actividad): int
    {
        $detalle = $actividad->fomentoCulturaVialDetalle ?? null;
        $detalleTotal = (int) ($detalle->total_poblacion_atendida ?? 0);

        return $detalleTotal > 0
            ? $detalleTotal
            : (int) ($actividad->personas_alcanzadas ?? 0);
    }

    private function unidadesDispositivo($dispositivo): int
    {
        return (int) ($dispositivo->crp ?? 0)
            + (int) ($dispositivo->motopatrullas ?? 0)
            + (int) ($dispositivo->fenix ?? 0)
            + (int) ($dispositivo->unidades_motorizadas ?? 0)
            + (int) ($dispositivo->patrullas ?? 0)
            + (int) ($dispositivo->gruas ?? 0)
            + (int) ($dispositivo->otros_apoyos ?? 0);
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

    private function contarUnidadesTexto($texto): int
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return 0;
        }

        if (preg_match('/^\d+$/', $texto)) {
            $numero = (int) $texto;

            if ($numero === 0) {
                return 0;
            }

            return $numero <= 100 ? $numero : 1;
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

    private function contarRecomendaciones($registro): int
    {
        $texto = $this->normalizar(implode(' ', array_filter([
            $registro->motivo ?? null,
            $registro->narrativa ?? null,
            $registro->acciones_realizadas ?? null,
            $registro->observaciones ?? null,
        ])));

        if ($texto === '') {
            return 0;
        }

        preg_match_all('/RECOMENDACION(?:ES)?\D{0,20}(\d+)/u', $texto, $matches);

        if (!empty($matches[1])) {
            return array_sum(array_map('intval', $matches[1]));
        }

        return 0;
    }

    private function textoActividad($actividad): string
    {
        $detalle = $actividad->fomentoCulturaVialDetalle ?? null;

        return $this->normalizar(implode(' ', array_filter([
            optional($actividad->categoria ?? null)->nombre,
            optional($actividad->subcategoria ?? null)->nombre,
            optional(optional($detalle)->programa ?? null)->nombre,
            $detalle->programa_nombre ?? null,
            $detalle->nivel_educativo ?? null,
            $detalle->sector ?? null,
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

    private function numeroVisible($numero)
    {
        $numero = (float) $numero;

        if (floor($numero) === $numero) {
            return (int) $numero;
        }

        return round($numero, 2);
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
