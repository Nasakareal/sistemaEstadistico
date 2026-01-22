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
     * Listado básico (lo que ya tenías, pero con campos útiles)
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
     * Listado "como web", con búsqueda simple opcional:
     *   ?q=algo
     */
    public function listado(Request $request)
    {
        $q = trim((string)$request->query('q'));

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
     * Replica la idea del web:
     * - filtra servicios por rango de fechas (created_at)
     * - filtra por grúas seleccionadas (por id)
     * - regresa por grúa: total de servicios y fecha del último servicio
     *
     * Params:
     *  - from=YYYY-MM-DD (opcional)
     *  - to=YYYY-MM-DD   (opcional)
     *  - gruas[]=1&gruas[]=2 (opcional, IDs)
     *
     * Respuesta:
     *  [
     *    { id, nombre, servicios_count, fecha_ultimo_servicio }
     *  ]
     */
    public function graficaSemanal(Request $request)
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        $gruasIds = $request->query('gruas', []);
        if (!is_array($gruasIds)) {
            $gruasIds = [$gruasIds];
        }

        // Si no mandan fechas, por defecto: últimos 7 días (incluyendo hoy)
        if (!$from || !$to) {
            $toDate = Carbon::today();
            $fromDate = Carbon::today()->subDays(6);
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

        $query = Grua::query()
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
            ->orderBy('gruas.nombre');

        $rows = $query->get();

        return response()->json([
            'meta' => [
                'from' => $fromDate->toDateString(),
                'to'   => $toDate->toDateString(),
            ],
            'data' => $rows,
        ]);
    }
}
