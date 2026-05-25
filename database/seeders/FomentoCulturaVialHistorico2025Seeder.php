<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class FomentoCulturaVialHistorico2025Seeder extends Seeder
{
    private const UNIDAD_FOMENTO_ID = 6;
    private const MARKER = 'SEEDER UFCV HISTORICO 2025';
    private const TZ = 'America/Mexico_City';

    private const POPULATION_FIELDS = [
        'ninas',
        'ninos',
        'adolescentes_mujeres',
        'adolescentes_hombres',
        'docentes_hombres',
        'docentes_mujeres',
        'hombres',
        'mujeres',
    ];

    private const ACTION_FIELDS = [
        'taller_seguridad_vial' => 'Taller de Seguridad Vial',
        'taller_manejo_defensivo' => 'Taller de Manejo Defensivo',
        'taller_gestion_emociones_conduccion' => 'Taller Gestion de Emociones en la Conduccion',
        'taller_educacion_vial_manejo_defensivo_ambulancia' => 'Taller de Educacion Vial y Manejo Defensivo para operador de ambulancia',
        'talleres_genero_violencia' => 'Talleres Genero y Violencia',
        'taller_ley_movilidad' => 'Taller de Ley de Movilidad',
        'complementarios_modulo_informativo' => 'Complementarios: Modulo Informativo',
        'clases_umsnh' => 'Clases UMSNH',
        'capacitacion_elementos' => 'Capacitacion para elementos',
        'apoyos_viales' => 'Apoyos Viales',
        'campanas' => 'Campañas',
        'actualizacion_elementos_coordinacion' => 'Actualizacion de elementos de la Coordinacion',
        'modulo_actividades_ludicas_kalli' => 'Modulo de Actividades Ludicas Pabellon Infantil Kalli',
        'taller_alcohol_conduccion' => 'Taller Alcohol y conduccion',
    ];

    private $schemaColumns = [];

    public function run(): void
    {
        $this->assertRequiredTables();
        $this->validateSourceRows();

        $now = Carbon::now(self::TZ)->format('Y-m-d H:i:s');

        DB::transaction(function () use ($now) {
            $this->ensureUnidadFomento($now);
            $catalogo = $this->ensureCatalogoHistorico($now);

            $this->limpiarActividadesPrevias();
            $this->seedActividades($catalogo, $now);
            $this->validateInsertedTotals();
        });

        if ($this->command) {
            $this->command->info('Seeder UFCV historico 2025 ejecutado correctamente.');
        }
    }

    private function assertRequiredTables(): void
    {
        $tables = [
            'unidades',
            'actividad_categorias',
            'actividad_subcategorias',
            'actividades',
            'fomento_cultura_vial_programas',
            'fomento_cultura_vial_detalles',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("No existe la tabla requerida [{$table}]. Ejecuta migraciones antes del seeder.");
            }
        }

        $columns = [
            'actividades' => [
                'actividad_categoria_id',
                'actividad_subcategoria_id',
                'nombre',
                'cantidad',
                'unidad_org_id',
                'fecha',
                'hora',
                'acciones_realizadas',
                'observaciones',
                'personas_alcanzadas',
                'estado_revision',
                'created_at',
                'updated_at',
            ],
            'fomento_cultura_vial_detalles' => array_merge(
                ['actividad_id'],
                self::POPULATION_FIELDS,
                ['total_poblacion_atendida']
            ),
        ];

        foreach ($columns as $table => $requiredColumns) {
            foreach ($requiredColumns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("No existe la columna requerida [{$table}.{$column}]. Ejecuta migraciones antes del seeder.");
                }
            }
        }
    }

    private function ensureUnidadFomento(string $now): void
    {
        $unidad = DB::table('unidades')
            ->where('id', self::UNIDAD_FOMENTO_ID)
            ->first();

        if ($unidad) {
            $payload = [
                'nombre' => 'UNIDAD DE FOMENTO A LA CULTURA VIAL',
                'activa' => true,
                'updated_at' => $now,
            ];

            if (empty($unidad->slug)) {
                $payload['slug'] = 'fomento-cultura-vial';
            }

            DB::table('unidades')
                ->where('id', self::UNIDAD_FOMENTO_ID)
                ->update($this->onlyExistingColumns('unidades', $payload));

            return;
        }

        DB::table('unidades')->insert($this->onlyExistingColumns('unidades', [
            'id' => self::UNIDAD_FOMENTO_ID,
            'nombre' => 'UNIDAD DE FOMENTO A LA CULTURA VIAL',
            'slug' => $this->uniqueSlug('unidades', 'fomento-cultura-vial'),
            'activa' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]));
    }

    private function ensureCatalogoHistorico(string $now): array
    {
        $categoriaId = $this->ensureCategoria('CAPACITACIONES', $now);
        $subcategoriaId = $this->ensureSubcategoria($categoriaId, 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL', $now);
        $programaId = $this->ensurePrograma($subcategoriaId, 'Historico 2025 Seguridad Vial', $now);

        return [
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'programa_id' => $programaId,
            'programa' => 'Historico 2025 Seguridad Vial',
        ];
    }

    private function ensureCategoria(string $nombre, string $now): int
    {
        DB::table('actividad_categorias')->updateOrInsert(
            ['slug' => Str::slug($nombre)],
            $this->onlyExistingColumns('actividad_categorias', [
                'nombre' => $nombre,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
        );

        return (int) DB::table('actividad_categorias')
            ->where('slug', Str::slug($nombre))
            ->value('id');
    }

    private function ensureSubcategoria(int $categoriaId, string $nombre, string $now): int
    {
        DB::table('actividad_subcategorias')->updateOrInsert(
            [
                'actividad_categoria_id' => $categoriaId,
                'slug' => Str::slug($nombre),
            ],
            $this->onlyExistingColumns('actividad_subcategorias', [
                'actividad_categoria_id' => $categoriaId,
                'nombre' => $nombre,
                'slug' => Str::slug($nombre),
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
        );

        return (int) DB::table('actividad_subcategorias')
            ->where('actividad_categoria_id', $categoriaId)
            ->where('slug', Str::slug($nombre))
            ->value('id');
    }

    private function ensurePrograma(int $subcategoriaId, string $nombre, string $now): int
    {
        DB::table('fomento_cultura_vial_programas')->updateOrInsert(
            [
                'actividad_subcategoria_id' => $subcategoriaId,
                'slug' => Str::slug($nombre),
            ],
            $this->onlyExistingColumns('fomento_cultura_vial_programas', [
                'actividad_subcategoria_id' => $subcategoriaId,
                'nombre' => $nombre,
                'slug' => Str::slug($nombre),
                'orden' => 2025,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
        );

        return (int) DB::table('fomento_cultura_vial_programas')
            ->where('actividad_subcategoria_id', $subcategoriaId)
            ->where('slug', Str::slug($nombre))
            ->value('id');
    }

    private function limpiarActividadesPrevias(): void
    {
        $ids = DB::table('actividades')
            ->where('unidad_org_id', self::UNIDAD_FOMENTO_ID)
            ->where('observaciones', 'like', '%' . self::MARKER . '%')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('fomento_cultura_vial_detalles')
            ->whereIn('actividad_id', $ids)
            ->delete();

        DB::table('actividades')
            ->whereIn('id', $ids)
            ->delete();
    }

    private function seedActividades(array $catalogo, string $now): void
    {
        foreach ($this->sourceRows() as $row) {
            $actionSummary = $this->actionSummary($row['acciones']);

            foreach ($this->distributeRow($row) as $index => $detalle) {
                $actividadId = DB::table('actividades')->insertGetId(
                    $this->onlyExistingColumns('actividades', [
                        'client_uuid' => (string) Str::uuid(),
                        'sync_status' => 'local',
                        'actividad_categoria_id' => $catalogo['categoria_id'],
                        'actividad_subcategoria_id' => $catalogo['subcategoria_id'],
                        'nombre' => $this->activityName($row, $index + 1),
                        'cantidad' => 1,
                        'unidad_org_id' => self::UNIDAD_FOMENTO_ID,
                        'fecha' => $row['fecha'],
                        'hora' => $this->timeForIndex($index),
                        'lugar' => 'UNIDAD DE FOMENTO A LA CULTURA VIAL',
                        'municipio' => $row['municipio'],
                        'motivo' => 'Seguridad Vial 2025',
                        'narrativa' => 'Registro historico capturado desde informe anual 2025 de Seguridad Vial.',
                        'acciones_realizadas' => $actionSummary,
                        'observaciones' => self::MARKER . '. Fuente: informe 2025. Periodo fuente: ' . $row['periodo'] . '. Las columnas de acciones del Excel se conservan como resumen fuente porque no son equivalentes uno a uno al No. de Eventos.',
                        'personas_alcanzadas' => $detalle['total_poblacion_atendida'],
                        'personas_participantes' => 0,
                        'personas_detenidas' => 0,
                        'estado_revision' => 'aprobado',
                        'observacion_revision' => 'Registro historico validado contra totales fuente 2025.',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );

                DB::table('fomento_cultura_vial_detalles')->insert(
                    $this->onlyExistingColumns('fomento_cultura_vial_detalles', array_merge($detalle, [
                        'actividad_id' => $actividadId,
                        'fomento_cultura_vial_programa_id' => $catalogo['programa_id'],
                        'programa_nombre' => $catalogo['programa'],
                        'nombre_institucion' => null,
                        'domicilio' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]))
                );
            }
        }
    }

    private function distributeRow(array $row): array
    {
        $count = (int) $row['no'];

        if ($count <= 0) {
            return [];
        }

        $totals = [
            'ninas' => (int) $row['ninas'],
            'ninos' => (int) $row['ninos'],
            'adolescentes_mujeres' => (int) $row['adolescentes_mujeres'],
            'adolescentes_hombres' => (int) $row['adolescentes_hombres'],
            'docentes_hombres' => (int) $row['docentes_hombres'],
            'docentes_mujeres' => (int) $row['docentes_mujeres'],
            'hombres' => (int) $row['hombres'],
            'mujeres' => (int) $row['mujeres'],
        ];

        $items = array_fill(0, $count, []);

        foreach ($totals as $field => $value) {
            $base = intdiv($value, $count);
            $remainder = $value % $count;

            for ($i = 0; $i < $count; $i++) {
                $items[$i][$field] = $base + ($i < $remainder ? 1 : 0);
            }
        }

        for ($i = 0; $i < $count; $i++) {
            $items[$i]['nivel_educativo'] = null;
            $items[$i]['sector'] = null;
            $items[$i]['total_poblacion_atendida'] = array_sum(array_intersect_key(
                $items[$i],
                array_flip(self::POPULATION_FIELDS)
            ));
        }

        return $items;
    }

    private function validateSourceRows(): void
    {
        foreach ($this->sourceRows() as $row) {
            $total = array_sum(array_map(static fn ($field) => (int) $row[$field], self::POPULATION_FIELDS));

            if ($total !== (int) $row['total']) {
                throw new RuntimeException("El renglon [{$row['periodo']}] suma {$total}, pero el total fuente dice {$row['total']}.");
            }
        }

        $this->assertTotals($this->expectedTotals(), $this->calculateSourceTotals(), 'fuente');
    }

    private function validateInsertedTotals(): void
    {
        $row = DB::table('actividades')
            ->join('fomento_cultura_vial_detalles as fomento', 'fomento.actividad_id', '=', 'actividades.id')
            ->where('actividades.unidad_org_id', self::UNIDAD_FOMENTO_ID)
            ->where('actividades.observaciones', 'like', '%' . self::MARKER . '%')
            ->selectRaw('COUNT(*) as eventos, SUM(fomento.total_poblacion_atendida) as total')
            ->first();

        $this->assertTotals([
            'eventos' => $this->expectedTotals()['eventos'],
            'total' => $this->expectedTotals()['total'],
        ], [
            'eventos' => (int) ($row->eventos ?? 0),
            'total' => (int) ($row->total ?? 0),
        ], 'insertado');
    }

    private function calculateSourceTotals(): array
    {
        $totals = [
            'eventos' => 0,
            'total' => 0,
        ];

        foreach (array_keys(self::ACTION_FIELDS) as $field) {
            $totals[$field] = 0;
        }

        foreach ($this->sourceRows() as $row) {
            $totals['eventos'] += (int) $row['no'];
            $totals['total'] += (int) $row['total'];

            foreach (array_keys(self::ACTION_FIELDS) as $field) {
                $totals[$field] += (int) ($row['acciones'][$field] ?? 0);
            }
        }

        return $totals;
    }

    private function assertTotals(array $expected, array $actual, string $context): void
    {
        foreach ($expected as $field => $expectedValue) {
            $actualValue = (int) ($actual[$field] ?? 0);

            if ($actualValue !== (int) $expectedValue) {
                throw new RuntimeException("Total {$context} incorrecto para {$field}: esperado {$expectedValue}, obtenido {$actualValue}.");
            }
        }
    }

    private function expectedTotals(): array
    {
        return [
            'eventos' => 1235,
            'total' => 235148,
            'taller_seguridad_vial' => 332,
            'taller_manejo_defensivo' => 33,
            'taller_gestion_emociones_conduccion' => 5,
            'taller_educacion_vial_manejo_defensivo_ambulancia' => 6,
            'talleres_genero_violencia' => 20,
            'taller_ley_movilidad' => 14,
            'complementarios_modulo_informativo' => 138,
            'clases_umsnh' => 65,
            'capacitacion_elementos' => 46,
            'apoyos_viales' => 11,
            'campanas' => 501,
            'actualizacion_elementos_coordinacion' => 116,
            'modulo_actividades_ludicas_kalli' => 18,
            'taller_alcohol_conduccion' => 36,
        ];
    }

    private function activityName(array $row, int $index): string
    {
        return sprintf(
            'UFCV historico 2025 %s #%03d/%03d',
            Str::limit($row['periodo'], 80, ''),
            $index,
            (int) $row['no']
        );
    }

    private function actionSummary(array $actions): string
    {
        $parts = [];

        foreach (self::ACTION_FIELDS as $field => $label) {
            $value = (int) ($actions[$field] ?? 0);

            if ($value > 0) {
                $parts[] = $label . ': ' . $value;
            }
        }

        return $parts ? implode('; ', $parts) : 'Sin acciones desglosadas en fuente.';
    }

    private function timeForIndex(int $index): string
    {
        $hour = 9 + (int) floor(($index % 48) / 6);
        $minute = ($index % 6) * 10;

        return sprintf('%02d:%02d:00', $hour, $minute);
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        if (!isset($this->schemaColumns[$table])) {
            $this->schemaColumns[$table] = array_flip(Schema::getColumnListing($table));
        }

        return array_intersect_key($payload, $this->schemaColumns[$table]);
    }

    private function uniqueSlug(string $table, string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function sourceRows(): array
    {
        return [
            $this->row('ENERO', '2025-01-31', 75, 2262, 2157, 589, 0, 33, 36, 2598, 2814, 10489, [17, 0, 0, 0, 4, 0, 5, 0, 8, 1, 24, 16, 0, 0]),
            $this->row('FEBRERO', '2025-02-28', 108, 3667, 3566, 4112, 0, 303, 319, 1203, 1302, 14472, [38, 3, 0, 2, 3, 3, 11, 8, 1, 5, 48, 0, 0, 0]),
            $this->row('MARZO', '2025-03-31', 139, 3758, 3691, 8347, 0, 347, 350, 2138, 1434, 20065, [39, 6, 2, 1, 12, 0, 5, 10, 18, 4, 9, 58, 0, 0]),
            $this->row('ABRIL', '2025-04-30', 95, 2654, 2513, 3223, 2802, 252, 257, 3609, 3450, 18760, [49, 2, 1, 1, 0, 2, 8, 7, 18, 1, 22, 7, 0, 0]),
            $this->row('MAYO', '2025-05-31', 82, 7950, 7582, 2351, 2216, 352, 449, 2507, 2622, 26029, [42, 3, 1, 0, 1, 2, 14, 11, 0, 0, 2, 5, 18, 0]),
            $this->row('JUNIO', '2025-06-30', 81, 1911, 1916, 1430, 1345, 132, 228, 4056, 3627, 14645, [34, 5, 0, 1, 0, 1, 3, 4, 0, 0, 29, 4, 0, 0]),
            $this->row('JULIO', '2025-07-31', 105, 705, 617, 40, 40, 0, 0, 9541, 6740, 17683, [9, 3, 1, 0, 0, 5, 10, 0, 0, 0, 72, 5, 0, 0]),
            $this->row('AGOSTO', '2025-08-31', 94, 1608, 1264, 0, 0, 6, 6, 8302, 6901, 18087, [4, 2, 0, 0, 0, 1, 16, 0, 0, 0, 70, 1, 0, 0]),
            $this->row('SEPTIEMBRE', '2025-09-30', 99, 1521, 1256, 3274, 2913, 94, 110, 6440, 7030, 22638, [17, 4, 0, 0, 0, 0, 7, 12, 0, 0, 67, 5, 0, 3]),
            $this->row('OCTUBRE', '2025-10-31', 138, 2827, 2800, 3892, 3863, 214, 279, 4547, 5834, 24256, [37, 3, 0, 0, 0, 0, 20, 9, 0, 0, 51, 9, 0, 17]),
            $this->row('NOVIEMBRE', '2025-11-30', 131, 2028, 1950, 4311, 4960, 948, 272, 8457, 8230, 31156, [32, 2, 0, 0, 0, 0, 32, 4, 0, 0, 43, 5, 0, 16]),
            $this->row('DICIEMBRE', '2025-12-31', 88, 848, 795, 1456, 1438, 67, 78, 5682, 6504, 16868, [14, 0, 0, 1, 0, 0, 7, 0, 1, 0, 64, 1, 0, 0]),
        ];
    }

    private function row(
        string $periodo,
        string $fecha,
        int $no,
        int $ninas,
        int $ninos,
        int $adolescentesMujeres,
        int $adolescentesHombres,
        int $docentesHombres,
        int $docentesMujeres,
        int $hombres,
        int $mujeres,
        int $total,
        array $acciones,
        ?string $municipio = null
    ): array {
        return [
            'periodo' => $periodo,
            'fecha' => $fecha,
            'no' => $no,
            'ninas' => $ninas,
            'ninos' => $ninos,
            'adolescentes_mujeres' => $adolescentesMujeres,
            'adolescentes_hombres' => $adolescentesHombres,
            'docentes_hombres' => $docentesHombres,
            'docentes_mujeres' => $docentesMujeres,
            'hombres' => $hombres,
            'mujeres' => $mujeres,
            'total' => $total,
            'acciones' => array_combine(array_keys(self::ACTION_FIELDS), $acciones),
            'municipio' => $municipio,
        ];
    }
}
