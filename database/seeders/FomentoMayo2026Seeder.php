<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FomentoMayo2026Seeder extends Seeder
{
    private const UNIDAD_FOMENTO_ID = 6;
    private const OBSERVACION_SEEDER = 'SEEDER FOMENTO MAYO 2026';

    public function run(): void
    {
        $tz = 'America/Mexico_City';
        $now = Carbon::now($tz)->format('Y-m-d H:i:s');

        DB::transaction(function () use ($now, $tz) {
            $this->ensureUnidadFomento($now);
            $catalogos = $this->ensureCatalogosFomento($now);
            $turnos = $this->ensureTurnosEstadoFuerza($now);
            $this->seedPersonal($now, $turnos);
            $this->seedIncidenciasEstadoFuerza($now);
            $this->limpiarActividadesPrevias();
            $this->seedActividades($catalogos, $now, $tz);
        });
    }

    private function ensureUnidadFomento(string $now): void
    {
        DB::table('unidades')->updateOrInsert(
            ['id' => self::UNIDAD_FOMENTO_ID],
            [
                'nombre' => 'FOMENTO A LA CULTURA VIAL',
                'slug' => 'cultura-vial',
                'activa' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    private function ensureCatalogosFomento(string $now): array
    {
        $categoriaId = $this->ensureCategoria('CAPACITACIONES', $now);

        $programasPorSubcategoria = [
            'TALLER EDUCACIÓN SEGURIDAD VIAL' => [
                'Taller Educación Vial',
                'Taller de Manejo Defensivo',
                'Taller de movilidad segura en la vía pública',
                'Taller de Promotores Escolares',
            ],
            'CAMPAÑA EDUCACIÓN SEGURIDAD VIAL' => [
                'Campaña de Sensibilización',
                'Infancias seguras en la vía pública',
                'Primero el Peatón',
                'Uso del Casco',
            ],
            'CAPACITACIONES EDUCACIÓN SEGURIDAD VIAL' => [
                'Capacitaciones para elementos de nuevo ingreso',
                'Actualizacion para elementos de la Coordinación de Seguridad Vial',
            ],
            'MÓDULOS EDUCACIÓN SEGURIDAD VIAL' => [
                'Modulo de Lúdico',
                'Simulacro de hecho de tránsito',
            ],
        ];

        $catalogos = [];

        foreach ($programasPorSubcategoria as $subcategoriaNombre => $programas) {
            $subcategoriaId = $this->ensureSubcategoria($categoriaId, $subcategoriaNombre, $now);

            foreach ($programas as $index => $programaNombre) {
                $programaId = $this->ensurePrograma($subcategoriaId, $programaNombre, $index + 1, $now);
                $catalogos[] = [
                    'categoria_id' => $categoriaId,
                    'subcategoria_id' => $subcategoriaId,
                    'subcategoria' => $subcategoriaNombre,
                    'programa_id' => $programaId,
                    'programa' => $programaNombre,
                ];
            }
        }

        return $catalogos;
    }

    private function ensureCategoria(string $nombre, string $now): int
    {
        DB::table('actividad_categorias')->updateOrInsert(
            ['slug' => Str::slug($nombre)],
            [
                'nombre' => $nombre,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) DB::table('actividad_categorias')
            ->where('slug', Str::slug($nombre))
            ->value('id');
    }

    private function ensureSubcategoria(int $categoriaId, string $nombre, string $now): int
    {
        $payload = [
            'actividad_categoria_id' => $categoriaId,
            'nombre' => $nombre,
            'activo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('actividad_subcategorias')->updateOrInsert(
            ['slug' => Str::slug($nombre)],
            $this->onlyExistingColumns('actividad_subcategorias', $payload)
        );

        return (int) DB::table('actividad_subcategorias')
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
            [
                'nombre' => $nombre,
                'orden' => $orden,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        return (int) DB::table('fomento_cultura_vial_programas')
            ->where('actividad_subcategoria_id', $subcategoriaId)
            ->where('slug', Str::slug($nombre))
            ->value('id');
    }

    private function ensureTurnosEstadoFuerza(string $now): array
    {
        $turnos = [
            'presente' => [
                'nombre' => 'FOMENTO PRESENTE SEED',
                'slug' => 'fomento-presente-seed',
                'tipo_rol' => 'SIEMPRE',
                'ciclo_inicio' => null,
                'trabajo_horas' => null,
                'descanso_horas' => null,
            ],
            'franco' => [
                'nombre' => 'FOMENTO FRANCO SEED',
                'slug' => 'fomento-franco-seed',
                'tipo_rol' => '24X24',
                'ciclo_inicio' => '2026-05-30 18:00:00',
                'trabajo_horas' => 24,
                'descanso_horas' => 24,
            ],
        ];

        foreach ($turnos as $turno) {
            DB::table('turnos')->updateOrInsert(
                ['slug' => $turno['slug']],
                $this->onlyExistingColumns('turnos', [
                    'nombre' => $turno['nombre'],
                    'activo' => true,
                    'tipo_rol' => $turno['tipo_rol'],
                    'ciclo_inicio' => $turno['ciclo_inicio'],
                    'trabajo_horas' => $turno['trabajo_horas'],
                    'descanso_horas' => $turno['descanso_horas'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        return [
            'presente' => (int) DB::table('turnos')->where('slug', 'fomento-presente-seed')->value('id'),
            'franco' => (int) DB::table('turnos')->where('slug', 'fomento-franco-seed')->value('id'),
        ];
    }

    private function seedPersonal(string $now, array $turnos): void
    {
        $personal = [
            ['10101', 'AURORA', 'MARTINEZ', 'RIVERA', 'INSPECTOR', 'COORDINACION OPERATIVA', 'OPERATIVO'],
            ['10102', 'DANIEL', 'PEREZ', 'SALGADO', 'OFICIAL', 'INSTRUCTOR VIAL', 'OPERATIVO'],
            ['10103', 'ELENA', 'GARCIA', 'MENDOZA', 'OFICIAL', 'INSTRUCTOR VIAL', 'OPERATIVO'],
            ['10104', 'MIGUEL', 'HERNANDEZ', 'LOPEZ', 'POLICIA', 'PROMOTOR VIAL', 'OPERATIVO'],
            ['10105', 'SOFIA', 'RAMIREZ', 'TORRES', 'POLICIA', 'PROMOTOR VIAL', 'OPERATIVO'],
            ['10106', 'JORGE', 'CRUZ', 'VARGAS', 'POLICIA', 'PROMOTOR VIAL', 'OPERATIVO'],
            ['10107', 'KAREN', 'FLORES', 'NUÑEZ', 'AUXILIAR', 'APOYO ADMINISTRATIVO', 'ADMINISTRATIVO'],
            ['10108', 'ROBERTO', 'SANCHEZ', 'DIAZ', 'AUXILIAR', 'LOGISTICA', 'ADMINISTRATIVO'],
        ];

        foreach ($personal as $index => [$numero, $nombre, $paterno, $materno, $grado, $puesto, $categoria]) {
            $curp = 'FOMENTOSEED' . str_pad((string) ($index + 1), 7, '0', STR_PAD_LEFT);

            DB::table('personals')->updateOrInsert(
                ['curp' => $curp],
                $this->onlyExistingColumns('personals', [
                    'unidad_id' => self::UNIDAD_FOMENTO_ID,
                    'turno_id' => $numero === '10106' ? $turnos['franco'] : $turnos['presente'],
                    'patrulla_id' => null,
                    'user_id' => null,
                    'numero_empleado' => $numero,
                    'nombre' => $nombre,
                    'ap_paterno' => $paterno,
                    'ap_materno' => $materno,
                    'rfc' => null,
                    'cuip' => 'FCVSEED' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'cup' => 'CUP-FCV-SEED-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'grado' => $grado,
                    'puesto' => $puesto,
                    'adscripcion' => 'UNIDAD DE FOMENTO A LA CULTURA VIAL',
                    'area' => 'FOMENTO A LA CULTURA VIAL',
                    'categoria' => $categoria,
                    'foto' => null,
                    'estatus' => 'ACTIVO',
                    'fecha_ingreso' => '2024-01-15',
                    'fecha_baja' => null,
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }

    private function seedIncidenciasEstadoFuerza(string $now): void
    {
        $tipos = $this->ensureIncidenciaTipos($now);

        $personalIds = DB::table('personals')
            ->where('unidad_id', self::UNIDAD_FOMENTO_ID)
            ->whereIn('numero_empleado', ['10102', '10103', '10104', '10105'])
            ->pluck('id', 'numero_empleado');

        DB::table('personal_incidencias')
            ->whereIn('personal_id', $personalIds->values())
            ->where('observaciones', self::OBSERVACION_SEEDER)
            ->delete();

        $incidencias = [
            '10102' => 'VACACIONES',
            '10103' => 'INCAPACIDAD',
            '10104' => 'PERMISO',
            '10105' => 'COMISION',
        ];

        foreach ($incidencias as $numeroEmpleado => $claveTipo) {
            $personalId = $personalIds[$numeroEmpleado] ?? null;
            $tipoId = $tipos[$claveTipo] ?? null;

            if (!$personalId || !$tipoId) {
                continue;
            }

            DB::table('personal_incidencias')->insert([
                'personal_id' => $personalId,
                'incidencia_tipo_id' => $tipoId,
                'fecha_inicio' => '2026-05-20',
                'fecha_fin' => '2026-05-31',
                'hora_inicio' => null,
                'hora_fin' => null,
                'folio' => 'FCV-SEED-' . $claveTipo,
                'motivo' => 'Incidencia de prueba para estado de fuerza Fomento.',
                'observaciones' => self::OBSERVACION_SEEDER,
                'documento_id' => null,
                'activo' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function ensureIncidenciaTipos(string $now): array
    {
        $tipos = [
            'VACACIONES' => 'VACACIONES',
            'INCAPACIDAD' => 'INCAPACIDAD',
            'PERMISO' => 'PERMISO',
            'FALTA' => 'FALTA',
            'COMISION' => 'COMISION',
            'CURSOS' => 'CURSOS',
            'OTRO' => 'OTRO',
        ];

        foreach ($tipos as $clave => $nombre) {
            DB::table('incidencia_tipos')->updateOrInsert(
                ['clave' => $clave],
                [
                    'nombre' => $nombre,
                    'categoria' => 'PERSONAL',
                    'descuenta' => in_array($clave, ['FALTA'], true),
                    'requiere_documento' => false,
                    'activo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        return DB::table('incidencia_tipos')
            ->whereIn('clave', array_keys($tipos))
            ->pluck('id', 'clave')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function limpiarActividadesPrevias(): void
    {
        $ids = DB::table('actividades')
            ->where('unidad_org_id', self::UNIDAD_FOMENTO_ID)
            ->where('observaciones', self::OBSERVACION_SEEDER)
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

    private function seedActividades(array $catalogos, string $now, string $tz): void
    {
        $escenarios = [
            ['Taller Educación Vial', 'PRIMARIA', 'PUBLICO', 'ESCUELA PRIMARIA LAZARO CARDENAS', 'MORELIA'],
            ['Uso del Casco', null, 'PUBLICO', 'AVENIDA MADERO PONIENTE', 'MORELIA'],
            ['Capacitaciones para elementos de nuevo ingreso', null, 'PUBLICO', 'INSTITUTO ESTATAL DE FORMACION POLICIAL', 'MORELIA'],
            ['Modulo de Lúdico', 'PREESCOLAR', 'PUBLICO', 'JARDIN DE NINOS MIGUEL HIDALGO', 'MORELIA'],
        ];

        $inicio = Carbon::create(2026, 5, 20, 0, 0, 0, $tz);
        $fin = Carbon::create(2026, 5, 31, 0, 0, 0, $tz);
        $fecha = $inicio->copy();

        while ($fecha->lte($fin)) {
            foreach ($escenarios as $escenarioIndex => [$programaNombre, $nivel, $sector, $lugar, $municipio]) {
                $catalogo = $this->catalogoPorPrograma($catalogos, $programaNombre);
                $hora = $fecha->copy()->setTime(9 + ($escenarioIndex * 2), ($fecha->day + $escenarioIndex * 11) % 60, 0);
                $detalle = $this->detallePoblacion($fecha->day, $escenarioIndex);

                $actividadId = DB::table('actividades')->insertGetId([
                    'client_uuid' => (string) Str::uuid(),
                    'sync_status' => 'local',
                    'sync_error' => null,
                    'synced_at' => null,
                    'actividad_categoria_id' => $catalogo['categoria_id'],
                    'actividad_subcategoria_id' => $catalogo['subcategoria_id'],
                    'nombre' => 'Fomento CV - ' . $programaNombre . ' - ' . $fecha->format('Y-m-d'),
                    'cantidad' => 1,
                    'foto_path' => null,
                    'foto_nombre_original' => null,
                    'foto_hash' => null,
                    'foto_thumbnail_path' => null,
                    'foto_archivo_zip_path' => null,
                    'foto_archivada_at' => null,
                    'foto_eliminada_at' => null,
                    'created_by' => null,
                    'updated_by' => null,
                    'estado_revision' => 'aprobado',
                    'revisado_por' => null,
                    'revisado_at' => null,
                    'observacion_revision' => null,
                    'unidad_org_id' => self::UNIDAD_FOMENTO_ID,
                    'delegacion_id' => null,
                    'destacamento_id' => null,
                    'fecha' => $fecha->format('Y-m-d'),
                    'hora' => $hora->format('H:i:s'),
                    'lugar' => $lugar,
                    'municipio' => $municipio,
                    'carretera' => null,
                    'tramo' => null,
                    'kilometro' => null,
                    'lat' => null,
                    'lng' => null,
                    'km_recorridos' => number_format(1.5 + $escenarioIndex + (($fecha->day % 5) * 0.4), 2, '.', ''),
                    'coordenadas_texto' => null,
                    'fuente_ubicacion' => null,
                    'nota_geo' => null,
                    'motivo' => mb_strtoupper($programaNombre),
                    'narrativa' => 'ACTIVIDAD DE PRUEBA PARA VALIDAR EL EXCEL DE FOMENTO A LA CULTURA VIAL.',
                    'acciones_realizadas' => $this->accionesPorPrograma($programaNombre),
                    'observaciones' => self::OBSERVACION_SEEDER,
                    'personas_alcanzadas' => $detalle['total_poblacion_atendida'],
                    'personas_participantes' => 2 + (($fecha->day + $escenarioIndex) % 4),
                    'personas_detenidas' => 0,
                    'elementos_participantes_texto' => 'PERSONAL DE FOMENTO A LA CULTURA VIAL',
                    'patrullas_participantes_texto' => 'N/A',
                    'created_at' => $hora->format('Y-m-d H:i:s'),
                    'updated_at' => $now,
                ]);

                DB::table('fomento_cultura_vial_detalles')->insert([
                    'actividad_id' => $actividadId,
                    'fomento_cultura_vial_programa_id' => $catalogo['programa_id'],
                    'nivel_educativo' => $nivel,
                    'sector' => $sector,
                    'programa_nombre' => $programaNombre,
                    'ninas' => $detalle['ninas'],
                    'ninos' => $detalle['ninos'],
                    'adolescentes_mujeres' => $detalle['adolescentes_mujeres'],
                    'adolescentes_hombres' => $detalle['adolescentes_hombres'],
                    'docentes_hombres' => $detalle['docentes_hombres'],
                    'docentes_mujeres' => $detalle['docentes_mujeres'],
                    'hombres' => $detalle['hombres'],
                    'mujeres' => $detalle['mujeres'],
                    'total_poblacion_atendida' => $detalle['total_poblacion_atendida'],
                    'created_at' => $hora->format('Y-m-d H:i:s'),
                    'updated_at' => $now,
                ]);
            }

            $fecha->addDay();
        }
    }

    private function catalogoPorPrograma(array $catalogos, string $programaNombre): array
    {
        foreach ($catalogos as $catalogo) {
            if ($catalogo['programa'] === $programaNombre) {
                return $catalogo;
            }
        }

        return $catalogos[0];
    }

    private function detallePoblacion(int $dia, int $escenarioIndex): array
    {
        $base = ($dia % 6) + ($escenarioIndex * 3);
        $detalle = [
            'ninas' => 8 + $base,
            'ninos' => 9 + $base,
            'adolescentes_mujeres' => 5 + $escenarioIndex + ($dia % 3),
            'adolescentes_hombres' => 6 + $escenarioIndex + ($dia % 4),
            'docentes_hombres' => 1 + ($escenarioIndex % 2),
            'docentes_mujeres' => 2 + ($dia % 2),
            'hombres' => 10 + $base,
            'mujeres' => 12 + $base,
        ];

        $detalle['total_poblacion_atendida'] = array_sum($detalle);

        return $detalle;
    }

    private function accionesPorPrograma(string $programaNombre): string
    {
        if (str_contains(mb_strtoupper($programaNombre), 'CASCO')) {
            return 'SE REALIZO CAMPAÑA DE SENSIBILIZACION SOBRE EL USO CORRECTO DEL CASCO.';
        }

        if (str_contains(mb_strtoupper($programaNombre), 'LUDICO')) {
            return 'SE INSTALO MODULO LUDICO CON ACTIVIDADES DE EDUCACION VIAL.';
        }

        if (str_contains(mb_strtoupper($programaNombre), 'NUEVO INGRESO')) {
            return 'SE IMPARTIO CAPACITACION A ELEMENTOS DE NUEVO INGRESO.';
        }

        return 'SE IMPARTIO TALLER DE EDUCACION VIAL Y MOVILIDAD SEGURA.';
    }

    private function onlyExistingColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }
}
