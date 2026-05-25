<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class FomentoCulturaVialHistorico2026Seeder extends Seeder
{
    private const UNIDAD_FOMENTO_ID = 6;
    private const MARKER = 'SEEDER UFCV HISTORICO ENERO-MAYO 2026';
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

    private $schemaColumns = [];

    public function run(): void
    {
        $this->assertRequiredTables();
        $this->validateSourceTotals();

        $now = Carbon::now(self::TZ)->format('Y-m-d H:i:s');

        DB::transaction(function () use ($now) {
            $this->ensureUnidadFomento($now);
            $catalogos = $this->ensureCatalogosFomento($now);

            $this->limpiarActividadesPrevias();
            $this->seedActividades($catalogos, $now);
            $this->validateInsertedTotals();
        });

        if ($this->command) {
            $this->command->info('Seeder UFCV historico enero-mayo 2026 ejecutado correctamente.');
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

    private function ensureCatalogosFomento(string $now): array
    {
        $categoriaId = $this->ensureCategoria('CAPACITACIONES', $now);
        $catalogos = [];
        $ordenPorSubcategoria = [];

        foreach ($this->actionCatalog() as $accion => $item) {
            $subcategoriaId = $this->ensureSubcategoria($categoriaId, $item['subcategoria'], $now);
            $ordenPorSubcategoria[$subcategoriaId] = ($ordenPorSubcategoria[$subcategoriaId] ?? 0) + 1;
            $programaId = $this->ensurePrograma(
                $subcategoriaId,
                $item['programa'],
                $ordenPorSubcategoria[$subcategoriaId],
                $now
            );

            $catalogos[$accion] = [
                'categoria_id' => $categoriaId,
                'subcategoria_id' => $subcategoriaId,
                'programa_id' => $programaId,
                'programa' => $item['programa'],
            ];
        }

        return $catalogos;
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

    private function ensurePrograma(int $subcategoriaId, string $nombre, int $orden, string $now): int
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
                'orden' => $orden,
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

    private function seedActividades(array $catalogos, string $now): void
    {
        foreach ($this->sourceRows() as $month => $rows) {
            $fecha = Carbon::create(2026, $month, 1, 12, 0, 0, self::TZ)
                ->endOfMonth()
                ->toDateString();

            foreach ($rows as $row) {
                if ((int) $row['no'] === 0) {
                    continue;
                }

                $catalogo = $catalogos[$row['accion']] ?? null;

                if (!$catalogo) {
                    throw new RuntimeException("No existe catalogo para [{$row['accion']}].");
                }

                foreach ($this->distributeRow($row) as $index => $detalle) {
                    $actividadId = DB::table('actividades')->insertGetId(
                        $this->onlyExistingColumns('actividades', [
                            'client_uuid' => (string) Str::uuid(),
                            'sync_status' => 'local',
                            'actividad_categoria_id' => $catalogo['categoria_id'],
                            'actividad_subcategoria_id' => $catalogo['subcategoria_id'],
                            'nombre' => $this->activityName($month, $row['accion'], $index + 1, (int) $row['no']),
                            'cantidad' => 1,
                            'unidad_org_id' => self::UNIDAD_FOMENTO_ID,
                            'fecha' => $fecha,
                            'hora' => $this->timeForIndex($index),
                            'lugar' => 'UNIDAD DE FOMENTO A LA CULTURA VIAL',
                            'motivo' => $row['accion'],
                            'narrativa' => 'Registro historico mensual capturado desde informe Excel de la Unidad de Fomento a la Cultura Vial.',
                            'acciones_realizadas' => $row['accion'],
                            'observaciones' => self::MARKER . '. Fuente: informe mensual UFCV 2026. Fecha asignada al cierre de mes por no contar con desglose diario en el Excel.',
                            'personas_alcanzadas' => $detalle['total_poblacion_atendida'],
                            'personas_participantes' => 0,
                            'personas_detenidas' => 0,
                            'estado_revision' => 'aprobado',
                            'observacion_revision' => 'Registro historico validado contra totales mensuales del Excel UFCV enero-mayo 2026.',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                    );

                    DB::table('fomento_cultura_vial_detalles')->insert(
                        $this->onlyExistingColumns('fomento_cultura_vial_detalles', array_merge($detalle, [
                            'actividad_id' => $actividadId,
                            'fomento_cultura_vial_programa_id' => $catalogo['programa_id'],
                            'programa_nombre' => $catalogo['programa'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]))
                    );
                }
            }
        }
    }

    private function distributeRow(array $row): array
    {
        $count = (int) $row['no'];

        if ($count <= 0) {
            return [];
        }

        $items = array_fill(0, $count, []);

        foreach (self::POPULATION_FIELDS as $field) {
            $value = (int) $row[$field];
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

    private function validateSourceTotals(): void
    {
        $totals = $this->emptyTotals();

        foreach ($this->sourceRows() as $month => $rows) {
            $totals[$month] = $this->emptyMonthTotals();

            foreach ($rows as $row) {
                $rowPopulation = $this->rowPopulation($row);

                if ($rowPopulation !== (int) $row['total_poblacion_atendida']) {
                    throw new RuntimeException("El renglon [{$row['accion']}] del mes {$month} suma {$rowPopulation}, pero el total fuente dice {$row['total_poblacion_atendida']}.");
                }

                $totals[$month]['no'] += (int) $row['no'];
                $totals[$month]['total_poblacion_atendida'] += (int) $row['total_poblacion_atendida'];

                foreach (self::POPULATION_FIELDS as $field) {
                    $totals[$month][$field] += (int) $row[$field];
                }
            }
        }

        $this->assertTotals($this->expectedMonthTotals(), $totals, 'fuente');
    }

    private function validateInsertedTotals(): void
    {
        $totals = $this->emptyTotals();

        $rows = DB::table('actividades')
            ->join('fomento_cultura_vial_detalles as fomento', 'fomento.actividad_id', '=', 'actividades.id')
            ->where('actividades.unidad_org_id', self::UNIDAD_FOMENTO_ID)
            ->where('actividades.observaciones', 'like', '%' . self::MARKER . '%')
            ->get(array_merge(
                ['actividades.fecha'],
                array_map(static fn ($field) => 'fomento.' . $field, self::POPULATION_FIELDS),
                ['fomento.total_poblacion_atendida']
            ));

        foreach ($rows as $row) {
            $month = (int) Carbon::parse($row->fecha, self::TZ)->format('n');
            $totals[$month] = $totals[$month] ?? $this->emptyMonthTotals();
            $totals[$month]['no']++;
            $totals[$month]['total_poblacion_atendida'] += (int) $row->total_poblacion_atendida;

            foreach (self::POPULATION_FIELDS as $field) {
                $totals[$month][$field] += (int) $row->{$field};
            }
        }

        $this->assertTotals($this->expectedMonthTotals(), $totals, 'insertado');
    }

    private function assertTotals(array $expected, array $actual, string $context): void
    {
        foreach ($expected as $month => $expectedTotals) {
            $actualTotals = $actual[$month] ?? $this->emptyMonthTotals();

            foreach ($expectedTotals as $field => $expectedValue) {
                $actualValue = (int) ($actualTotals[$field] ?? 0);

                if ($actualValue !== (int) $expectedValue) {
                    throw new RuntimeException("Total {$context} incorrecto para mes {$month}, campo {$field}: esperado {$expectedValue}, obtenido {$actualValue}.");
                }
            }
        }
    }

    private function emptyTotals(): array
    {
        return [
            1 => $this->emptyMonthTotals(),
            2 => $this->emptyMonthTotals(),
            3 => $this->emptyMonthTotals(),
            4 => $this->emptyMonthTotals(),
            5 => $this->emptyMonthTotals(),
        ];
    }

    private function emptyMonthTotals(): array
    {
        return array_merge(
            ['no' => 0],
            array_fill_keys(self::POPULATION_FIELDS, 0),
            ['total_poblacion_atendida' => 0]
        );
    }

    private function rowPopulation(array $row): int
    {
        return array_sum(array_map(static fn ($field) => (int) $row[$field], self::POPULATION_FIELDS));
    }

    private function activityName(int $month, string $accion, int $index, int $total): string
    {
        return sprintf(
            'UFCV historico 2026-%02d %s #%03d/%03d',
            $month,
            Str::limit($accion, 128, ''),
            $index,
            $total
        );
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

    private function actionCatalog(): array
    {
        return [
            'Taller de Seguridad Vial' => [
                'subcategoria' => 'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Taller de Seguridad Vial',
            ],
            'Taller de Manejo Defensivo' => [
                'subcategoria' => 'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Taller de Manejo Defensivo',
            ],
            'Taller para conductor de vehiculo de emergencia' => [
                'subcategoria' => 'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Taller para conductor de vehiculo de emergencia',
            ],
            'Actividades en Stand Ludico' => [
                'subcategoria' => 'MÓDULOS EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Actividades en Stand Ludico',
            ],
            'Actividades en Stand Ludico, Simulacros' => [
                'subcategoria' => 'MÓDULOS EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Actividades en Stand Ludico, Simulacros',
            ],
            'Clases UMSNH' => [
                'subcategoria' => 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Clases UMSNH',
            ],
            'Capacitacion para elementos' => [
                'subcategoria' => 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Capacitacion para elementos',
            ],
            'Apoyos Viales' => [
                'subcategoria' => 'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Apoyos Viales',
            ],
            'Campañas' => [
                'subcategoria' => 'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Campañas',
            ],
            'Actualizacion de elementos de la Coordinacion' => [
                'subcategoria' => 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Actualizacion de elementos de la Coordinacion',
            ],
            'Taller de Proximidad Social para personal operativo' => [
                'subcategoria' => 'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Taller de Proximidad Social para personal operativo',
            ],
            'Taller de movilidad segura en la vía pública' => [
                'subcategoria' => 'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Taller de movilidad segura en la vía pública',
            ],
            'Taller de Ley y Reglamento de la Ley de Movilidad' => [
                'subcategoria' => 'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Taller de Ley y Reglamento de la Ley de Movilidad',
            ],
            'Taller alcohol y conduccion' => [
                'subcategoria' => 'TALLER EDUCACIÓN SEGURIDAD VIAL',
                'programa' => 'Taller alcohol y conduccion',
            ],
        ];
    }

    private function sourceRows(): array
    {
        return [
            1 => [
                $this->row('Taller de Seguridad Vial', 13, 587, 466, 398, 382, 58, 86, 90, 27, 2094),
                $this->row('Taller de Manejo Defensivo', 1, 0, 0, 0, 0, 0, 0, 30, 8, 38),
                $this->row('Taller para conductor de vehiculo de emergencia', 1, 0, 0, 0, 0, 0, 0, 1, 0, 1),
                $this->row('Actividades en Stand Ludico', 1, 31, 37, 0, 0, 5, 5, 0, 0, 78),
                $this->row('Clases UMSNH', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Capacitacion para elementos', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Apoyos Viales', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Campañas', 79, 681, 644, 0, 0, 0, 0, 5262, 5740, 12327),
                $this->row('Actualizacion de elementos de la Coordinacion', 3, 0, 0, 0, 0, 0, 0, 8, 2, 10),
            ],
            2 => [
                $this->row('Taller de Seguridad Vial', 37, 1735, 1811, 1092, 857, 144, 202, 195, 103, 6139),
                $this->row('Taller de Manejo Defensivo', 2, 0, 0, 0, 0, 0, 30, 125, 16, 171),
                $this->row('Taller para conductor de vehiculo de emergencia', 3, 0, 0, 0, 0, 0, 0, 1, 0, 1),
                $this->row('Actividades en Stand Ludico, Simulacros', 12, 458, 593, 1578, 1164, 32, 53, 54, 51, 3983),
                $this->row('Clases UMSNH', 9, 0, 0, 0, 0, 0, 0, 65, 74, 139),
                $this->row('Capacitacion para elementos', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Apoyos Viales', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Campañas', 39, 972, 790, 0, 0, 0, 0, 2745, 3103, 7610),
                $this->row('Actualizacion de elementos de la Coordinacion', 1, 0, 0, 0, 0, 0, 0, 68, 0, 68),
            ],
            3 => [
                $this->row('Taller de Seguridad Vial', 18, 878, 719, 719, 467, 55, 56, 64, 48, 3006),
                $this->row('Taller de Manejo Defensivo', 1, 0, 0, 0, 0, 0, 0, 76, 2, 78),
                $this->row('Taller para conductor de vehiculo de emergencia', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Actividades en Stand Ludico, Simulacros', 4, 274, 217, 459, 287, 0, 4, 43, 35, 1319),
                $this->row('Clases UMSNH', 8, 0, 0, 0, 0, 0, 0, 45, 50, 95),
                $this->row('Taller de Proximidad Social para personal operativo', 1, 0, 0, 0, 0, 0, 1, 5, 25, 31),
                $this->row('Taller de movilidad segura en la vía pública', 2, 0, 0, 129, 97, 0, 0, 21, 13, 260),
                $this->row('Apoyos Viales', 1, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Campañas', 49, 270, 238, 0, 0, 0, 0, 3467, 5109, 9084),
                $this->row('Actualizacion de elementos de la Coordinacion', 2, 0, 0, 0, 0, 0, 0, 2, 4, 6),
                $this->row('Taller de Ley y Reglamento de la Ley de Movilidad', 1, 0, 0, 0, 0, 0, 0, 21, 13, 34),
            ],
            4 => [
                $this->row('Taller de Seguridad Vial', 19, 820, 811, 141, 67, 45, 71, 188, 109, 2252),
                $this->row('Taller de Manejo Defensivo', 2, 3, 4, 134, 68, 0, 0, 12, 0, 221),
                $this->row('Actividades en Stand Ludico', 13, 1387, 1127, 240, 210, 114, 137, 467, 370, 4052),
                $this->row('Clases UMSNH', 7, 0, 0, 0, 0, 0, 0, 45, 50, 95),
                $this->row('Campañas', 33, 55, 55, 262, 328, 0, 0, 3894, 3862, 8456),
                $this->row('Actualizacion de elementos de la Coordinacion', 2, 0, 0, 0, 0, 0, 0, 5, 10, 15),
                $this->row('Taller alcohol y conduccion', 1, 0, 0, 0, 0, 0, 0, 25, 17, 42),
            ],
            5 => [
                $this->row('Taller de Seguridad Vial', 2, 649, 531, 0, 0, 0, 0, 180, 660, 2020),
                $this->row('Taller de Manejo Defensivo', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Taller para conductor de vehiculo de emergencia', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Actividades en Stand Ludico', 4, 1409, 1153, 0, 0, 8, 8, 298, 448, 3324),
                $this->row('Clases UMSNH', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Capacitacion para elementos', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Apoyos Viales', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Campañas', 14, 0, 0, 0, 0, 0, 180, 1207, 1300, 2687),
                $this->row('Actualizacion de elementos de la Coordinacion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Taller de Ley y Reglamento de la Ley de Movilidad', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Taller de Proximidad Social para personal operativo', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
                $this->row('Taller alcohol y conduccion', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
            ],
        ];
    }

    private function row(
        string $accion,
        int $no,
        int $ninas,
        int $ninos,
        int $adolescentesMujeres,
        int $adolescentesHombres,
        int $docentesHombres,
        int $docentesMujeres,
        int $hombres,
        int $mujeres,
        int $total
    ): array {
        return [
            'accion' => $accion,
            'no' => $no,
            'ninas' => $ninas,
            'ninos' => $ninos,
            'adolescentes_mujeres' => $adolescentesMujeres,
            'adolescentes_hombres' => $adolescentesHombres,
            'docentes_hombres' => $docentesHombres,
            'docentes_mujeres' => $docentesMujeres,
            'hombres' => $hombres,
            'mujeres' => $mujeres,
            'total_poblacion_atendida' => $total,
        ];
    }

    private function expectedMonthTotals(): array
    {
        return [
            1 => $this->total(98, 1299, 1147, 398, 382, 63, 91, 5391, 5777, 14548),
            2 => $this->total(103, 3165, 3194, 2670, 2021, 176, 285, 3253, 3347, 18111),
            3 => $this->total(87, 1422, 1174, 1307, 851, 55, 61, 3744, 5299, 13913),
            4 => $this->total(77, 2265, 1997, 777, 673, 159, 208, 4636, 4418, 15133),
            5 => $this->total(20, 2058, 1684, 0, 0, 8, 188, 1685, 2408, 8031),
        ];
    }

    private function total(
        int $no,
        int $ninas,
        int $ninos,
        int $adolescentesMujeres,
        int $adolescentesHombres,
        int $docentesHombres,
        int $docentesMujeres,
        int $hombres,
        int $mujeres,
        int $total
    ): array {
        return [
            'no' => $no,
            'ninas' => $ninas,
            'ninos' => $ninos,
            'adolescentes_mujeres' => $adolescentesMujeres,
            'adolescentes_hombres' => $adolescentesHombres,
            'docentes_hombres' => $docentesHombres,
            'docentes_mujeres' => $docentesMujeres,
            'hombres' => $hombres,
            'mujeres' => $mujeres,
            'total_poblacion_atendida' => $total,
        ];
    }
}
