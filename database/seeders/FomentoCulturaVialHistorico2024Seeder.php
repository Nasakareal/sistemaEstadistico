<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class FomentoCulturaVialHistorico2024Seeder extends Seeder
{
    private const UNIDAD_FOMENTO_ID = 6;
    private const MARKER = 'SEEDER UFCV HISTORICO 2024';
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
        'taller_seguridad_vial_derecho_humano' => 'Taller Seguridad Vial como Derecho Humano',
        'taller_gestion_emociones_conduccion' => 'Taller de Gestion de emociones en la conduccion',
        'taller_genero_movilidad' => 'Taller de Genero y Movilidad',
        'taller_complementarios' => 'Taller Complementarios',
        'clases_umsnh' => 'Clases UMSNH',
        'capacitacion_elementos_area' => 'Capacitacion para elementos del area',
        'apoyos_viales' => 'Apoyos Viales',
        'campanas' => 'Campañas',
        'actualizacion_elementos_coordinacion' => 'Actualizacion de elementos de la Coordinacion',
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
            $this->command->info('Seeder UFCV historico 2024 ejecutado correctamente.');
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
        $programaId = $this->ensurePrograma($subcategoriaId, 'Historico 2024 Cultura Vial', $now);

        return [
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'programa_id' => $programaId,
            'programa' => 'Historico 2024 Cultura Vial',
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
                'orden' => 2024,
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
                        'motivo' => 'Cultura Vial 2024',
                        'narrativa' => 'Registro historico capturado desde informe anual 2024 de Cultura Vial.',
                        'acciones_realizadas' => $actionSummary,
                        'observaciones' => self::MARKER . '. Fuente: informe 2024. Periodo fuente: ' . $row['periodo'] . '. Las columnas de acciones del Excel se conservan como resumen fuente porque no son equivalentes uno a uno al No. de Eventos. Los adolescentes vienen sin desglose de genero y se distribuyen tecnicamente 50/50 para adaptarse al esquema actual.',
                        'personas_alcanzadas' => $detalle['total_poblacion_atendida'],
                        'personas_participantes' => 0,
                        'personas_detenidas' => 0,
                        'estado_revision' => 'aprobado',
                        'observacion_revision' => 'Registro historico validado contra totales fuente 2024.',
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

        $adolescentesMujeres = intdiv((int) $row['adolescentes'], 2) + ((int) $row['adolescentes'] % 2);
        $adolescentesHombres = intdiv((int) $row['adolescentes'], 2);

        $totals = [
            'ninas' => (int) $row['ninas'],
            'ninos' => (int) $row['ninos'],
            'adolescentes_mujeres' => $adolescentesMujeres,
            'adolescentes_hombres' => $adolescentesHombres,
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
            $total = (int) $row['ninas']
                + (int) $row['ninos']
                + (int) $row['adolescentes']
                + (int) $row['docentes_hombres']
                + (int) $row['docentes_mujeres']
                + (int) $row['hombres']
                + (int) $row['mujeres'];

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
            'eventos' => 1067,
            'total' => 232906,
            'taller_seguridad_vial' => 286,
            'taller_manejo_defensivo' => 50,
            'taller_seguridad_vial_derecho_humano' => 1,
            'taller_gestion_emociones_conduccion' => 11,
            'taller_genero_movilidad' => 27,
            'taller_complementarios' => 94,
            'clases_umsnh' => 36,
            'capacitacion_elementos_area' => 132,
            'apoyos_viales' => 18,
            'campanas' => 351,
            'actualizacion_elementos_coordinacion' => 91,
        ];
    }

    private function activityName(array $row, int $index): string
    {
        return sprintf(
            'UFCV historico 2024 %s #%03d/%03d',
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
            $this->row('ENERO', '2024-01-31', 34, 1853, 1769, 357, 26, 48, 1310, 1231, 6594, [10, 1, 0, 1, 0, 0, 0, 9, 0, 12, 0]),
            $this->row('FEBRERO', '2024-02-29', 69, 3350, 3148, 3472, 137, 178, 2538, 3041, 15864, [35, 0, 0, 1, 0, 6, 4, 9, 2, 8, 0]),
            $this->row('MARZO', '2024-03-31', 97, 1327, 1471, 4110, 157, 234, 5193, 6199, 18691, [32, 3, 0, 0, 11, 1, 4, 11, 0, 42, 0]),
            $this->row('ABRIL', '2024-04-30', 102, 5496, 5413, 4461, 147, 183, 4792, 5017, 25509, [37, 10, 0, 1, 3, 18, 2, 10, 1, 37, 0]),
            $this->row('MAYO', '2024-05-31', 79, 8149, 8170, 5151, 267, 284, 2583, 2694, 27298, [20, 5, 0, 1, 3, 26, 2, 14, 0, 8, 0]),
            $this->row('JUNIO', '2024-06-30', 74, 2474, 2349, 4172, 208, 247, 2071, 2426, 13947, [26, 3, 0, 3, 4, 3, 4, 18, 1, 12, 0]),
            $this->row('JULIO', '2024-07-31', 93, 213, 199, 0, 0, 45, 6402, 6252, 13111, [6, 2, 0, 0, 0, 4, 0, 29, 2, 46, 0]),
            $this->row('AGOSTO', '2024-08-31', 130, 1486, 1519, 927, 62, 62, 11338, 11234, 26628, [8, 2, 1, 0, 0, 13, 0, 2, 11, 81, 22]),
            $this->row('SEPTIEMBRE', '2024-09-30', 85, 3144, 3063, 5637, 199, 219, 843, 859, 13964, [29, 5, 0, 2, 4, 2, 7, 15, 1, 7, 20]),
            $this->row('OCTUBRE', '2024-10-31', 96, 997, 1014, 9844, 322, 322, 1698, 1840, 16037, [43, 5, 0, 1, 0, 4, 7, 3, 0, 11, 23]),
            $this->row('NOVIEMBRE', '2024-11-30', 108, 1251, 1267, 5085, 236, 259, 16287, 16092, 40477, [27, 2, 0, 1, 2, 7, 5, 6, 0, 38, 18]),
            $this->row('DICIEMBRE', '2024-12-31', 100, 282, 286, 1483, 27, 28, 6557, 6123, 14786, [13, 12, 0, 0, 0, 10, 1, 6, 0, 49, 8]),
        ];
    }

    private function row(
        string $periodo,
        string $fecha,
        int $no,
        int $ninas,
        int $ninos,
        int $adolescentes,
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
            'adolescentes' => $adolescentes,
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
