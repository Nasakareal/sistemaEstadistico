<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class FomentoCulturaVialHistorico2023Seeder extends Seeder
{
    private const UNIDAD_FOMENTO_ID = 6;
    private const MARKER = 'SEEDER UFCV HISTORICO 2023';
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
            $this->command->info('Seeder UFCV historico 2023 ejecutado correctamente.');
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

    private function ensureCatalogoHistorico(string $now): array
    {
        $categoriaId = $this->ensureCategoria('CAPACITACIONES', $now);
        $subcategoriaId = $this->ensureSubcategoria($categoriaId, 'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL', $now);
        $programaId = $this->ensurePrograma($subcategoriaId, 'Historico 2023 Educacion y Proyectos Tecnicos', $now);

        return [
            'categoria_id' => $categoriaId,
            'subcategoria_id' => $subcategoriaId,
            'programa_id' => $programaId,
            'programa' => 'Historico 2023 Educacion y Proyectos Tecnicos',
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
                'orden' => 2023,
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
                        'motivo' => 'Educacion y Proyectos Tecnicos 2023',
                        'narrativa' => 'Registro historico capturado desde informe anual 2023 de Educacion y Proyectos Tecnicos.',
                        'acciones_realizadas' => $row['periodo'],
                        'observaciones' => self::MARKER . '. Fuente: informe 2023. Periodo fuente: ' . $row['periodo'] . '. Los adolescentes vienen sin desglose de genero en el Excel y se distribuyen tecnicamente 50/50 para adaptarse al esquema actual.',
                        'personas_alcanzadas' => $detalle['total_poblacion_atendida'],
                        'personas_participantes' => 0,
                        'personas_detenidas' => 0,
                        'estado_revision' => 'aprobado',
                        'observacion_revision' => 'Registro historico validado contra totales fuente 2023.',
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

        $this->assertTotals($this->expectedTotals(), [
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

        foreach ($this->sourceRows() as $row) {
            $totals['eventos'] += (int) $row['no'];
            $totals['total'] += (int) $row['total'];
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
            'eventos' => 707,
            'total' => 85021,
        ];
    }

    private function activityName(array $row, int $index): string
    {
        return sprintf(
            'UFCV historico 2023 %s #%03d/%03d',
            Str::limit($row['periodo'], 80, ''),
            $index,
            (int) $row['no']
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

    private function sourceRows(): array
    {
        return [
            $this->row('ENERO', '2023-01-31', 37, 406, 431, 988, 15, 19, 320, 179, 2358),
            $this->row('FEBRERO', '2023-02-28', 20, 776, 708, 1817, 27, 62, 162, 93, 3645),
            $this->row('MARZO', '2023-03-31', 53, 1024, 1009, 4647, 49, 156, 628, 535, 8048),
            $this->row('ABRIL', '2023-04-30', 62, 1383, 1325, 1868, 32, 41, 1618, 1366, 7633),
            $this->row('MAYO', '2023-05-31', 83, 2580, 2609, 5349, 179, 184, 324, 290, 11515),
            $this->row('JUNIO', '2023-06-30', 75, 1807, 1811, 6395, 236, 296, 473, 189, 11207),
            $this->row('JULIO', '2023-07-31', 45, 772, 683, 109, 12, 49, 335, 141, 2101),
            $this->row('AGOSTO', '2023-08-31', 58, 895, 987, 0, 0, 0, 929, 905, 3716),
            $this->row('SEPTIEMBRE', '2023-09-30', 41, 710, 650, 2651, 88, 85, 333, 200, 4717),
            $this->row('OCTUBRE', '2023-10-31', 82, 752, 739, 4152, 126, 139, 1042, 1089, 8039),
            $this->row('NOVIEMBRE', '2023-11-30', 79, 2138, 1998, 4105, 134, 155, 508, 427, 9465),
            $this->row('DICIEMBRE', '2023-12-31', 32, 602, 593, 1556, 8, 13, 917, 1308, 4997),
            $this->row('ZAMORA JUN-DIC', '2023-12-31', 40, 2874, 2721, 1589, 115, 121, 101, 59, 7580, 'ZAMORA'),
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
            'municipio' => $municipio,
        ];
    }
}
