<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use ZipArchive;

class SeguridadVialPowerPointService
{
    private const SLIDE_W = 12192000;
    private const SLIDE_H = 6858000;
    private const EMU = 914400;

    private int $shapeId = 2;

    public function generar(array $reporte): string
    {
        $slides = [
            $this->buildSlide(fn () => $this->slidePortada($reporte)),
            $this->buildSlide(fn () => $this->slideMunicipios($reporte)),
            $this->buildSlide(fn () => $this->slideMapaMorelia($reporte)),
            $this->buildSlide(fn () => $this->slideResumen($reporte)),
            $this->buildSlide(fn () => $this->slideTemporal($reporte)),
            $this->buildSlide(fn () => $this->slideOperativa($reporte)),
        ];

        $path = storage_path('app/temp_seguridad_vial_' . uniqid('', true) . '.pptx');

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('No se pudo crear el archivo PowerPoint.');
        }

        $this->writePackage($zip, $slides);
        $zip->close();

        return $path;
    }

    private function buildSlide(callable $builder): string
    {
        $this->shapeId = 2;

        return $this->wrapSlide($builder());
    }

    private function slidePortada(array $reporte): string
    {
        $periodo = $this->periodoTexto($reporte);

        return implode('', [
            $this->rect(0, 0, 13.333, 7.5, 'F7FAFC'),
            $this->rect(0, 0, 13.333, .22, '0F766E'),
            $this->rect(0, 7.18, 13.333, .32, 'C99743'),
            $this->text(.58, .62, 3.2, .5, 'SECRETARIA DE SEGURIDAD PUBLICA', 11, '214269', true),
            $this->text(9.15, .62, 3.55, .5, 'GOBIERNO DE MICHOACAN', 11, '214269', true, 'r'),
            $this->text(.9, 1.56, 11.55, .42, 'INFORME INSTITUCIONAL', 15, '0F766E', true, 'c'),
            $this->text(.9, 2.15, 11.55, 1.48, "INFORME DE\nSEGURIDAD VIAL", 50, '0B2B61', true, 'c'),
            $this->rect(5.7, 3.88, 1.95, .08, '0F766E', true),
            $this->text(1.35, 4.24, 10.65, .55, $periodo, 24, '12366F', true, 'c'),
            $this->text(2.05, 4.9, 9.25, .36, 'CONSOLIDADO GENERAL DE TODAS LAS UNIDADES', 14, '365A7D', true, 'c'),
            $this->pill(4.76, 5.78, 3.82, .58, '0F766E', 'PRESENTACION OFFLINE', 16, 'FFFFFF'),
        ]);
    }

    private function slideMunicipios(array $reporte): string
    {
        $ranking = collect($reporte['ranking_municipios'] ?? [])->take(9)->values();
        $kpis = $reporte['kpis'] ?? [];

        $content = [
            $this->slideBackground(),
            $this->slideHeader('Comparativa municipal', 'Municipios con mayor incidencia', $this->periodoTexto($reporte)),
            $this->text(10.35, .62, 2.2, .36, 'TOTAL', 12, '64748B', true, 'r'),
            $this->text(10.3, .94, 2.2, .66, $this->num($kpis['total_hechos'] ?? 0), 34, 'DC2626', true, 'r'),
            $this->text(10.3, 1.55, 2.2, .26, 'SINIESTROS', 10, '64748B', true, 'r'),
        ];

        $max = max(1, (int) $ranking->max('hechos'));
        $y = 1.62;
        $colors = ['1D4ED8', 'DC2626', 'D97706', '059669', '7C3AED', '0F766E', '334155', 'BE123C', '2563EB'];

        foreach ($ranking as $index => $item) {
            $value = (int) ($item['hechos'] ?? 0);
            $barW = 5.75 * ($value / $max);
            $color = $colors[$index % count($colors)];

            $content[] = $this->text(.72, $y + .04, .38, .28, (string) ($index + 1), 10, 'FFFFFF', true, 'c', '0F172A');
            $content[] = $this->text(1.22, $y, 2.35, .35, $this->short($item['municipio'] ?? 'SIN MUNICIPIO', 24), 14, '0F172A', true);
            $content[] = $this->rect(3.75, $y + .07, 6.0, .22, 'E2E8F0', true);
            $content[] = $this->rect(3.75, $y + .07, max(.08, $barW), .22, $color, true);
            $content[] = $this->text(9.95, $y - .02, .65, .35, $this->num($value), 16, $color, true, 'r');
            $content[] = $this->text(10.8, $y, 1.38, .3, number_format((float) ($item['participacion'] ?? 0), 1) . '%', 12, '475569', true);
            $y += .5;
        }

        $content[] = $this->rect(.72, 6.38, 11.9, .02, 'CBD5E1');
        $content[] = $this->text(.72, 6.5, 11.9, .3, 'Ranking por numero de siniestros registrados en el periodo filtrado.', 10, '64748B', false, 'c');

        return implode('', $content);
    }

    private function slideMapaMorelia(array $reporte): string
    {
        $mapa = $reporte['mapa_morelia'] ?? [];
        $puntos = $this->values($mapa['puntos'] ?? []);
        $totales = $mapa['totales'] ?? [];
        $bounds = $this->mapBounds($puntos);
        $maxTotal = max(1, (int) collect($puntos)->max('total'));
        $mapX = .82;
        $mapY = 1.55;
        $mapW = 8.35;
        $mapH = 4.88;

        $content = [
            $this->slideBackground(),
            $this->slideHeader('Lectura territorial', 'Mapa de calor Morelia', $this->periodoTexto($reporte)),
            $this->rect($mapX, $mapY, $mapW, $mapH, 'EEF6FF', true, 'CBD5E1'),
            $this->rect($mapX + .22, $mapY + .2, $mapW - .44, $mapH - .4, 'F8FAFC', true, 'E2E8F0'),
        ];

        foreach ([.25, .5, .75] as $ratio) {
            $content[] = $this->rect($mapX + .22 + (($mapW - .44) * $ratio), $mapY + .2, .01, $mapH - .4, 'D9E6F3');
            $content[] = $this->rect($mapX + .22, $mapY + .2 + (($mapH - .4) * $ratio), $mapW - .44, .01, 'D9E6F3');
        }

        $content[] = $this->text($mapX + .44, $mapY + .34, 1.7, .26, 'MORELIA', 12, '64748B', true);
        $content[] = $this->text($mapX + 5.92, $mapY + 4.18, 2.65, .24, 'Concentracion por coordenadas', 9.5, '64748B', true, 'r');

        if (count($puntos) === 0) {
            $content[] = $this->text($mapX + 1.2, $mapY + 2.1, 5.9, .46, 'SIN COORDENADAS DE MORELIA EN EL PERIODO', 18, '64748B', true, 'c');
        }

        foreach (array_slice($puntos, 0, 28) as $punto) {
            if (!is_numeric($punto['lat'] ?? null) || !is_numeric($punto['lng'] ?? null)) {
                continue;
            }

            [$px, $py] = $this->mapPoint((float) $punto['lat'], (float) $punto['lng'], $bounds, $mapX + .22, $mapY + .2, $mapW - .44, $mapH - .4);
            $ratio = sqrt(max(1, (int) ($punto['total'] ?? 1)) / $maxTotal);
            $size = .18 + (.54 * $ratio);
            $color = $this->heatPointColor($punto);

            $content[] = $this->ellipse($px - $size, $py - $size, $size * 2, $size * 2, $color, null, 26000);
            $content[] = $this->ellipse($px - ($size * .48), $py - ($size * .48), $size * .96, $size * .96, $color, 'FFFFFF', 86000);
        }

        $panelX = 9.48;
        $content[] = $this->rect($panelX, 1.55, 2.92, 4.88, 'FFFFFF', true, 'E2E8F0');
        $content[] = $this->text($panelX + .24, 1.82, 2.38, .26, 'MORELIA', 11, '64748B', true);
        $content[] = $this->text($panelX + .24, 2.08, 2.38, .42, $this->num($totales['hechos'] ?? 0), 30, '0F172A', true);
        $content[] = $this->text($panelX + .24, 2.5, 2.38, .23, 'SINIESTROS CON COORDENADAS', 8.5, '64748B', true);

        $rows = [
            ['Fallecidos', $totales['fallecidos'] ?? 0, 'DC2626'],
            ['Lesionados', $totales['lesionados'] ?? 0, 'D97706'],
            ['Choques normales', $totales['choques'] ?? 0, '2563EB'],
            ['Puntos de calor', $totales['puntos'] ?? 0, '0F766E'],
        ];

        $y = 3.05;
        foreach ($rows as $row) {
            $content[] = $this->rect($panelX + .24, $y + .05, .14, .14, $row[2], true);
            $content[] = $this->text($panelX + .48, $y, 1.48, .24, $row[0], 10, '334155', true);
            $content[] = $this->text($panelX + 2.05, $y - .01, .48, .26, $this->num($row[1]), 14, $row[2], true, 'r');
            $y += .45;
        }

        $content[] = $this->rect($panelX + .24, 5.22, 2.42, .02, 'E2E8F0');
        $content[] = $this->text($panelX + .24, 5.42, 2.42, .38, 'Color por severidad y tamano por concentracion.', 10.5, '475569', false);
        $content[] = $this->text(.82, 6.58, 11.58, .28, 'Mapa estatico generado desde latitud/longitud de hechos registrados en Morelia.', 10, '64748B', false, 'c');

        return implode('', $content);
    }

    private function slideResumen(array $reporte): string
    {
        $k = $reporte['kpis'] ?? [];
        $metrics = [
            ['Siniestros', $k['total_hechos'] ?? 0, '1D4ED8'],
            ['Lesionados', $k['total_lesionados'] ?? 0, 'D97706'],
            ['Fallecidos', $k['total_fallecidos'] ?? 0, 'DC2626'],
            ['Vehiculos', $k['total_vehiculos'] ?? 0, '059669'],
            ['Municipios', $k['municipios_con_hechos'] ?? 0, '7C3AED'],
            ['Promedio diario', $k['promedio_diario'] ?? 0, '334155'],
        ];

        $content = [
            $this->slideBackground(),
            $this->slideHeader('Consolidado general', 'Indicadores de seguridad vial', $this->periodoTexto($reporte)),
        ];

        foreach ($metrics as $i => $metric) {
            $col = $i % 3;
            $row = intdiv($i, 3);
            $x = .82 + ($col * 4.15);
            $y = 1.72 + ($row * 1.68);

            $content[] = $this->rect($x, $y, 3.55, 1.25, 'FFFFFF', true, 'E2E8F0');
            $content[] = $this->rect($x, $y, .16, 1.25, $metric[2], true);
            $content[] = $this->text($x + .34, $y + .16, 2.8, .32, mb_strtoupper($metric[0], 'UTF-8'), 12, '475569', true);
            $content[] = $this->text($x + .34, $y + .48, 2.8, .56, $this->num($metric[1]), 34, '102A43', true);
        }

        $content[] = $this->rect(.82, 5.35, 11.58, .85, 'ECFEFF', true, 'A7F3D0');
        $content[] = $this->text(1.08, 5.5, 5.6, .28, 'MUNICIPIO CON MAYOR INCIDENCIA', 11, '64748B', true);
        $content[] = $this->text(1.08, 5.8, 6.8, .38, $this->short($k['municipio_principal'] ?? 'SIN DATOS', 38), 22, '0F172A', true);
        $content[] = $this->text(10.35, 5.52, 1.65, .62, $this->num($k['municipio_principal_total'] ?? 0), 42, 'DC2626', true, 'r');

        return implode('', $content);
    }

    private function slideTemporal(array $reporte): string
    {
        $k = $reporte['kpis'] ?? [];
        $diaLabels = $this->values($reporte['graficas']['por_dia']['labels'] ?? []);
        $diaSeries = $this->values($reporte['graficas']['por_dia']['series'] ?? []);
        $horaLabels = $this->values($reporte['graficas']['por_hora']['labels'] ?? []);
        $horaSeries = $this->values($reporte['graficas']['por_hora']['series'] ?? []);

        $content = [
            $this->slideBackground(),
            $this->slideHeader('Lectura temporal', 'Distribucion semanal y horaria', $this->periodoTexto($reporte)),
            $this->insight(.82, 1.42, 'DIA DE LA SEMANA', $k['dia_pico'] ?? 'SIN DATOS', $k['dia_pico_total'] ?? 0),
            $this->insight(4.68, 1.42, 'HORA CRITICA', $k['hora_pico'] ?? 'SIN HORA', $k['hora_pico_total'] ?? 0),
            $this->insight(8.54, 1.42, 'PROMEDIO DIARIO', $this->num($k['promedio_diario'] ?? 0), 'HECHOS'),
            $this->text(.82, 2.62, 5.4, .32, 'Siniestros por dia de la semana', 18, '0F172A', true),
            $this->text(6.8, 2.62, 5.4, .32, 'Siniestros por hora', 18, '0F172A', true),
        ];

        $content = array_merge($content, $this->verticalBars(.82, 3.05, 5.6, 2.5, $diaLabels, $diaSeries, '1D4ED8'));
        $content = array_merge($content, $this->hourGrid(6.78, 3.08, 5.55, 2.62, $horaLabels, $horaSeries));

        return implode('', $content);
    }

    private function slideOperativa(array $reporte): string
    {
        $k = $reporte['kpis'] ?? [];
        $tipoLabels = $this->values($reporte['graficas']['por_tipo']['labels'] ?? []);
        $tipoSeries = $this->values($reporte['graficas']['por_tipo']['series'] ?? []);
        $situacionLabels = $this->values($reporte['graficas']['por_situacion']['labels'] ?? []);
        $situacionSeries = $this->values($reporte['graficas']['por_situacion']['series'] ?? []);

        $content = [
            $this->slideBackground(),
            $this->slideHeader('Lectura operativa', 'Tipo de siniestro y estatus', $this->periodoTexto($reporte)),
            $this->text(.82, 1.42, 5.9, .28, 'TIPO PRINCIPAL', 11, '64748B', true),
            $this->text(.82, 1.72, 5.9, .38, $this->short($k['tipo_principal'] ?? 'SIN DATOS', 42), 18, '0F172A', true),
            $this->text(5.82, 1.58, .92, .48, $this->num($k['tipo_principal_total'] ?? 0), 28, 'DC2626', true, 'r'),
            $this->text(.82, 2.42, 6.1, .28, 'Top 10 por tipo', 16, '0F172A', true),
            $this->text(7.32, 1.42, 4.8, .28, 'Situacion reportada', 16, '0F172A', true),
        ];

        $content = array_merge($content, $this->horizontalBars(.82, 2.82, 6.35, 3.18, $tipoLabels, $tipoSeries));
        $content = array_merge($content, $this->stackedStatus(7.32, 2.02, 4.65, 3.7, $situacionLabels, $situacionSeries));

        return implode('', $content);
    }

    private function slideHeader(string $eyebrow, string $title, string $periodo): string
    {
        return implode('', [
            $this->text(.7, .48, 5.7, .32, mb_strtoupper($eyebrow, 'UTF-8'), 11, '64748B', true),
            $this->text(.7, .84, 7.8, .5, $title, 26, '0F172A', true),
            $this->text(.7, 1.28, 8.4, .28, mb_strtoupper($periodo, 'UTF-8'), 11, '475569', true),
        ]);
    }

    private function insight(float $x, float $y, string $label, $value, $number): string
    {
        return implode('', [
            $this->rect($x, $y, 3.42, .78, 'ECFEFF', true, 'BAE6FD'),
            $this->text($x + .18, $y + .12, 2.05, .22, $label, 9, '64748B', true),
            $this->text($x + .18, $y + .38, 2.08, .26, (string) $value, 16, '0F172A', true),
            $this->text($x + 2.45, $y + .18, .72, .38, (string) $number, 22, 'DC2626', true, 'r'),
        ]);
    }

    private function horizontalBars(float $x, float $y, float $w, float $h, array $labels, array $values): array
    {
        $content = [];
        $max = max(1, (int) max($values ?: [1]));
        $rowH = min(.31, $h / max(1, count($labels)));
        $colors = ['1D4ED8', 'DC2626', 'D97706', '059669', '7C3AED', '0F766E', '334155', 'BE123C', '2563EB', 'CA8A04'];

        foreach (array_slice($labels, 0, 10) as $i => $label) {
            $value = (int) ($values[$i] ?? 0);
            $yy = $y + ($i * ($rowH + .04));
            $barW = ($w - 3.1) * ($value / $max);
            $color = $colors[$i % count($colors)];

            $content[] = $this->text($x, $yy, 2.35, .22, $this->short($label, 32), 8.6, '334155', true);
            $content[] = $this->rect($x + 2.52, $yy + .05, $w - 3.2, .13, 'E2E8F0', true);
            $content[] = $this->rect($x + 2.52, $yy + .05, max(.05, $barW), .13, $color, true);
            $content[] = $this->text($x + $w - .55, $yy - .01, .42, .2, $this->num($value), 9, $color, true, 'r');
        }

        return $content;
    }

    private function verticalBars(float $x, float $y, float $w, float $h, array $labels, array $values, string $color): array
    {
        $content = [];
        $max = max(1, (int) max($values ?: [1]));
        $count = max(1, count($labels));
        $gap = .08;
        $barSlot = $w / $count;
        $barW = max(.16, $barSlot - $gap);

        $content[] = $this->rect($x, $y + $h, $w, .02, 'CBD5E1');

        foreach ($labels as $i => $label) {
            $value = (int) ($values[$i] ?? 0);
            $barH = max(.04, ($h - .55) * ($value / $max));
            $xx = $x + ($i * $barSlot) + ($gap / 2);
            $yy = $y + ($h - .08) - $barH;

            $content[] = $this->rect($xx, $yy, $barW, $barH, $color, true);
            $content[] = $this->text($xx - .04, $yy - .24, $barW + .08, .2, $this->num($value), 9, '0F172A', true, 'c');
            $content[] = $this->text($xx - .06, $y + $h + .08, $barW + .12, .24, $this->short($label, 8), 8, '475569', true, 'c');
        }

        return $content;
    }

    private function hourGrid(float $x, float $y, float $w, float $h, array $labels, array $values): array
    {
        $content = [];
        $max = max(1, (int) max($values ?: [1]));
        $cellW = $w / 12;
        $cellH = $h / 2;

        foreach (range(0, 23) as $i) {
            $row = intdiv($i, 12);
            $col = $i % 12;
            $value = (int) ($values[$i] ?? 0);
            $ratio = $value / $max;
            $fill = $ratio >= .75 ? '0F766E' : ($ratio >= .5 ? '14B8A6' : ($ratio > 0 ? 'A7F3D0' : 'F1F5F9'));
            $text = $ratio >= .5 ? 'FFFFFF' : '0F172A';
            $xx = $x + ($col * $cellW);
            $yy = $y + ($row * $cellH);

            $content[] = $this->rect($xx + .03, $yy + .04, $cellW - .06, $cellH - .08, $fill, true, 'FFFFFF');
            $content[] = $this->text($xx + .04, $yy + .16, $cellW - .08, .18, substr((string) ($labels[$i] ?? sprintf('%02d:00', $i)), 0, 2), 8, $text, true, 'c');
            $content[] = $this->text($xx + .04, $yy + .45, $cellW - .08, .24, $this->num($value), 14, $text, true, 'c');
        }

        return $content;
    }

    private function stackedStatus(float $x, float $y, float $w, float $h, array $labels, array $values): array
    {
        $content = [];
        $total = max(1, array_sum(array_map('intval', $values)));
        $colors = ['059669', 'D97706', 'DC2626', '1D4ED8', '64748B'];
        $currentX = $x;

        $content[] = $this->rect($x, $y, $w, .46, 'E2E8F0', true);

        foreach ($labels as $i => $label) {
            $value = (int) ($values[$i] ?? 0);
            $segmentW = $w * ($value / $total);
            $color = $colors[$i % count($colors)];

            if ($segmentW > 0) {
                $content[] = $this->rect($currentX, $y, max(.04, $segmentW), .46, $color, true);
            }

            $currentX += $segmentW;
        }

        $yy = $y + .82;
        foreach ($labels as $i => $label) {
            $value = (int) ($values[$i] ?? 0);
            $pct = round(($value / $total) * 100, 1);
            $color = $colors[$i % count($colors)];

            $content[] = $this->rect($x, $yy + .05, .16, .16, $color, true);
            $content[] = $this->text($x + .26, $yy, 2.7, .24, $this->short($label, 26), 10.5, '334155', true);
            $content[] = $this->text($x + 3.05, $yy, .7, .24, $this->num($value), 11, $color, true, 'r');
            $content[] = $this->text($x + 3.85, $yy, .7, .24, $pct . '%', 10, '64748B', true, 'r');
            $yy += .45;
        }

        $content[] = $this->text($x, $y + $h - .38, $w, .28, 'Total: ' . $this->num($total) . ' siniestros', 15, '0F172A', true, 'c');

        return $content;
    }

    private function slideBackground(): string
    {
        return implode('', [
            $this->rect(0, 0, 13.333, 7.5, 'F8FAFC'),
            $this->rect(0, 0, 13.333, .18, '0F766E'),
            $this->rect(0, 7.26, 13.333, .24, '0B2B61'),
        ]);
    }

    private function rect(float $x, float $y, float $w, float $h, string $fill, bool $round = false, ?string $line = null): string
    {
        $id = $this->shapeId++;
        $geom = $round ? 'roundRect' : 'rect';
        $lineXml = $line
            ? '<a:ln w="9525"><a:solidFill><a:srgbClr val="' . $line . '"/></a:solidFill></a:ln>'
            : '<a:ln><a:noFill/></a:ln>';

        return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="Shape ' . $id . '"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="' . $this->emu($x) . '" y="' . $this->emu($y) . '"/><a:ext cx="' . $this->emu($w) . '" cy="' . $this->emu($h) . '"/></a:xfrm><a:prstGeom prst="' . $geom . '"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val="' . $fill . '"/></a:solidFill>' . $lineXml . '</p:spPr></p:sp>';
    }

    private function ellipse(float $x, float $y, float $w, float $h, string $fill, ?string $line = null, int $alpha = 100000): string
    {
        $id = $this->shapeId++;
        $alpha = max(0, min(100000, $alpha));
        $lineXml = $line
            ? '<a:ln w="9525"><a:solidFill><a:srgbClr val="' . $line . '"/></a:solidFill></a:ln>'
            : '<a:ln><a:noFill/></a:ln>';

        return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="Heat ' . $id . '"/><p:cNvSpPr/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="' . $this->emu($x) . '" y="' . $this->emu($y) . '"/><a:ext cx="' . $this->emu($w) . '" cy="' . $this->emu($h) . '"/></a:xfrm><a:prstGeom prst="ellipse"><a:avLst/></a:prstGeom><a:solidFill><a:srgbClr val="' . $fill . '"><a:alpha val="' . $alpha . '"/></a:srgbClr></a:solidFill>' . $lineXml . '</p:spPr></p:sp>';
    }

    private function pill(float $x, float $y, float $w, float $h, string $fill, string $text, int $size, string $color): string
    {
        return $this->text($x, $y, $w, $h, $text, $size, $color, true, 'c', $fill, true);
    }

    private function text(float $x, float $y, float $w, float $h, string $text, float $size, string $color, bool $bold = false, string $align = 'l', ?string $fill = null, bool $round = false): string
    {
        $id = $this->shapeId++;
        $fillXml = $fill
            ? '<a:solidFill><a:srgbClr val="' . $fill . '"/></a:solidFill>'
            : '<a:noFill/>';
        $geom = $round ? 'roundRect' : 'rect';
        $paragraphs = collect(preg_split('/\R/u', $text) ?: [''])
            ->map(fn ($line) => $this->paragraph($line, $size, $color, $bold, $align))
            ->implode('');

        return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="Text ' . $id . '"/><p:cNvSpPr txBox="1"/><p:nvPr/></p:nvSpPr><p:spPr><a:xfrm><a:off x="' . $this->emu($x) . '" y="' . $this->emu($y) . '"/><a:ext cx="' . $this->emu($w) . '" cy="' . $this->emu($h) . '"/></a:xfrm><a:prstGeom prst="' . $geom . '"><a:avLst/></a:prstGeom>' . $fillXml . '<a:ln><a:noFill/></a:ln></p:spPr><p:txBody><a:bodyPr wrap="square" anchor="ctr"><a:spAutoFit/></a:bodyPr><a:lstStyle/>' . $paragraphs . '</p:txBody></p:sp>';
    }

    private function paragraph(string $text, float $size, string $color, bool $bold, string $align): string
    {
        $align = $this->paragraphAlign($align);

        return '<a:p><a:pPr algn="' . $align . '"/><a:r><a:rPr lang="es-MX" sz="' . (int) round($size * 100) . '"' . ($bold ? ' b="1"' : '') . '><a:solidFill><a:srgbClr val="' . $color . '"/></a:solidFill><a:latin typeface="Aptos"/></a:rPr><a:t>' . $this->xml($text) . '</a:t></a:r><a:endParaRPr lang="es-MX"/></a:p>';
    }

    private function paragraphAlign(string $align): string
    {
        if ($align === 'c') {
            return 'ctr';
        }

        return in_array($align, ['l', 'r', 'ctr', 'just'], true) ? $align : 'l';
    }

    private function wrapSlide(string $content): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">'
            . '<p:cSld><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>'
            . $content
            . '</p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sld>';
    }

    private function writePackage(ZipArchive $zip, array $slides): void
    {
        $zip->addFromString('[Content_Types].xml', $this->contentTypes(count($slides)));
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('docProps/app.xml', $this->appProps(count($slides)));
        $zip->addFromString('docProps/core.xml', $this->coreProps());
        $zip->addFromString('ppt/presentation.xml', $this->presentationXml(count($slides)));
        $zip->addFromString('ppt/_rels/presentation.xml.rels', $this->presentationRels(count($slides)));
        $zip->addFromString('ppt/presProps.xml', $this->presPropsXml());
        $zip->addFromString('ppt/viewProps.xml', $this->viewPropsXml());
        $zip->addFromString('ppt/tableStyles.xml', $this->tableStylesXml());
        $zip->addFromString('ppt/theme/theme1.xml', $this->themeXml());
        $zip->addFromString('ppt/slideMasters/slideMaster1.xml', $this->slideMasterXml());
        $zip->addFromString('ppt/slideMasters/_rels/slideMaster1.xml.rels', $this->slideMasterRels());
        $zip->addFromString('ppt/slideLayouts/slideLayout1.xml', $this->slideLayoutXml());
        $zip->addFromString('ppt/slideLayouts/_rels/slideLayout1.xml.rels', $this->slideLayoutRels());

        foreach ($slides as $index => $xml) {
            $n = $index + 1;
            $zip->addFromString("ppt/slides/slide{$n}.xml", $xml);
            $zip->addFromString("ppt/slides/_rels/slide{$n}.xml.rels", $this->slideRels());
        }
    }

    private function contentTypes(int $slideCount): string
    {
        $slides = '';

        for ($i = 1; $i <= $slideCount; $i++) {
            $slides .= '<Override PartName="/ppt/slides/slide' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/><Override PartName="/ppt/presProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presProps+xml"/><Override PartName="/ppt/viewProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.viewProps+xml"/><Override PartName="/ppt/tableStyles.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.tableStyles+xml"/><Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/><Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/><Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>' . $slides . '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function presentationXml(int $slideCount): string
    {
        $sldIds = '';

        for ($i = 1; $i <= $slideCount; $i++) {
            $sldIds .= '<p:sldId id="' . (255 + $i) . '" r:id="rId' . ($i + 1) . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId1"/></p:sldMasterIdLst><p:sldIdLst>' . $sldIds . '</p:sldIdLst><p:sldSz cx="' . self::SLIDE_W . '" cy="' . self::SLIDE_H . '" type="wide"/><p:notesSz cx="6858000" cy="9144000"/><p:defaultTextStyle><a:defPPr><a:defRPr lang="es-MX"/></a:defPPr><a:lvl1pPr marL="0" algn="l"><a:defRPr sz="1800"><a:solidFill><a:schemeClr val="tx1"/></a:solidFill><a:latin typeface="Aptos"/></a:defRPr></a:lvl1pPr></p:defaultTextStyle></p:presentation>';
    }

    private function presentationRels(int $slideCount): string
    {
        $rels = '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>';

        for ($i = 1; $i <= $slideCount; $i++) {
            $rels .= '<Relationship Id="rId' . ($i + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide' . $i . '.xml"/>';
        }

        $rels .= '<Relationship Id="rId' . ($slideCount + 2) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/presProps" Target="presProps.xml"/>';
        $rels .= '<Relationship Id="rId' . ($slideCount + 3) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/viewProps" Target="viewProps.xml"/>';
        $rels .= '<Relationship Id="rId' . ($slideCount + 4) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>';
        $rels .= '<Relationship Id="rId' . ($slideCount + 5) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/tableStyles" Target="tableStyles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private function slideRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/></Relationships>';
    }

    private function presPropsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:presentationPr xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:showPr showNarration="1" useTimings="1"/></p:presentationPr>';
    }

    private function viewPropsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:viewPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:normalViewPr><p:restoredLeft sz="15620"/><p:restoredTop sz="94660"/></p:normalViewPr><p:slideViewPr><p:cSldViewPr><p:cViewPr varScale="1"><p:scale><a:sx n="100" d="100"/><a:sy n="100" d="100"/></p:scale><p:origin x="0" y="0"/></p:cViewPr><p:guideLst/></p:cSldViewPr></p:slideViewPr><p:notesTextViewPr><p:cViewPr><p:scale><a:sx n="100" d="100"/><a:sy n="100" d="100"/></p:scale><p:origin x="0" y="0"/></p:cViewPr></p:notesTextViewPr><p:gridSpacing cx="72008" cy="72008"/></p:viewPr>';
    }

    private function tableStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:tblStyleLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" def="{5C22544A-7EE6-4342-B048-85BDC9FD1C3A}"/>';
    }

    private function slideMasterXml(): string
    {
        $lvl = '<a:lvl1pPr marL="0" algn="l"><a:defRPr sz="2400" kern="1200"><a:solidFill><a:schemeClr val="tx1"/></a:solidFill><a:latin typeface="Aptos"/></a:defRPr></a:lvl1pPr>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"><p:cSld><p:bg><p:bgRef idx="1001"><a:schemeClr val="bg1"/></p:bgRef></p:bg><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/><p:sldLayoutIdLst><p:sldLayoutId id="2147483649" r:id="rId1"/></p:sldLayoutIdLst><p:txStyles><p:titleStyle>' . $lvl . '</p:titleStyle><p:bodyStyle>' . $lvl . '</p:bodyStyle><p:otherStyle>' . $lvl . '</p:otherStyle></p:txStyles></p:sldMaster>';
    }

    private function slideMasterRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/></Relationships>';
    }

    private function slideLayoutXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="blank" preserve="1"><p:cSld name="Blank"><p:spTree><p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr><p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr></p:spTree></p:cSld><p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr></p:sldLayout>';
    }

    private function slideLayoutRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/></Relationships>';
    }

    private function themeXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Seguridad Vial"><a:themeElements><a:clrScheme name="Seguridad Vial"><a:dk1><a:srgbClr val="0F172A"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1><a:dk2><a:srgbClr val="0B2B61"/></a:dk2><a:lt2><a:srgbClr val="F8FAFC"/></a:lt2><a:accent1><a:srgbClr val="1D4ED8"/></a:accent1><a:accent2><a:srgbClr val="0F766E"/></a:accent2><a:accent3><a:srgbClr val="D97706"/></a:accent3><a:accent4><a:srgbClr val="DC2626"/></a:accent4><a:accent5><a:srgbClr val="7C3AED"/></a:accent5><a:accent6><a:srgbClr val="334155"/></a:accent6><a:hlink><a:srgbClr val="2563EB"/></a:hlink><a:folHlink><a:srgbClr val="7C3AED"/></a:folHlink></a:clrScheme><a:fontScheme name="Aptos"><a:majorFont><a:latin typeface="Aptos Display"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont><a:minorFont><a:latin typeface="Aptos"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont></a:fontScheme><a:fmtScheme name="Seguridad Vial"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:lumMod val="110000"/><a:satMod val="105000"/><a:tint val="67000"/></a:schemeClr></a:gs><a:gs pos="50000"><a:schemeClr val="phClr"><a:lumMod val="105000"/><a:satMod val="103000"/><a:tint val="73000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:lumMod val="105000"/><a:satMod val="109000"/><a:tint val="81000"/></a:schemeClr></a:gs></a:gsLst><a:lin ang="5400000" scaled="0"/></a:gradFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:satMod val="103000"/><a:lumMod val="102000"/><a:tint val="94000"/></a:schemeClr></a:gs><a:gs pos="50000"><a:schemeClr val="phClr"><a:satMod val="110000"/><a:lumMod val="100000"/><a:shade val="100000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:lumMod val="99000"/><a:satMod val="120000"/><a:shade val="78000"/></a:schemeClr></a:gs></a:gsLst><a:lin ang="5400000" scaled="0"/></a:gradFill></a:fillStyleLst><a:lnStyleLst><a:ln w="6350" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="12700" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="19050" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="20000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="23000"/></a:srgbClr></a:outerShdw></a:effectLst></a:effectStyle><a:effectStyle><a:effectLst><a:outerShdw blurRad="57150" dist="19050" dir="5400000" algn="ctr" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="23000"/></a:srgbClr></a:outerShdw></a:effectLst></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"><a:tint val="95000"/><a:satMod val="170000"/></a:schemeClr></a:solidFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="93000"/><a:satMod val="150000"/><a:shade val="98000"/><a:lumMod val="102000"/></a:schemeClr></a:gs><a:gs pos="50000"><a:schemeClr val="phClr"><a:tint val="98000"/><a:satMod val="130000"/><a:shade val="90000"/><a:lumMod val="103000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="63000"/><a:satMod val="120000"/></a:schemeClr></a:gs></a:gsLst><a:lin ang="5400000" scaled="0"/></a:gradFill></a:bgFillStyleLst></a:fmtScheme></a:themeElements><a:objectDefaults/><a:extraClrSchemeLst/></a:theme>';
    }

    private function appProps(int $slideCount): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistema Estadistico</Application><PresentationFormat>Widescreen</PresentationFormat><Slides>' . $slideCount . '</Slides><ScaleCrop>false</ScaleCrop></Properties>';
    }

    private function coreProps(): string
    {
        $now = now('America/Mexico_City')->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>Informe de Seguridad Vial</dc:title><dc:creator>Sistema Estadistico</dc:creator><cp:lastModifiedBy>Sistema Estadistico</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>';
    }

    private function periodoTexto(array $reporte): string
    {
        return (string) ($reporte['periodo']['texto'] ?? 'Periodo seleccionado');
    }

    private function values($value): array
    {
        if ($value instanceof Collection) {
            return $value->values()->all();
        }

        if (is_array($value)) {
            return array_values($value);
        }

        return [];
    }

    private function mapBounds(array $puntos): array
    {
        $lats = [];
        $lngs = [];

        foreach ($puntos as $punto) {
            if (is_numeric($punto['lat'] ?? null) && is_numeric($punto['lng'] ?? null)) {
                $lats[] = (float) $punto['lat'];
                $lngs[] = (float) $punto['lng'];
            }
        }

        if (!$lats || !$lngs) {
            return [19.55, 19.86, -101.36, -101.02];
        }

        $latMin = min($lats);
        $latMax = max($lats);
        $lngMin = min($lngs);
        $lngMax = max($lngs);
        $latPad = max(.01, ($latMax - $latMin) * .18);
        $lngPad = max(.01, ($lngMax - $lngMin) * .18);

        return [$latMin - $latPad, $latMax + $latPad, $lngMin - $lngPad, $lngMax + $lngPad];
    }

    private function mapPoint(float $lat, float $lng, array $bounds, float $x, float $y, float $w, float $h): array
    {
        [$latMin, $latMax, $lngMin, $lngMax] = $bounds;
        $latSpan = max(.000001, $latMax - $latMin);
        $lngSpan = max(.000001, $lngMax - $lngMin);
        $px = $x + ((($lng - $lngMin) / $lngSpan) * $w);
        $py = $y + ($h - ((($lat - $latMin) / $latSpan) * $h));

        return [
            max($x, min($x + $w, $px)),
            max($y, min($y + $h, $py)),
        ];
    }

    private function heatPointColor(array $punto): string
    {
        if (($punto['categoria'] ?? null) === 'fallecidos') {
            return 'DC2626';
        }

        if (($punto['categoria'] ?? null) === 'lesionados') {
            return 'D97706';
        }

        return '2563EB';
    }

    private function short($value, int $max): string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $value));

        if (mb_strlen($text, 'UTF-8') <= $max) {
            return $text;
        }

        return mb_substr($text, 0, max(1, $max - 1), 'UTF-8') . '...';
    }

    private function num($value): string
    {
        if (is_numeric($value)) {
            return number_format((float) $value, floor((float) $value) == (float) $value ? 0 : 1);
        }

        return (string) $value;
    }

    private function emu(float $inches): int
    {
        return (int) round($inches * self::EMU);
    }

    private function xml($value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
