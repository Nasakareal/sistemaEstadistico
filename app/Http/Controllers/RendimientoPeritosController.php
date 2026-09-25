<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RendimientoPeritosController extends Controller
{
    private const UNIDAD_SINIESTROS_ID = 1;

    public function index(Request $request)
    {
        $validated = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'turno' => ['nullable', 'in:A,B'],
            'perito_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $hasta = Carbon::createFromFormat('Y-m-d', $validated['hasta'] ?? now('America/Mexico_City')->toDateString())
            ->startOfDay();
        $desde = Carbon::createFromFormat('Y-m-d', $validated['desde'] ?? $hasta->copy()->subDays(29)->toDateString())
            ->startOfDay();

        // Evita consultas accidentales excesivamente grandes desde el tablero.
        if ($desde->diffInDays($hasta) > 730) {
            $desde = $hasta->copy()->subDays(730);
        }

        $turno = $validated['turno'] ?? null;
        $peritoId = isset($validated['perito_id']) ? (int) $validated['perito_id'] : null;

        $peritos = $this->peritosDisponibles();
        if ($peritoId && !$peritos->contains('id', $peritoId)) {
            $peritoId = null;
        }

        $base = $this->capturasPeritosQuery($desde, $hasta, $turno, $peritoId);
        $baseActividades = $this->actividadesPeritosQuery($desde, $hasta, $turno, $peritoId);
        $baseCombinada = $this->capturasCombinadasQuery($desde, $hasta, $turno, $peritoId);

        $resumen = (clone $base)->selectRaw(<<<'SQL'
            COUNT(*) AS total,
            COUNT(DISTINCT users.id) AS peritos_activos,
            COUNT(DISTINCT DATE(hechos.created_at)) AS dias_con_captura,
            SUM(CASE WHEN hechos.captura_completa = 1 THEN 1 ELSE 0 END) AS completas,
            SUM(CASE WHEN hechos.captura_completa = 0 THEN 1 ELSE 0 END) AS incompletas,
            SUM(CASE WHEN LOWER(COALESCE(hechos.estado_revision, '')) = 'aprobado' THEN 1 ELSE 0 END) AS aprobadas,
            SUM(CASE WHEN LOWER(COALESCE(hechos.estado_revision, '')) = 'rechazado' THEN 1 ELSE 0 END) AS rechazadas,
            SUM(CASE WHEN hechos.lat IS NOT NULL AND hechos.lng IS NOT NULL THEN 1 ELSE 0 END) AS con_ubicacion,
            SUM(CASE WHEN hechos.captura_completa_at IS NOT NULL
                       AND hechos.captura_completa_at >= hechos.created_at
                       AND TIMESTAMPDIFF(HOUR, hechos.created_at, hechos.captura_completa_at) <= 24
                     THEN 1 ELSE 0 END) AS completas_24h,
            AVG(CASE WHEN hechos.captura_completa_at IS NOT NULL
                       AND hechos.captura_completa_at >= hechos.created_at
                     THEN TIMESTAMPDIFF(MINUTE, hechos.created_at, hechos.captura_completa_at) END) AS minutos_promedio_cierre,
            SUM(COALESCE(hechos.vehiculos_esperados, 0) + COALESCE(hechos.conductores_esperados, 0) + COALESCE(hechos.lesionados_esperados, 0)) AS elementos_esperados,
            SUM(COALESCE(hechos.vehiculos_capturados, 0) + COALESCE(hechos.conductores_capturados, 0) + COALESCE(hechos.lesionados_capturados, 0)) AS elementos_capturados
        SQL)->first();

        $resumenCombinado = (clone $baseCombinada)->selectRaw(<<<'SQL'
            COUNT(*) AS total,
            SUM(CASE WHEN origen = 'hecho' THEN 1 ELSE 0 END) AS hechos,
            SUM(CASE WHEN origen = 'actividad' THEN 1 ELSE 0 END) AS actividades,
            COUNT(DISTINCT user_id) AS peritos_activos,
            COUNT(DISTINCT DATE(created_at)) AS dias_con_captura,
            SUM(con_ubicacion) AS con_ubicacion,
            SUM(aprobada) AS aprobadas,
            SUM(rechazada) AS rechazadas,
            SUM(cantidad) AS cantidad_actividades,
            SUM(personas_alcanzadas) AS personas_alcanzadas,
            SUM(personas_participantes) AS personas_participantes
        SQL)->first();

        $totalHechos = (int) ($resumen->total ?? 0);
        $totalActividades = (int) ($resumenCombinado->actividades ?? 0);
        $total = (int) ($resumenCombinado->total ?? 0);
        $completas = (int) ($resumen->completas ?? 0);
        $diasPeriodo = $desde->diffInDays($hasta) + 1;
        $peritosActivos = (int) ($resumenCombinado->peritos_activos ?? 0);

        $kpis = [
            'total' => $total,
            'hechos' => $totalHechos,
            'actividades' => $totalActividades,
            'cantidad_actividades' => (int) ($resumenCombinado->cantidad_actividades ?? 0),
            'personas_alcanzadas' => (int) ($resumenCombinado->personas_alcanzadas ?? 0),
            'personas_participantes' => (int) ($resumenCombinado->personas_participantes ?? 0),
            'peritos_activos' => $peritosActivos,
            'dias_periodo' => $diasPeriodo,
            'dias_con_captura' => (int) ($resumenCombinado->dias_con_captura ?? 0),
            'completas' => $completas,
            'incompletas' => (int) ($resumen->incompletas ?? 0),
            'completitud' => $this->porcentaje($completas, $totalHechos),
            'promedio_por_perito' => $peritosActivos > 0 ? round($total / $peritosActivos, 1) : 0,
            'promedio_diario' => $diasPeriodo > 0 ? round($total / $diasPeriodo, 1) : 0,
            'minutos_promedio_cierre' => $resumen->minutos_promedio_cierre !== null
                ? (int) round((float) $resumen->minutos_promedio_cierre)
                : null,
            'completas_24h' => (int) ($resumen->completas_24h ?? 0),
            'oportunidad_24h' => $this->porcentaje((int) ($resumen->completas_24h ?? 0), $completas),
            'aprobadas' => (int) ($resumenCombinado->aprobadas ?? 0),
            'rechazadas' => (int) ($resumenCombinado->rechazadas ?? 0),
            'con_ubicacion' => (int) ($resumenCombinado->con_ubicacion ?? 0),
            'cobertura_ubicacion' => $this->porcentaje((int) ($resumenCombinado->con_ubicacion ?? 0), $total),
            'elementos_esperados' => (int) ($resumen->elementos_esperados ?? 0),
            'elementos_capturados' => (int) ($resumen->elementos_capturados ?? 0),
            'cobertura_elementos' => $this->porcentaje(
                min((int) ($resumen->elementos_capturados ?? 0), (int) ($resumen->elementos_esperados ?? 0)),
                (int) ($resumen->elementos_esperados ?? 0)
            ),
        ];

        $porPerito = (clone $baseCombinada)
            ->selectRaw(<<<'SQL'
                user_id AS id,
                name,
                turno,
                COUNT(*) AS total,
                SUM(CASE WHEN origen = 'hecho' THEN 1 ELSE 0 END) AS hechos,
                SUM(CASE WHEN origen = 'actividad' THEN 1 ELSE 0 END) AS actividades,
                COUNT(DISTINCT DATE(created_at)) AS dias_activos,
                SUM(completa) AS completas,
                SUM(aprobada) AS aprobadas,
                SUM(rechazada) AS rechazadas,
                SUM(con_ubicacion) AS con_ubicacion,
                AVG(minutos_cierre) AS minutos_promedio_cierre,
                SUM(cantidad) AS cantidad_actividades,
                SUM(personas_alcanzadas) AS personas_alcanzadas,
                MAX(created_at) AS ultima_captura
            SQL)
            ->groupBy('user_id', 'name', 'turno')
            ->orderByDesc('total')
            ->get()
            ->map(function ($fila) {
                $fila->total = (int) $fila->total;
                $fila->hechos = (int) $fila->hechos;
                $fila->actividades = (int) $fila->actividades;
                $fila->dias_activos = (int) $fila->dias_activos;
                $fila->completas = (int) $fila->completas;
                $fila->aprobadas = (int) $fila->aprobadas;
                $fila->rechazadas = (int) $fila->rechazadas;
                $fila->con_ubicacion = (int) $fila->con_ubicacion;
                $fila->cantidad_actividades = (int) $fila->cantidad_actividades;
                $fila->personas_alcanzadas = (int) $fila->personas_alcanzadas;
                $fila->completitud = $this->porcentaje($fila->completas, $fila->hechos);
                $fila->cobertura_ubicacion = $this->porcentaje($fila->con_ubicacion, $fila->total);
                $fila->promedio_dia_activo = $fila->dias_activos > 0
                    ? round($fila->total / $fila->dias_activos, 1)
                    : 0;
                $fila->minutos_promedio_cierre = $fila->minutos_promedio_cierre !== null
                    ? (int) round((float) $fila->minutos_promedio_cierre)
                    : null;

                return $fila;
            });

        $porTurno = (clone $baseCombinada)
            ->selectRaw(<<<'SQL'
                turno,
                COUNT(*) AS total,
                SUM(CASE WHEN origen = 'hecho' THEN 1 ELSE 0 END) AS hechos,
                SUM(CASE WHEN origen = 'actividad' THEN 1 ELSE 0 END) AS actividades,
                COUNT(DISTINCT user_id) AS peritos_activos,
                COUNT(DISTINCT DATE(created_at)) AS dias_activos,
                SUM(completa) AS completas,
                SUM(con_ubicacion) AS con_ubicacion,
                AVG(minutos_cierre) AS minutos_promedio_cierre,
                SUM(personas_alcanzadas) AS personas_alcanzadas
            SQL)
            ->groupBy('turno')
            ->orderBy('turno')
            ->get()
            ->map(function ($fila) {
                $fila->total = (int) $fila->total;
                $fila->hechos = (int) $fila->hechos;
                $fila->actividades = (int) $fila->actividades;
                $fila->peritos_activos = (int) $fila->peritos_activos;
                $fila->dias_activos = (int) $fila->dias_activos;
                $fila->completas = (int) $fila->completas;
                $fila->con_ubicacion = (int) $fila->con_ubicacion;
                $fila->personas_alcanzadas = (int) $fila->personas_alcanzadas;
                $fila->completitud = $this->porcentaje($fila->completas, $fila->hechos);
                $fila->cobertura_ubicacion = $this->porcentaje($fila->con_ubicacion, $fila->total);
                $fila->promedio_por_perito = $fila->peritos_activos > 0
                    ? round($fila->total / $fila->peritos_activos, 1)
                    : 0;
                $fila->minutos_promedio_cierre = $fila->minutos_promedio_cierre !== null
                    ? (int) round((float) $fila->minutos_promedio_cierre)
                    : null;

                return $fila;
            });

        $diarioRaw = (clone $baseCombinada)
            ->selectRaw('DATE(created_at) AS fecha, turno, origen, COUNT(*) AS total')
            ->groupBy(DB::raw('DATE(created_at)'), 'turno', 'origen')
            ->orderBy('fecha')
            ->get();

        $diario = $this->serieDiaria($desde, $hasta, $diarioRaw);

        $tiposHecho = (clone $base)
            ->selectRaw("COALESCE(NULLIF(TRIM(hechos.tipo_hecho), ''), 'NO ESPECIFICADO') AS tipo, COUNT(*) AS total")
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $categoriasActividad = (clone $baseActividades)
            ->leftJoin('actividad_categorias', 'actividad_categorias.id', '=', 'actividades.actividad_categoria_id')
            ->selectRaw("COALESCE(NULLIF(TRIM(actividad_categorias.nombre), ''), 'NO ESPECIFICADA') AS categoria, COUNT(*) AS total")
            ->groupBy('categoria')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $periodoAnteriorHasta = $desde->copy()->subDay();
        $periodoAnteriorDesde = $periodoAnteriorHasta->copy()->subDays($diasPeriodo - 1);
        $anterior = $this->capturasCombinadasQuery($periodoAnteriorDesde, $periodoAnteriorHasta, $turno, $peritoId)
            ->selectRaw("COUNT(*) AS total, SUM(CASE WHEN origen = 'hecho' AND completa = 1 THEN 1 ELSE 0 END) AS completas, SUM(CASE WHEN origen = 'hecho' THEN 1 ELSE 0 END) AS hechos")
            ->first();

        $comparacion = [
            'desde' => $periodoAnteriorDesde->toDateString(),
            'hasta' => $periodoAnteriorHasta->toDateString(),
            'total' => (int) ($anterior->total ?? 0),
            'completitud' => $this->porcentaje((int) ($anterior->completas ?? 0), (int) ($anterior->hechos ?? 0)),
            'variacion_total' => $this->variacion($total, (int) ($anterior->total ?? 0)),
        ];

        return view('estadisticas_globales.rendimiento_peritos', [
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'turnoSeleccionado' => $turno,
            'peritoSeleccionado' => $peritoId,
            'peritos' => $peritos,
            'kpis' => $kpis,
            'porPerito' => $porPerito,
            'porTurno' => $porTurno,
            'diario' => $diario,
            'tiposHecho' => $tiposHecho,
            'categoriasActividad' => $categoriasActividad,
            'comparacion' => $comparacion,
        ]);
    }

    private function capturasPeritosQuery(Carbon $desde, Carbon $hasta, ?string $turno, ?int $peritoId): Builder
    {
        return DB::table('hechos')
            ->join('users', 'users.id', '=', 'hechos.created_by')
            ->leftJoin('bitacora_servicio_patrullas as bitacoras', 'bitacoras.id', '=', 'hechos.bitacora_servicio_patrulla_id')
            ->join('turnos as turnos_captura', function ($join) {
                $join->on('turnos_captura.id', '=', DB::raw('COALESCE(bitacoras.turno_id, users.turno_id)'));
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('roles.name', 'Perito');
            })
            ->where(function ($query) {
                $query->where('hechos.unidad_org_id', self::UNIDAD_SINIESTROS_ID)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('hechos.unidad_org_id')
                            ->where('users.unidad_id', self::UNIDAD_SINIESTROS_ID);
                    });
            })
            ->whereIn('turnos_captura.nombre', ['A', 'B'])
            ->whereBetween('hechos.created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when($turno, fn ($query) => $query->where('turnos_captura.nombre', $turno))
            ->when($peritoId, fn ($query) => $query->where('users.id', $peritoId));
    }

    private function actividadesPeritosQuery(Carbon $desde, Carbon $hasta, ?string $turno, ?int $peritoId): Builder
    {
        return DB::table('actividades')
            ->join('users', 'users.id', '=', 'actividades.created_by')
            ->leftJoin('bitacora_servicio_patrullas as bitacoras', 'bitacoras.id', '=', 'actividades.bitacora_servicio_patrulla_id')
            ->join('turnos as turnos_captura', function ($join) {
                $join->on('turnos_captura.id', '=', DB::raw('COALESCE(bitacoras.turno_id, users.turno_id)'));
            })
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('roles.name', 'Perito');
            })
            ->where(function ($query) {
                $query->where('actividades.unidad_org_id', self::UNIDAD_SINIESTROS_ID)
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('actividades.unidad_org_id')
                            ->where('users.unidad_id', self::UNIDAD_SINIESTROS_ID);
                    });
            })
            ->whereIn('turnos_captura.nombre', ['A', 'B'])
            ->whereBetween('actividades.created_at', [$desde->copy()->startOfDay(), $hasta->copy()->endOfDay()])
            ->when($turno, fn ($query) => $query->where('turnos_captura.nombre', $turno))
            ->when($peritoId, fn ($query) => $query->where('users.id', $peritoId));
    }

    private function capturasCombinadasQuery(Carbon $desde, Carbon $hasta, ?string $turno, ?int $peritoId): Builder
    {
        $hechos = $this->capturasPeritosQuery($desde, $hasta, $turno, $peritoId)
            ->selectRaw(<<<'SQL'
                users.id AS user_id,
                users.name AS name,
                turnos_captura.nombre AS turno,
                hechos.created_at AS created_at,
                'hecho' AS origen,
                CASE WHEN hechos.captura_completa = 1 THEN 1 ELSE 0 END AS completa,
                CASE WHEN LOWER(COALESCE(hechos.estado_revision, '')) = 'aprobado' THEN 1 ELSE 0 END AS aprobada,
                CASE WHEN LOWER(COALESCE(hechos.estado_revision, '')) = 'rechazado' THEN 1 ELSE 0 END AS rechazada,
                CASE WHEN hechos.lat IS NOT NULL AND hechos.lng IS NOT NULL THEN 1 ELSE 0 END AS con_ubicacion,
                CASE WHEN hechos.captura_completa_at IS NOT NULL AND hechos.captura_completa_at >= hechos.created_at
                     THEN TIMESTAMPDIFF(MINUTE, hechos.created_at, hechos.captura_completa_at) END AS minutos_cierre,
                0 AS cantidad,
                0 AS personas_alcanzadas,
                0 AS personas_participantes
            SQL);

        $actividades = $this->actividadesPeritosQuery($desde, $hasta, $turno, $peritoId)
            ->selectRaw(<<<'SQL'
                users.id AS user_id,
                users.name AS name,
                turnos_captura.nombre AS turno,
                actividades.created_at AS created_at,
                'actividad' AS origen,
                0 AS completa,
                CASE WHEN LOWER(COALESCE(actividades.estado_revision, '')) = 'aprobado' THEN 1 ELSE 0 END AS aprobada,
                CASE WHEN LOWER(COALESCE(actividades.estado_revision, '')) = 'rechazado' THEN 1 ELSE 0 END AS rechazada,
                CASE WHEN actividades.lat IS NOT NULL AND actividades.lng IS NOT NULL THEN 1 ELSE 0 END AS con_ubicacion,
                NULL AS minutos_cierre,
                COALESCE(actividades.cantidad, 0) AS cantidad,
                COALESCE(actividades.personas_alcanzadas, 0) AS personas_alcanzadas,
                COALESCE(actividades.personas_participantes, 0) AS personas_participantes
            SQL);

        return DB::query()->fromSub($hechos->unionAll($actividades), 'capturas');
    }

    private function peritosDisponibles()
    {
        return DB::table('users')
            ->join('turnos', 'turnos.id', '=', 'users.turno_id')
            ->where('users.unidad_id', self::UNIDAD_SINIESTROS_ID)
            ->whereIn('turnos.nombre', ['A', 'B'])
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->where('roles.name', 'Perito');
            })
            ->select('users.id', 'users.name', 'turnos.nombre as turno')
            ->orderBy('turnos.nombre')
            ->orderBy('users.name')
            ->get();
    }

    private function serieDiaria(Carbon $desde, Carbon $hasta, $filas): array
    {
        $indice = [];
        foreach ($filas as $fila) {
            $indice[$fila->fecha][$fila->turno][$fila->origen] = (int) $fila->total;
        }

        $serie = [];
        foreach (CarbonPeriod::create($desde, $hasta) as $fecha) {
            $llave = $fecha->toDateString();
            $serie[] = [
                'fecha' => $llave,
                'hechos_A' => $indice[$llave]['A']['hecho'] ?? 0,
                'actividades_A' => $indice[$llave]['A']['actividad'] ?? 0,
                'hechos_B' => $indice[$llave]['B']['hecho'] ?? 0,
                'actividades_B' => $indice[$llave]['B']['actividad'] ?? 0,
            ];
        }

        return $serie;
    }

    private function porcentaje(int $parte, int $total): float
    {
        return $total > 0 ? round(($parte / $total) * 100, 1) : 0.0;
    }

    private function variacion(int $actual, int $anterior): ?float
    {
        if ($anterior === 0) {
            return $actual === 0 ? 0.0 : null;
        }

        return round((($actual - $anterior) / $anterior) * 100, 1);
    }
}
