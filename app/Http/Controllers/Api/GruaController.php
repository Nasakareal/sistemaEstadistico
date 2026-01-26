<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grua;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GruaController extends Controller
{
    /**
     * GET /api/gruas
     */
    public function index(Request $request)
    {
        $gruas = Grua::query()
            ->withCount('servicios as total_servicios')
            ->select(['id', 'nombre', 'direccion', 'telefono', 'email', 'created_at'])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $gruas,
        ]);
    }

    /**
     * GET /api/gruas/listado?q=...
     */
    public function listado(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $gruas = Grua::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('nombre', 'like', "%{$q}%")
                        ->orWhere('direccion', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $gruas,
        ]);
    }

    /**
     * GET /api/gruas/grafica-semanal?from=YYYY-MM-DD&to=YYYY-MM-DD&gruas[]=1
     *
     * Respuesta:
     * { meta:{from,to}, data:[{id,nombre,servicios_count,fecha_ultimo_servicio}] }
     */
    public function graficaSemanal(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $gruasIds = $request->query('gruas', []);
        if (!is_array($gruasIds)) {
            $gruasIds = [$gruasIds];
        }

        // Default: últimos 7 días (incluyendo hoy)
        if (!$from || !$to) {
            $toDate   = Carbon::today()->endOfDay();
            $fromDate = Carbon::today()->subDays(6)->startOfDay();
        } else {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate   = Carbon::parse($to)->endOfDay();
        }

        $serviciosSub = DB::table('servicios')
            ->select([
                'grua_id',
                DB::raw('COUNT(*) as servicios_count'),
                DB::raw('MAX(created_at) as fecha_ultimo_servicio'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('grua_id');

        $rows = Grua::query()
            ->leftJoinSub($serviciosSub, 'ss', function ($join) {
                $join->on('gruas.id', '=', 'ss.grua_id');
            })
            ->select([
                'gruas.id',
                'gruas.nombre',
                DB::raw('COALESCE(ss.servicios_count, 0) as servicios_count'),
                DB::raw('ss.fecha_ultimo_servicio as fecha_ultimo_servicio'),
            ])
            ->when(!empty($gruasIds), function ($q) use ($gruasIds) {
                $q->whereIn('gruas.id', $gruasIds);
            })
            ->orderBy('gruas.nombre')
            ->get();

        return response()->json([
            'meta' => [
                'from' => $fromDate->toDateString(),
                'to'   => $toDate->toDateString(),
            ],
            'data' => $rows,
        ]);
    }

    /**
     * GET /api/gruas/resumen-semanal?from=YYYY-MM-DD&to=YYYY-MM-DD&gruas[]=1
     *
     * Devuelve por grúa (semana):
     * - servicios_count y fecha_ultimo_servicio (desde servicios por grua_id)
     * - tipo_vehiculo_top (desde vehiculos.tipo por match exacto vehiculos.grua == gruas.nombre)
     *
     * Respuesta:
     * { meta:{from,to}, data:[{id,nombre,servicios_count,fecha_ultimo_servicio,tipo_vehiculo_top}] }
     */
    public function resumenSemanal(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $gruasIds = $request->query('gruas', []);
        if (!is_array($gruasIds)) {
            $gruasIds = [$gruasIds];
        }

        // Default: últimos 7 días (incluyendo hoy)
        if (!$from || !$to) {
            $toDate   = Carbon::today()->endOfDay();
            $fromDate = Carbon::today()->subDays(6)->startOfDay();
        } else {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate   = Carbon::parse($to)->endOfDay();
        }

        // 1) Grúas base
        $gruas = Grua::query()
            ->select(['id', 'nombre'])
            ->when(!empty($gruasIds), function ($q) use ($gruasIds) {
                $q->whereIn('id', $gruasIds);
            })
            ->orderBy('nombre')
            ->get();

        // 2) Conteo semanal de servicios por grua_id
        $servicios = DB::table('servicios')
            ->select([
                'grua_id',
                DB::raw('COUNT(*) as servicios_count'),
                DB::raw('MAX(created_at) as fecha_ultimo_servicio'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->when(!empty($gruasIds), function ($q) use ($gruasIds) {
                $q->whereIn('grua_id', $gruasIds);
            })
            ->groupBy('grua_id')
            ->get();

        $byGruaId = [];
        foreach ($servicios as $s) {
            $byGruaId[(int) $s->grua_id] = [
                'servicios_count' => (int) $s->servicios_count,
                'fecha_ultimo_servicio' => $s->fecha_ultimo_servicio,
            ];
        }

        // 3) Tipos top por grúa (desde vehiculos, match exacto por nombre)
        // OJO: aquí uso vehiculos.created_at dentro del rango (como lo pediste: coincide exacto por nombre)
        $tipos = DB::table('vehiculos')
            ->select([
                'grua',
                'tipo',
                DB::raw('COUNT(*) as c'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->whereNotNull('grua')
            ->where('grua', '<>', '')
            ->whereNotNull('tipo')
            ->where('tipo', '<>', '')
            ->groupBy('grua', 'tipo')
            ->orderBy('grua')
            ->orderByDesc('c')
            ->get();

        // Elegir el tipo con mayor count por cada grúa (en PHP, sin window functions)
        $topTipoByNombre = [];
        foreach ($tipos as $t) {
            $nombreGrua = (string) $t->grua;
            $tipo = (string) $t->tipo;
            $c = (int) $t->c;

            if (!isset($topTipoByNombre[$nombreGrua])) {
                $topTipoByNombre[$nombreGrua] = ['tipo' => $tipo, 'c' => $c];
                continue;
            }

            // si hay empate, dejo el que ya quedó (o puedes cambiar a orden alfabético)
            if ($c > $topTipoByNombre[$nombreGrua]['c']) {
                $topTipoByNombre[$nombreGrua] = ['tipo' => $tipo, 'c' => $c];
            }
        }

        // 4) Merge final
        $data = [];
        foreach ($gruas as $g) {
            $id = (int) $g->id;
            $nombre = (string) $g->nombre;

            $serv = $byGruaId[$id] ?? ['servicios_count' => 0, 'fecha_ultimo_servicio' => null];
            $tipoTop = $topTipoByNombre[$nombre]['tipo'] ?? null;

            $data[] = [
                'id' => $id,
                'nombre' => $nombre,
                'servicios_count' => (int) $serv['servicios_count'],
                'fecha_ultimo_servicio' => $serv['fecha_ultimo_servicio'],
                'tipo_vehiculo_top' => $tipoTop,
            ];
        }

        return response()->json([
            'meta' => [
                'from' => $fromDate->toDateString(),
                'to'   => $toDate->toDateString(),
            ],
            'data' => $data,
        ]);
    }
}
