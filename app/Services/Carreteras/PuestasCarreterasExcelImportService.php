<?php

namespace App\Services\Carreteras;

use App\Models\Destacamento;
use App\Models\PuestaDisposicion;
use App\Models\Unidad;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class PuestasCarreterasExcelImportService
{
    private const REQUIRED_HEADERS = [
        'folio' => ['FOLIO'],
        'numero_origen' => ['NUMERO'],
        'fecha' => ['FECHA'],
        'destacamento' => ['DESTACAMENTO'],
        'lugar' => ['MUNICIPIO DE LA PUESTA'],
        'faltas' => ['FALTA ADMINISTRATIVA'],
        'detenidos' => ['DETENCION'],
        'aseguramientos' => ['ASEGURAMIENTO'],
        'sexo' => ['SEXO'],
        'menores' => ['CUANTOS MENORES DE EDAD'],
        'descripcion' => ['MOTIVO'],
        'primer_respondiente' => ['1ER RESPONDIENTE', 'PRIMER RESPONDIENTE'],
        'personal_participante' => ['PERSONAL QUE PARTICIPA EN LA PUESTA A DISPOSICION'],
        'detenidos_descripcion' => ['DETENIDOS EN LA PUESTA'],
        'rnd' => ['RND'],
        'carpeta_investigacion' => ['CARPETA DE INVESTIGACION'],
        'autoridad_receptora' => ['MP COMUN O FEDERAL'],
    ];

    public function analizarArchivo(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("No se puede leer el archivo: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);
        [$sheet, $headerRow, $columns] = $this->locateSourceSheet($workbook->getAllSheets());

        $records = [];
        $errors = [];
        $warnings = [];
        $seenSequences = [];

        for ($row = $headerRow + 1; $row <= $sheet->getHighestDataRow(); $row++) {
            $sequenceValue = $sheet->getCellByColumnAndRow(1, $row)->getValue();

            if (!is_numeric($sequenceValue) || (int) $sequenceValue <= 0) {
                continue;
            }

            $sequence = (int) $sequenceValue;
            if (isset($seenSequences[$sequence])) {
                $errors[] = "Fila {$row}: la secuencia {$sequence} esta duplicada.";
                continue;
            }
            $seenSequences[$sequence] = true;

            $date = $this->parseDate($sheet->getCellByColumnAndRow($columns['fecha'], $row)->getValue());
            $record = [
                'fila' => $row,
                'secuencia_origen' => $sequence,
                'folio_origen' => $this->cellText($sheet, $columns['folio'], $row),
                'numero_origen' => $this->cellText($sheet, $columns['numero_origen'], $row),
                'fecha_puesta' => $date,
                'destacamento' => $this->cellText($sheet, $columns['destacamento'], $row),
                'lugar_puesta' => $this->cellText($sheet, $columns['lugar'], $row),
                'numero_faltas_administrativas' => $this->cellInteger($sheet, $columns['faltas'], $row),
                'numero_detenidos' => $this->cellInteger($sheet, $columns['detenidos'], $row),
                'numero_aseguramientos' => $this->cellInteger($sheet, $columns['aseguramientos'], $row),
                'sexo_resumen' => $this->cellText($sheet, $columns['sexo'], $row),
                'numero_menores' => $this->cellInteger($sheet, $columns['menores'], $row),
                'descripcion_origen' => $this->cellText($sheet, $columns['descripcion'], $row),
                'primer_respondiente' => $this->cellText($sheet, $columns['primer_respondiente'], $row),
                'personal_participante' => $this->cellText($sheet, $columns['personal_participante'], $row),
                'detenidos_descripcion' => $this->cellText($sheet, $columns['detenidos_descripcion'], $row),
                'rnd' => $this->cellText($sheet, $columns['rnd'], $row),
                'carpeta_investigacion' => $this->cellText($sheet, $columns['carpeta_investigacion'], $row),
                'autoridad_receptora' => $this->cellText($sheet, $columns['autoridad_receptora'], $row),
            ];

            if (!$record['fecha_puesta']) {
                $errors[] = "Fila {$row}: fecha invalida o vacia.";
            }
            if (!$record['destacamento']) {
                $errors[] = "Fila {$row}: destacamento vacio.";
            }
            if (!$record['descripcion_origen']) {
                $errors[] = "Fila {$row}: motivo/descripción vacio.";
            }
            if (!$record['primer_respondiente']) {
                $errors[] = "Fila {$row}: primer respondiente vacio.";
            }
            if (!$record['numero_origen']) {
                $warnings[] = "Fila {$row}: NÚMERO de origen vacio; se conservara la secuencia {$sequence}.";
            }

            $records[] = $record;
        }

        if (!$records) {
            $errors[] = 'No se encontraron registros numerados en la hoja IPH.';
        }

        $detailTotals = [
            'puestas' => count($records),
            'faltas' => array_sum(array_column($records, 'numero_faltas_administrativas')),
            'detenciones' => array_sum(array_column($records, 'numero_detenidos')),
            'aseguramientos' => array_sum(array_column($records, 'numero_aseguramientos')),
        ];

        $footerTotals = $this->readFooterTotals($sheet, $headerRow, $columns);
        foreach (['faltas', 'detenciones', 'aseguramientos'] as $key) {
            if ($footerTotals[$key] !== null && $footerTotals[$key] !== $detailTotals[$key]) {
                $warnings[] = "El detalle suma {$detailTotals[$key]} {$key}, pero el total al pie indica {$footerTotals[$key]}. Se usara el detalle fila por fila.";
            }
        }

        $summaryTotals = $this->readConcentradoTotals($workbook->getAllSheets());
        foreach (['puestas', 'faltas', 'detenciones', 'aseguramientos'] as $key) {
            if ($summaryTotals[$key] !== null && $summaryTotals[$key] !== $detailTotals[$key]) {
                $warnings[] = "El detalle suma {$detailTotals[$key]} {$key}, pero CONCENTRADO indica {$summaryTotals[$key]}. Se usara el detalle fila por fila.";
            }
        }

        $years = array_values(array_unique(array_filter(array_map(function ($record) {
            return $record['fecha_puesta'] ? (int) substr($record['fecha_puesta'], 0, 4) : null;
        }, $records))));
        sort($years);

        return [
            'hoja' => $sheet->getTitle(),
            'registros' => $records,
            'errores' => array_values(array_unique($errors)),
            'advertencias' => array_values(array_unique($warnings)),
            'totales_detalle' => $detailTotals,
            'totales_pie' => $footerTotals,
            'totales_concentrado' => $summaryTotals,
            'anios' => $years,
        ];
    }

    public function planificar(string $path, ?string $source = null): array
    {
        $analysis = $this->analizarArchivo($path);
        $unit = Unidad::query()->where('slug', 'carreteras')->first();

        if (!$unit) {
            $analysis['errores'][] = 'No existe la unidad con slug carreteras.';
            return $this->emptyPlan($analysis, $source);
        }

        $source = $this->sourceName($source, $analysis['anios']);
        $detachments = Destacamento::query()
            ->where('unidad_id', $unit->id)
            ->where('activo', true)
            ->get()
            ->keyBy(fn ($item) => $this->normalize($item->nombre));

        $minDate = collect($analysis['registros'])->pluck('fecha_puesta')->filter()->min();
        $maxDate = collect($analysis['registros'])->pluck('fecha_puesta')->filter()->max();
        $existing = PuestaDisposicion::query()
            ->with('vehiculos')
            ->where('unidad_id', $unit->id)
            ->when($minDate && $maxDate, fn ($query) => $query->whereBetween('fecha_puesta', [$minDate, $maxDate]))
            ->get();
        $bySource = $existing
            ->filter(fn ($item) => $item->fuente_importacion === $source && $item->secuencia_origen !== null)
            ->keyBy(fn ($item) => (int) $item->secuencia_origen);
        $byDateAndDetachment = [];

        foreach ($existing as $item) {
            $key = $this->dateDetachmentKey(
                $item->fecha_puesta ? $item->fecha_puesta->format('Y-m-d') : null,
                $item->destacamento_id
            );
            $byDateAndDetachment[$key][] = $item;
        }

        $rows = [];
        $stats = ['crear' => 0, 'vincular' => 0, 'omitido' => 0, 'error' => 0];

        foreach ($analysis['registros'] as $record) {
            $detachment = $detachments->get($this->normalize($record['destacamento']));
            $action = 'crear';
            $existingId = null;
            $message = null;

            if (!$detachment) {
                $action = 'error';
                $message = "Destacamento no catalogado: {$record['destacamento']}";
            } elseif ($bySource->has($record['secuencia_origen'])) {
                $action = 'omitido';
                $existingId = $bySource->get($record['secuencia_origen'])->id;
                $message = 'La fila ya fue importada.';
            } else {
                $key = $this->dateDetachmentKey($record['fecha_puesta'], $detachment->id);
                $candidates = array_values(array_filter($byDateAndDetachment[$key] ?? [], function ($candidate) use ($record) {
                    return $candidate->fuente_importacion === null
                        && $this->namesCompatible($record['primer_respondiente'], $candidate->nombre_policia);
                }));

                if (count($candidates) === 1) {
                    $action = 'vincular';
                    $existingId = $candidates[0]->id;
                    $message = 'Coincide por fecha, destacamento y elementos del nombre del primer respondiente.';
                } elseif (count($candidates) > 1) {
                    $action = 'error';
                    $message = 'Hay mas de una posible coincidencia existente; requiere revision manual.';
                }
            }

            $stats[$action]++;
            $record['destacamento_id'] = $detachment ? (int) $detachment->id : null;
            $record['tipo_puesta_destino'] = $this->inferType($record);
            $record['motivo_destino'] = $this->inferReason($record);
            $record['accion'] = $action;
            $record['existente_id'] = $existingId;
            $record['mensaje'] = $message;
            $rows[] = $record;
        }

        $duplicateTargets = collect($rows)
            ->where('accion', 'vincular')
            ->groupBy('existente_id')
            ->filter(fn ($group) => $group->count() > 1);

        if ($duplicateTargets->isNotEmpty()) {
            $existingById = $existing->keyBy('id');

            foreach ($duplicateTargets as $existingId => $group) {
                $target = $existingById->get((int) $existingId);
                $ranked = $group
                    ->mapWithKeys(fn ($row) => [
                        $row['secuencia_origen'] => $target
                            ? $this->vehicleMatchRank($row, $target)
                            : null,
                    ])
                    ->filter(fn ($rank) => $rank !== null);
                $bestRank = $ranked->max();
                $winners = $bestRank === null
                    ? collect()
                    : $ranked->filter(fn ($rank) => $rank === $bestRank);

                if ($winners->count() === 1) {
                    $winnerSequence = (int) $winners->keys()->first();
                    foreach ($rows as &$row) {
                        if ($row['accion'] !== 'vincular' || (int) $row['existente_id'] !== (int) $existingId) {
                            continue;
                        }

                        if ((int) $row['secuencia_origen'] === $winnerSequence) {
                            $row['mensaje'] = 'Coincide por fecha, respondiente e identificadores del vehiculo.';
                            continue;
                        }

                        $row['accion'] = 'crear';
                        $row['existente_id'] = null;
                        $row['mensaje'] = 'Es una puesta distinta del registro existente, de acuerdo con los identificadores del vehiculo.';
                        $stats['vincular']--;
                        $stats['crear']++;
                    }
                    unset($row);
                    continue;
                }

                $sequences = $group
                    ->pluck('secuencia_origen')
                    ->implode(', ');
                foreach ($rows as &$row) {
                    if ($row['accion'] !== 'vincular' || (int) $row['existente_id'] !== (int) $existingId) {
                        continue;
                    }
                    $row['accion'] = 'error';
                    $row['mensaje'] = "El registro existente {$existingId} coincide con varias secuencias ({$sequences}); requiere revision manual.";
                    $stats['vincular']--;
                    $stats['error']++;
                }
                unset($row);
            }
        }

        return [
            'fuente' => $source,
            'unidad_id' => (int) $unit->id,
            'unidad_nombre' => $unit->nombre,
            'analisis' => $analysis,
            'registros' => $rows,
            'conteos' => $stats,
            'clasificacion' => [
                'tipos' => collect($rows)->countBy('tipo_puesta_destino')->sortKeys()->all(),
                'motivos' => collect($rows)->countBy('motivo_destino')->sortKeys()->all(),
            ],
        ];
    }

    public function ejecutar(array $plan, ?int $createdBy = null, bool $linkExisting = false): array
    {
        if (!empty($plan['analisis']['errores']) || ($plan['conteos']['error'] ?? 0) > 0) {
            throw new RuntimeException('El plan contiene errores y no puede ejecutarse.');
        }
        if (($plan['conteos']['vincular'] ?? 0) > 0 && !$linkExisting) {
            throw new RuntimeException('Hay coincidencias existentes. Revise el dry-run y use --vincular-existentes para confirmarlas.');
        }

        return DB::transaction(function () use ($plan, $createdBy, $linkExisting) {
            $result = ['creados' => 0, 'vinculados' => 0, 'omitidos' => 0];
            $nextNumbers = [];

            foreach ($plan['registros'] as $record) {
                if ($record['accion'] === 'omitido') {
                    $result['omitidos']++;
                    continue;
                }

                $origin = $this->originAttributes($plan['fuente'], $record);

                if ($record['accion'] === 'vincular' && $linkExisting) {
                    PuestaDisposicion::query()->whereKey($record['existente_id'])->update($origin);
                    $result['vinculados']++;
                    continue;
                }

                if ($record['accion'] !== 'crear') {
                    throw new RuntimeException("No se puede procesar la fila {$record['fila']}: {$record['mensaje']}");
                }

                $year = (int) substr($record['fecha_puesta'], 0, 4);
                if (!isset($nextNumbers[$year])) {
                    $last = PuestaDisposicion::query()
                        ->where('anio', $year)
                        ->where('unidad_id', $plan['unidad_id'])
                        ->orderByDesc('numero_puesta')
                        ->lockForUpdate()
                        ->first(['numero_puesta']);
                    $nextNumbers[$year] = $last ? ((int) $last->numero_puesta + 1) : 1;
                }

                PuestaDisposicion::query()->create(array_merge($origin, [
                    'numero_puesta' => $nextNumbers[$year]++,
                    'anio' => $year,
                    'tipo_puesta' => $record['tipo_puesta_destino'],
                    'motivo' => $record['motivo_destino'],
                    'estatus' => 'ACTIVA',
                    'nombre_policia' => Str::upper($record['primer_respondiente']),
                    'nombre_mp' => null,
                    'autoridad_receptora' => $this->upperOrNull($record['autoridad_receptora']),
                    'area' => Str::upper($plan['unidad_nombre']),
                    'carpeta_investigacion' => $this->upperOrNull($record['carpeta_investigacion']),
                    'oficio' => null,
                    'fecha_puesta' => $record['fecha_puesta'],
                    'hora_puesta' => null,
                    'lugar_puesta' => $this->upperOrNull($record['lugar_puesta']),
                    'narrativa' => $this->upperOrNull($record['descripcion_origen']),
                    'observaciones' => 'REGISTRO HISTORICO IMPORTADO DE LISTADO IPH DE CARRETERAS.',
                    'unidad_id' => $plan['unidad_id'],
                    'delegacion_id' => null,
                    'destacamento_id' => $record['destacamento_id'],
                    'created_by' => $createdBy,
                ]));
                $result['creados']++;
            }

            return $result;
        });
    }

    private function locateSourceSheet(array $sheets): array
    {
        foreach ($sheets as $sheet) {
            $limit = min(25, $sheet->getHighestDataRow());
            for ($row = 1; $row <= $limit; $row++) {
                $headers = [];
                for ($column = 1; $column <= min(40, $sheet->getHighestDataColumn() ? \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) : 1); $column++) {
                    $value = $this->normalize($sheet->getCellByColumnAndRow($column, $row)->getValue());
                    if ($value !== '') {
                        $headers[$value] = $column;
                    }
                }

                $columns = [];
                foreach (self::REQUIRED_HEADERS as $key => $aliases) {
                    foreach ($aliases as $alias) {
                        $normalizedAlias = $this->normalize($alias);
                        if (isset($headers[$normalizedAlias])) {
                            $columns[$key] = $headers[$normalizedAlias];
                            break;
                        }
                    }
                }

                if (count($columns) === count(self::REQUIRED_HEADERS)) {
                    return [$sheet, $row, $columns];
                }
            }
        }

        throw new RuntimeException('No se encontro una hoja con todos los encabezados esperados del listado IPH.');
    }

    private function readFooterTotals(Worksheet $sheet, int $headerRow, array $columns): array
    {
        $result = ['faltas' => null, 'detenciones' => null, 'aseguramientos' => null];
        for ($row = $headerRow + 1; $row <= $sheet->getHighestDataRow(); $row++) {
            $sequence = $sheet->getCellByColumnAndRow(1, $row)->getValue();
            if (is_numeric($sequence) && (int) $sequence > 0) {
                continue;
            }
            foreach (['faltas' => 'faltas', 'detenciones' => 'detenidos', 'aseguramientos' => 'aseguramientos'] as $key => $columnKey) {
                $value = $sheet->getCellByColumnAndRow($columns[$columnKey], $row)->getCalculatedValue();
                if (is_numeric($value)) {
                    $result[$key] = (int) $value;
                }
            }
        }
        return $result;
    }

    private function readConcentradoTotals(array $sheets): array
    {
        $result = ['puestas' => null, 'faltas' => null, 'detenciones' => null, 'aseguramientos' => null];
        foreach ($sheets as $sheet) {
            if ($this->normalize($sheet->getTitle()) !== 'CONCENTRADO') {
                continue;
            }
            for ($row = 1; $row <= $sheet->getHighestDataRow(); $row++) {
                if ($this->normalize($sheet->getCellByColumnAndRow(1, $row)->getValue()) !== 'TOTAL') {
                    continue;
                }
                $result['puestas'] = $this->numericOrNull($sheet->getCellByColumnAndRow(2, $row)->getCalculatedValue());
                $result['aseguramientos'] = $this->numericOrNull($sheet->getCellByColumnAndRow(3, $row)->getCalculatedValue());
                $result['faltas'] = $this->numericOrNull($sheet->getCellByColumnAndRow(4, $row)->getCalculatedValue());
                $result['detenciones'] = $this->numericOrNull($sheet->getCellByColumnAndRow(5, $row)->getCalculatedValue());
                break;
            }
        }
        return $result;
    }

    private function originAttributes(string $source, array $record): array
    {
        return [
            'fuente_importacion' => $source,
            'secuencia_origen' => $record['secuencia_origen'],
            'folio_origen' => $this->upperOrNull($record['folio_origen']),
            'numero_origen' => $this->upperOrNull($record['numero_origen']),
            'descripcion_origen' => $this->upperOrNull($record['descripcion_origen']),
            'personal_participante' => $this->upperOrNull($record['personal_participante']),
            'detenidos_descripcion' => $this->upperOrNull($record['detenidos_descripcion']),
            'rnd' => $this->upperOrNull($record['rnd']),
            'numero_faltas_administrativas' => $record['numero_faltas_administrativas'],
            'numero_detenidos' => $record['numero_detenidos'],
            'numero_aseguramientos' => $record['numero_aseguramientos'],
            'numero_menores' => $record['numero_menores'],
            'sexo_resumen' => $this->upperOrNull($record['sexo_resumen']),
        ];
    }

    private function inferType(array $record): string
    {
        $detaineeText = $this->normalize($record['detenidos_descripcion']);
        $hasNamedDetainees = $detaineeText !== ''
            && !preg_match('/^(SIN|NO) (PERSONAS )?DETENID/', $detaineeText);
        $hasPerson = ($record['numero_faltas_administrativas'] + $record['numero_detenidos']) > 0
            || $hasNamedDetainees;
        $hasSeizure = $record['numero_aseguramientos'] > 0;
        if ($hasPerson && $hasSeizure) {
            return 'MIXTA';
        }
        if ($hasPerson) {
            return 'PERSONA';
        }
        return $this->containsVehicle($record['descripcion_origen']) ? 'VEHICULO' : 'OBJETO';
    }

    private function inferReason(array $record): string
    {
        $text = $this->normalize($record['descripcion_origen']);
        $authority = $this->normalize($record['autoridad_receptora']);
        if (str_contains($text, 'ORDEN DE APREHENSION')) return 'ORDEN DE APREHENSION';
        if (str_contains($text, 'MANDAMIENTO')) return 'MANDAMIENTO JUDICIAL';
        if ($record['numero_faltas_administrativas'] > 0 || preg_match('/BARANDILLA|JUSTICIA CIVICA/', $authority)) return 'FALTA ADMINISTRATIVA';
        if (preg_match('/HECHO DE TRANSITO|CHOQUE|VOLCADURA/', $text)) return 'HECHO DE TRANSITO TURNADO';
        if ($this->containsVehicle($text) && str_contains($text, 'ABANDONAD')) return 'VEHICULO ABANDONADO';
        if ($this->containsVehicle($text) && str_contains($text, 'REPORTE DE ROBO')) return 'VEHICULO CON REPORTE DE ROBO';
        if ($this->containsVehicle($text) && preg_match('/ALTERACION|ALTERADO|MEDIOS DE IDENTIFICACION/', $text)) return 'VEHICULO ALTERADO';
        if ($this->containsVehicle($text) && preg_match('/RECUPERAD/', $text)) return 'VEHICULO RECUPERADO';
        if (str_contains($text, 'ARMA DE FUEGO')) return 'POSESION DE ARMA DE FUEGO';
        if (str_contains($text, 'ARMA BLANCA')) return 'POSESION DE ARMA BLANCA';
        if (preg_match('/CRISTAL|MARIHUANA|NARCOT|DROGA|SUSTANCIA/', $text)) return 'POSESION DE SUSTANCIAS PROHIBIDAS';
        if (str_contains($text, 'ROBO')) return 'ROBO';
        if (str_contains($text, 'LESION')) return 'LESIONES';
        if (str_contains($text, 'DANO')) return 'DAÑOS';
        if ($record['numero_detenidos'] > 0) return 'PERSONA DETENIDA';
        if ($this->containsVehicle($text)) return 'OTRO';
        if ($record['numero_aseguramientos'] > 0) return 'OBJETO ASEGURADO';
        return 'OTRO';
    }

    private function containsVehicle(?string $text): bool
    {
        return (bool) preg_match('/VEHICUL|MOTO|TRACTO|CAMION|REMOLQUE|SEMIRREMOLQUE|PORTACONTENEDOR|CONTENEDOR|CAJA SECA|AUTOMOVIL|CAMIONETA|SEDAN|PICK UP|CHASIS|PLACAS|SERIE/', $this->normalize($text));
    }

    private function dateDetachmentKey(?string $date, ?int $detachmentId): string
    {
        return implode('|', [$date ?: '', (int) $detachmentId]);
    }

    private function namesCompatible(?string $source, ?string $existing): bool
    {
        $sourceTokens = array_values(array_unique(array_filter(explode(' ', $this->normalize($source)), fn ($token) => strlen($token) > 1)));
        $existingTokens = array_values(array_unique(array_filter(explode(' ', $this->normalize($existing)), fn ($token) => strlen($token) > 1)));
        if (count($sourceTokens) < 2 || count($existingTokens) < 2) {
            return false;
        }
        $intersection = array_intersect($sourceTokens, $existingTokens);
        return count($intersection) === min(count($sourceTokens), count($existingTokens));
    }

    private function vehicleMatchRank(array $record, PuestaDisposicion $existing): ?int
    {
        $description = $this->compactNormalize($record['descripcion_origen'] ?? null);
        if ($description === '') {
            return null;
        }

        $best = null;
        foreach ($existing->vehiculos as $vehicle) {
            $score = 0;
            $strongIdentifier = false;
            $plate = preg_replace('/\s*\(.*/', '', (string) $vehicle->placas);
            $plate = $this->compactNormalize($plate);
            if (strlen($plate) >= 5 && str_contains($description, $plate)) {
                $score += 100;
                $strongIdentifier = true;
            }

            $serial = $this->compactNormalize($vehicle->serie);
            if (strlen($serial) >= 8 && str_contains($description, $serial)) {
                $score += 50;
                $strongIdentifier = true;
            }

            foreach (['modelo' => 4, 'marca' => 2, 'tipo' => 1] as $attribute => $points) {
                $value = $this->compactNormalize($vehicle->{$attribute});
                if ($value !== '' && str_contains($description, $value)) {
                    $score += $points;
                }
            }

            if ($strongIdentifier && ($best === null || $score > $best)) {
                $best = $score;
            }
        }

        return $best;
    }

    private function compactNormalize($value): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', Str::upper(Str::ascii(trim((string) $value))));
    }

    private function sourceName(?string $source, array $years): string
    {
        if ($source !== null && trim($source) !== '') {
            $normalized = Str::upper(Str::ascii(trim($source)));
            $normalized = trim((string) preg_replace('/[^A-Z0-9_-]+/', '_', $normalized), '_-');
            return substr($normalized, 0, 100);
        }
        $suffix = count($years) === 1 ? (string) $years[0] : implode('_', $years);
        return substr('IPH_CARRETERAS_' . ($suffix ?: 'SIN_ANIO'), 0, 100);
    }

    private function emptyPlan(array $analysis, ?string $source): array
    {
        return [
            'fuente' => $this->sourceName($source, $analysis['anios']),
            'unidad_id' => null,
            'unidad_nombre' => null,
            'analisis' => $analysis,
            'registros' => [],
            'conteos' => ['crear' => 0, 'vincular' => 0, 'omitido' => 0, 'error' => count($analysis['errores'])],
            'clasificacion' => ['tipos' => [], 'motivos' => []],
        ];
    }

    private function parseDate($value): ?string
    {
        try {
            if (is_numeric($value)) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('Y-m-d');
            }
            $text = trim((string) $value);
            return $text === '' ? null : Carbon::parse($text)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cellText(Worksheet $sheet, int $column, int $row): ?string
    {
        $value = $sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue();
        if ($value === null || trim((string) $value) === '') return null;
        return trim(preg_replace('/\r\n?/', "\n", (string) $value));
    }

    private function cellInteger(Worksheet $sheet, int $column, int $row): int
    {
        $value = $sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue();
        return is_numeric($value) ? max(0, (int) $value) : 0;
    }

    private function numericOrNull($value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function upperOrNull(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : Str::upper($value);
    }

    private function normalize($value): string
    {
        $text = Str::upper(Str::ascii(trim((string) $value)));
        $text = preg_replace('/[^A-Z0-9]+/', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', (string) $text));
    }
}
