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
     * Listado básico
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
     * GET /api/gruas/listado
     * Listado con búsqueda
     *   ?q=algo
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
     * GET /api/gruas/grafica-semanal
     *
     * Params:
     *  - from=YYYY-MM-DD (opcional)
     *  - to=YYYY-MM-DD   (opcional)
     *  - gruas[]=1&gruas[]=2 (opcional, IDs)
     *
     * Respuesta:
     *  { meta:{from,to}, data:[{id,nombre,servicios_count,fecha_ultimo_servicio}] }
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
     * GET /api/gruas/resumen-semanal
     *
     * Devuelve por grúa (semana):
     * - servicios_count (tabla servicios)
     * - fecha_ultimo_servicio (tabla servicios)
     * - tipo_vehiculo_top (tabla vehiculos.tipo) por match exacto:
     *     vehiculos.grua == gruas.nombre
     *
     * Params:
     *  - from=YYYY-MM-DD (opcional)
     *  - to=YYYY-MM-DD   (opcional)
     *  - gruas[]=1&gruas[]=2 (opcional, IDs)
     *
     * Respuesta:
     *  { meta:{from,to}, data:[{id,nombre,servicios_count,fecha_ultimo_servicio,tipo_vehiculo_top}] }
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

        // Servicios por grúa (semana) usando relación real (grua_id)
        $serviciosSub = DB::table('servicios')
            ->select([
                'grua_id',
                DB::raw('COUNT(*) as servicios_count'),
                DB::raw('MAX(created_at) as fecha_ultimo_servicio'),
            ])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->groupBy('grua_id');

        // Top tipo por grúa (semana) usando match exacto por NOMBRE (vehiculos.grua)
        // Nota: aquí usamos el created_at del vehículo como referencia de "semana".
        // Si quieres por servicios, habría que tener FK servicios->vehiculo y tomar el tipo desde ahí.
        $tiposTopSub = DB::table('vehiculos as v')
            ->select([
                'v.grua as grua_nombre',
                'v.tipo as tipo_vehiculo_top',
                DB::raw('COUNT(*) as c'),
            ])
            ->whereNotNull('v.grua')
            ->where('v.grua', '<>', '')
            ->whereNotNull('v.tipo')
            ->where('v.tipo', '<>', '')
            ->whereBetween('v.created_at', [$fromDate, $toDate])
            ->groupBy('v.grua', 'v.tipo');

        // Elegir el tipo con mayor COUNT por grúa (y desempate alfabético)
        $tiposWinnerSub = DB::query()
            ->fromSub($tiposTopSub, 'tt')
            ->select([
                'tt.grua_nombre',
                'tt.tipo_vehiculo_top',
                'tt.c',
                DB::raw(
                    'ROW_NUMBER() OVER (PARTITION BY tt.grua_nombre ORDER BY tt.c DESC, tt.tipo_vehiculo_top ASC) as rn'
                ),
            );

        $rows = Grua::query()
            ->leftJoinSub($serviciosSub, 'ss', function ($join) {
                $join->on('gruas.id', '=', 'ss.grua_id');
            })
            ->leftJoinSub($tiposWinnerSub, 'tw', function ($join) {
                $join->on('gruas.nombre', '=', 'tw.grua_nombre')
                     ->where('tw.rn', '=', 1);
            })
            ->select([
                'gruas.id',
                'gruas.nombre',
                DB::raw('COALESCE(ss.servicios_count, 0) as servicios_count'),
                DB::raw('ss.fecha_ultimo_servicio as fecha_ultimo_servicio'),
                DB::raw('tw.tipo_vehiculo_top as tipo_vehiculo_top'),
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
}
