<?php

namespace App\Services\Carreteras;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
use App\Models\Unidad;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ActividadesCarreterasExcelImportService
{
    /**
     * El reporte solo trae el total diario de la categoria. Por eso se usa la
     * subcategoria residual correspondiente y se conserva la etiqueta original.
     */
    private const CATEGORY_MAP = [
        'INSTITUCIONES' => ['INSTITUCIONES', 'OTROS TIPOS ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
        'REPORTES C5I' => ['REPORTES C5I', 'OTROS REPORTES ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
        'ABANDERAMIENTOS' => ['ABANDERAMIENTOS', 'OTROS ABANDERAMIENTOS ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
        'CORTE DE CIRCULACION VIAL' => ['ABANDERAMIENTOS', 'CORTES DE CIRCULACION'],
        'OPERATIVOS' => ['OPERATIVOS', 'OTROS OPERATIVOS ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
        'MONITOREO' => ['MONITOREOS', 'OTROS MONITOREOS ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
        'AUXILIO VIAL A CONDUCTORES' => ['AUXILIO VIAL A CONDUCTORES', 'OTROS AUXILIOS ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
        'DISPOSITIVOS DE SEGURIDAD VIAL' => ['DISPOSITIVOS DE SEGURIDAD VIAL', 'OTROS ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
        'CAMPANAS' => ['CAMPAÑAS', 'OTRAS ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
        'PROXIMIDAD SOCIAL' => ['PROXIMIDAD SOCIAL', 'OTRAS ESPECIFICAR EN LAS NOVEDADES RELEVANTES'],
    ];

    private const MONTHS = [
        'ENERO' => 1,
        'FEBRERO' => 2,
        'MARZO' => 3,
        'ABRIL' => 4,
        'MAYO' => 5,
        'JUNIO' => 6,
        'JULIO' => 7,
        'AGOSTO' => 8,
        'SEPTIEMBRE' => 9,
        'OCTUBRE' => 10,
        'NOVIEMBRE' => 11,
        'DICIEMBRE' => 12,
    ];

    public function analizarArchivo(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("No se puede leer el archivo: {$path}");
        }

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);

        $records = [];
        $errors = [];
        $warnings = [];
        $sheetsRead = [];
        $excludedPuestas = 0;

        foreach ($workbook->getAllSheets() as $sheet) {
            $period = $this->sheetPeriod($sheet);
            if (!$period) {
                continue;
            }

            [$year, $month] = $period;
            $headerRow = $this->findHeaderRow($sheet);
            if (!$headerRow) {
                $errors[] = "Hoja {$sheet->getTitle()}: no se encontro la fila CATEGORÍA.";
                continue;
            }

            $sheetsRead[] = $sheet->getTitle();
            $dayColumns = $this->dayColumns($sheet, $headerRow, $year, $month, $errors);

            for ($row = $headerRow + 1; $row <= $sheet->getHighestDataRow(); $row++) {
                $sourceLabel = trim((string) $sheet->getCellByColumnAndRow(1, $row)->getCalculatedValue());
                $normalizedLabel = $this->normalize($sourceLabel);

                if ($normalizedLabel === 'PUESTA A DISPOSICION AL MP') {
                    foreach ($dayColumns as $column => $date) {
                        $excludedPuestas += $this->positiveInteger(
                            $sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue()
                        );
                    }
                    continue;
                }

                if (!isset(self::CATEGORY_MAP[$normalizedLabel])) {
                    continue;
                }

                [$categoryName, $subcategoryName] = self::CATEGORY_MAP[$normalizedLabel];

                foreach ($dayColumns as $column => $date) {
                    $value = $sheet->getCellByColumnAndRow($column, $row)->getCalculatedValue();
                    if ($value === null || $value === '') {
                        continue;
                    }
                    if (!is_numeric($value) || (float) $value < 0 || floor((float) $value) !== (float) $value) {
                        $errors[] = sprintf(
                            'Hoja %s, celda %s: la cantidad debe ser un entero mayor o igual a cero.',
                            $sheet->getTitle(),
                            $sheet->getCellByColumnAndRow($column, $row)->getCoordinate()
                        );
                        continue;
                    }

                    $quantity = (int) $value;
                    if ($quantity === 0) {
                        continue;
                    }

                    $records[] = [
                        'hoja' => $sheet->getTitle(),
                        'celda' => $sheet->getCellByColumnAndRow($column, $row)->getCoordinate(),
                        'fecha' => $date,
                        'etiqueta_origen' => $sourceLabel,
                        'categoria' => $categoryName,
                        'subcategoria' => $subcategoryName,
                        'cantidad_origen' => $quantity,
                    ];
                }
            }
        }

        if (!$sheetsRead) {
            $errors[] = 'No se encontraron hojas mensuales con el formato OPERATIVIDAD ... DE 2026.';
        }
        if (!$records) {
            $errors[] = 'No se encontraron cantidades de actividades compatibles con el catalogo.';
        }

        $duplicates = collect($records)
            ->groupBy(fn (array $record) => $record['fecha'] . '|' . $this->normalize($record['categoria']))
            ->filter(fn ($group) => $group->count() > 1);
        foreach ($duplicates as $group) {
            $errors[] = sprintf(
                'Las celdas %s representan mas de un total para la misma categoria y fecha.',
                $group->map(fn ($record) => $record['hoja'] . '!' . $record['celda'])->implode(', ')
            );
        }

        if ($excludedPuestas > 0) {
            $warnings[] = "Se detectaron {$excludedPuestas} puestas a disposicion en el reporte y se excluyeron por completo.";
        }

        return [
            'hojas' => array_values(array_unique($sheetsRead)),
            'registros' => $records,
            'errores' => array_values(array_unique($errors)),
            'advertencias' => array_values(array_unique($warnings)),
            'puestas_excluidas' => $excludedPuestas,
            'totales' => [
                'filas_agregadas' => count($records),
                'actividades' => array_sum(array_column($records, 'cantidad_origen')),
                'por_categoria' => collect($records)
                    ->groupBy('categoria')
                    ->map(fn ($group) => (int) $group->sum('cantidad_origen'))
                    ->sortKeys()
                    ->all(),
            ],
        ];
    }

    public function planificar(string $path, ?string $source = null): array
    {
        $analysis = $this->analizarArchivo($path);
        $unit = Unidad::query()->where('slug', 'carreteras')->first();
        $source = $this->sourceName($source, $analysis['registros']);

        if (!$unit) {
            $analysis['errores'][] = 'No existe la unidad con slug carreteras.';
            return $this->emptyPlan($analysis, $source);
        }

        $catalog = $this->resolveCatalog($analysis['registros'], (int) $unit->id, $analysis['errores']);
        if ($analysis['errores']) {
            return $this->emptyPlan($analysis, $source, (int) $unit->id, $unit->nombre);
        }

        $minDate = collect($analysis['registros'])->min('fecha');
        $maxDate = collect($analysis['registros'])->max('fecha');
        $categoryIds = collect($catalog)->pluck('categoria_id')->unique()->values();

        $existingTotals = Actividad::query()
            ->where('unidad_org_id', $unit->id)
            ->whereDate('fecha', '>=', $minDate)
            ->whereDate('fecha', '<=', $maxDate)
            ->whereIn('actividad_categoria_id', $categoryIds)
            ->get(['fecha', 'actividad_categoria_id', 'cantidad'])
            ->groupBy(fn ($row) => $row->fecha->format('Y-m-d') . '|' . (int) $row->actividad_categoria_id)
            ->map(fn ($group) => (int) $group->sum('cantidad'));

        $alreadyImported = Actividad::query()
            ->where('fuente_importacion', $source)
            ->whereNotNull('clave_importacion')
            ->pluck('id', 'clave_importacion');

        $rows = [];
        $counts = ['crear' => 0, 'omitido' => 0, 'advertencia' => 0, 'error' => 0];
        $quantity = ['origen' => 0, 'existente' => 0, 'crear' => 0];

        foreach ($analysis['registros'] as $record) {
            $resolved = $catalog[$this->normalize($record['categoria'])];
            $record['categoria_id'] = $resolved['categoria_id'];
            $record['subcategoria_id'] = $resolved['subcategoria_id'];
            $record['clave_importacion'] = $this->recordKey($record);
            $record['cantidad_existente'] = (int) $existingTotals->get(
                $record['fecha'] . '|' . $record['categoria_id'],
                0
            );
            $record['cantidad_crear'] = max(0, $record['cantidad_origen'] - $record['cantidad_existente']);
            $record['accion'] = 'crear';
            $record['mensaje'] = 'Se agregara unicamente el faltante respecto al total ya capturado.';

            if ($alreadyImported->has($record['clave_importacion'])) {
                $record['accion'] = 'omitido';
                $record['cantidad_crear'] = 0;
                $record['mensaje'] = 'Esta celda del respaldo ya fue importada.';
            } elseif ($record['cantidad_existente'] >= $record['cantidad_origen']) {
                $record['accion'] = $record['cantidad_existente'] > $record['cantidad_origen']
                    ? 'advertencia'
                    : 'omitido';
                $record['cantidad_crear'] = 0;
                $record['mensaje'] = $record['cantidad_existente'] > $record['cantidad_origen']
                    ? 'El sistema ya contiene una cantidad mayor; no se modificara ni eliminara nada.'
                    : 'El total del respaldo ya esta cubierto por registros existentes.';
            }

            $counts[$record['accion']]++;
            $quantity['origen'] += $record['cantidad_origen'];
            $quantity['existente'] += min($record['cantidad_origen'], $record['cantidad_existente']);
            $quantity['crear'] += $record['cantidad_crear'];
            $rows[] = $record;
        }

        return [
            'fuente' => $source,
            'unidad_id' => (int) $unit->id,
            'unidad_nombre' => $unit->nombre,
            'analisis' => $analysis,
            'registros' => $rows,
            'conteos' => $counts,
            'cantidades' => $quantity,
        ];
    }

    public function ejecutar(array $plan, ?int $createdBy = null): array
    {
        if (!empty($plan['analisis']['errores']) || ($plan['conteos']['error'] ?? 0) > 0) {
            throw new RuntimeException('El plan contiene errores y no puede ejecutarse.');
        }

        return DB::transaction(function () use ($plan, $createdBy) {
            $result = ['registros_creados' => 0, 'actividades_agregadas' => 0, 'omitidos' => 0];

            foreach ($plan['registros'] as $record) {
                if ($record['accion'] !== 'crear') {
                    $result['omitidos']++;
                    continue;
                }

                $imported = Actividad::query()
                    ->where('fuente_importacion', $plan['fuente'])
                    ->where('clave_importacion', $record['clave_importacion'])
                    ->exists();
                if ($imported) {
                    $result['omitidos']++;
                    continue;
                }

                $existingQuantity = (int) Actividad::query()
                    ->where('unidad_org_id', $plan['unidad_id'])
                    ->whereDate('fecha', $record['fecha'])
                    ->where('actividad_categoria_id', $record['categoria_id'])
                    ->lockForUpdate()
                    ->get(['cantidad'])
                    ->sum('cantidad');
                $missingQuantity = max(0, $record['cantidad_origen'] - $existingQuantity);

                if ($missingQuantity === 0) {
                    $result['omitidos']++;
                    continue;
                }

                Actividad::query()->create([
                    'client_uuid' => (string) Str::uuid(),
                    'submission_fingerprint' => hash('sha256', $plan['fuente'] . '|' . $record['clave_importacion']),
                    'fuente_importacion' => $plan['fuente'],
                    'clave_importacion' => $record['clave_importacion'],
                    'sync_status' => 'local',
                    'actividad_categoria_id' => $record['categoria_id'],
                    'actividad_subcategoria_id' => $record['subcategoria_id'],
                    'nombre' => 'RESPALDO HISTÓRICO DE CARRETERAS',
                    'cantidad' => $missingQuantity,
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                    'unidad_org_id' => $plan['unidad_id'],
                    'fecha' => $record['fecha'],
                    'motivo' => Str::upper($record['etiqueta_origen']),
                    'observaciones' => sprintf(
                        'TOTAL DIARIO AGREGADO IMPORTADO DEL REPORTE SEMANAL DE CARRETERAS (%s!%s). NO INCLUYE PUESTAS A DISPOSICIÓN.',
                        $record['hoja'],
                        $record['celda']
                    ),
                    'estado_revision' => 'pendiente',
                ]);

                $result['registros_creados']++;
                $result['actividades_agregadas'] += $missingQuantity;
            }

            return $result;
        });
    }

    private function sheetPeriod(Worksheet $sheet): ?array
    {
        $title = $this->normalize($sheet->getCell('A1')->getCalculatedValue());
        if (!preg_match('/OPERATIVIDAD DEL 01 AL \d{1,2} DE ([A-Z]+) DE (\d{4})/', $title, $matches)) {
            return null;
        }

        $month = self::MONTHS[$matches[1]] ?? null;
        return $month ? [(int) $matches[2], $month] : null;
    }

    private function findHeaderRow(Worksheet $sheet): ?int
    {
        for ($row = 1; $row <= min(10, $sheet->getHighestDataRow()); $row++) {
            if ($this->normalize($sheet->getCellByColumnAndRow(1, $row)->getCalculatedValue()) === 'CATEGORIA') {
                return $row;
            }
        }
        return null;
    }

    private function dayColumns(Worksheet $sheet, int $headerRow, int $year, int $month, array &$errors): array
    {
        $columns = [];
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        for ($column = 2; $column <= $highestColumn; $column++) {
            $value = $sheet->getCellByColumnAndRow($column, $headerRow)->getCalculatedValue();
            if (!is_numeric($value)) {
                continue;
            }
            $day = (int) $value;
            if (!checkdate($month, $day, $year)) {
                $errors[] = "Hoja {$sheet->getTitle()}: el dia {$day} no es valido para el periodo.";
                continue;
            }
            $columns[$column] = sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        return $columns;
    }

    private function resolveCatalog(array $records, int $unitId, array &$errors): array
    {
        $required = collect($records)->pluck('categoria')->unique();
        $categories = ActividadCategoria::query()->with('subcategorias')->get()
            ->keyBy(fn ($category) => $this->normalize($category->nombre));
        $catalog = [];

        foreach ($required as $categoryName) {
            $normalizedCategory = $this->normalize($categoryName);
            $category = $categories->get($normalizedCategory);
            if (!$category) {
                $errors[] = "No existe la categoria de actividades {$categoryName}.";
                continue;
            }

            $subcategoryName = collect($records)
                ->first(fn ($record) => $this->normalize($record['categoria']) === $normalizedCategory)['subcategoria'];
            $subcategory = $category->subcategorias
                ->filter(fn ($item) => $item->unidad_id === null || (int) $item->unidad_id === $unitId)
                ->first(fn ($item) => $this->normalize($item->nombre) === $this->normalize($subcategoryName));

            if (!$subcategory) {
                $errors[] = "No existe la subcategoria {$subcategoryName} dentro de {$categoryName}.";
                continue;
            }

            $catalog[$normalizedCategory] = [
                'categoria_id' => (int) $category->id,
                'subcategoria_id' => (int) $subcategory->id,
            ];
        }

        return $catalog;
    }

    private function sourceName(?string $source, array $records): string
    {
        if ($source !== null && trim($source) !== '') {
            return substr(trim((string) preg_replace('/[^A-Z0-9_-]+/', '_', Str::upper(Str::ascii($source))), '_-'), 0, 80);
        }

        $years = collect($records)->pluck('fecha')->map(fn ($date) => substr($date, 0, 4))->unique()->sort()->values();
        return substr('ACTIVIDADES_CARRETERAS_' . ($years->implode('_') ?: 'SIN_ANIO'), 0, 80);
    }

    private function recordKey(array $record): string
    {
        return hash('sha256', $record['fecha'] . '|' . $this->normalize($record['categoria']));
    }

    private function positiveInteger($value): int
    {
        return is_numeric($value) && (float) $value > 0 ? (int) floor((float) $value) : 0;
    }

    private function normalize($value): string
    {
        $text = Str::upper(Str::ascii(trim((string) $value)));
        return trim((string) preg_replace('/[^A-Z0-9]+/', ' ', $text));
    }

    private function emptyPlan(array $analysis, string $source, ?int $unitId = null, ?string $unitName = null): array
    {
        return [
            'fuente' => $source,
            'unidad_id' => $unitId,
            'unidad_nombre' => $unitName,
            'analisis' => $analysis,
            'registros' => [],
            'conteos' => ['crear' => 0, 'omitido' => 0, 'advertencia' => 0, 'error' => count($analysis['errores'])],
            'cantidades' => ['origen' => 0, 'existente' => 0, 'crear' => 0],
        ];
    }
}
