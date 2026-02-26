<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Services\EstadoFuerzaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    protected EstadoFuerzaService $estadoFuerzaService;

    public function __construct(EstadoFuerzaService $estadoFuerzaService)
    {
        $this->middleware('auth');
        $this->estadoFuerzaService = $estadoFuerzaService;
    }

    public function index(Request $request)
    {
        $limit = (int) $request->query('limit', 12);
        if ($limit < 1) $limit = 1;
        if ($limit > 30) $limit = 30;

        $cursorCreatedAt = $request->query('cursor_created_at');
        $cursorId = $request->query('cursor_id');

        $data = $this->getFeed($limit, $cursorCreatedAt, $cursorId);

        // =========================
        // KPIs SUPERIOR (tarjetas)
        // =========================
        $momento = now('America/Mexico_City');

        // Turno activo (lo sacamos del personal del usuario logueado si existe)
        $turnoActivoNombre = '—';
        $user = Auth::user();

        if ($user) {
            $personalUser = Personal::with('turno')
                ->where('user_id', $user->id)
                ->first();

            if ($personalUser && $personalUser->turno && !empty($personalUser->turno->nombre)) {
                $turnoActivoNombre = (string) $personalUser->turno->nombre;
            }
        }

        // Totales: todos los activos
        $personales = Personal::with([
                'turno',
                'incidencias.tipo',
            ])
            ->where('estatus', 'ACTIVO')
            ->get();

        $totalActivos = $personales->count();

        $enServicio = 0;
        foreach ($personales as $p) {
            if ($this->estadoFuerzaService->estado($p, $momento) === 'EN_SERVICIO') {
                $enServicio++;
            }
        }

        return view('home', [
            'feed_items' => $data['items'],
            'feed_next_cursor' => $data['next_cursor'],
            'feed_limit' => $limit,

            // KPIs
            'turno_activo' => $turnoActivoNombre,
            'personal_en_servicio' => $enServicio,
            'total_activos' => $totalActivos,
        ]);
    }

    public function feed(Request $request)
    {
        $limit = (int) $request->query('limit', 12);
        if ($limit < 1) $limit = 1;
        if ($limit > 30) $limit = 30;

        $cursorCreatedAt = $request->query('cursor_created_at');
        $cursorId = $request->query('cursor_id');

        $data = $this->getFeed($limit, $cursorCreatedAt, $cursorId);

        return response()->json([
            'limit' => $limit,
            'count' => count($data['items']),
            'next_cursor' => $data['next_cursor'],
            'data' => $data['items'],
        ]);
    }

    private function getFeed(int $limit, $cursorCreatedAt = null, $cursorId = null): array
    {
        $hechosQ = DB::table('hechos as h')
            ->join('users as u', 'u.id', '=', 'h.created_by')
            ->selectRaw("
                'HECHO' as type,
                h.id as item_id,
                h.created_by as user_id,
                u.name as user_name,
                CONCAT(TRIM(COALESCE(h.calle,'')), ', col. ', TRIM(COALESCE(h.colonia,''))) as resumen,
                COALESCE(h.foto_lugar, h.foto_situacion) as foto_path,
                h.created_at as created_at
            ");

        $actividadesQ = DB::table('actividades as a')
            ->join('users as u', 'u.id', '=', 'a.created_by')
            ->selectRaw("
                'ACTIVIDAD' as type,
                a.id as item_id,
                a.created_by as user_id,
                u.name as user_name,
                a.nombre as resumen,
                a.foto_path as foto_path,
                a.created_at as created_at
            ");

        if ($cursorCreatedAt && $cursorId) {
            $hechosQ->where(function ($q) use ($cursorCreatedAt, $cursorId) {
                $q->where('h.created_at', '<', $cursorCreatedAt)
                  ->orWhere(function ($q2) use ($cursorCreatedAt, $cursorId) {
                      $q2->where('h.created_at', '=', $cursorCreatedAt)
                         ->where('h.id', '<', (int) $cursorId);
                  });
            });

            $actividadesQ->where(function ($q) use ($cursorCreatedAt, $cursorId) {
                $q->where('a.created_at', '<', $cursorCreatedAt)
                  ->orWhere(function ($q2) use ($cursorCreatedAt, $cursorId) {
                      $q2->where('a.created_at', '=', $cursorCreatedAt)
                         ->where('a.id', '<', (int) $cursorId);
                  });
            });
        }

        $hechos = $hechosQ->orderByDesc('h.created_at')->orderByDesc('h.id')->limit($limit)->get();
        $actividades = $actividadesQ->orderByDesc('a.created_at')->orderByDesc('a.id')->limit($limit)->get();

        $items = $hechos->concat($actividades)
            ->sortByDesc('created_at')
            ->values()
            ->take($limit)
            ->values();

        $mapped = $items->map(function ($row) {
            $foto_url = null;

            if (!empty($row->foto_path)) {
                $path = ltrim((string) $row->foto_path, '/');

                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                    $foto_url = $path;
                } else {
                    $foto_url = asset('storage/' . $path);
                }
            }

            return [
                'type'       => $row->type,
                'id'         => (int) $row->item_id,
                'user_id'    => (int) $row->user_id,
                'user_name'  => $row->user_name,
                'resumen'    => $this->limpiaResumen($row->resumen, $row->type),
                'foto_url'   => $foto_url,
                'created_at' => (string) $row->created_at,
                'show_url'   => $row->type === 'HECHO'
                    ? route('hechos.show', $row->item_id)
                    : route('actividades.show', $row->item_id),
            ];
        })->values()->all();

        $next_cursor = null;
        if (count($mapped) > 0) {
            $last = $items->last();
            $next_cursor = [
                'cursor_created_at' => (string) $last->created_at,
                'cursor_id' => (int) $last->item_id,
            ];
        }

        return [
            'items' => $mapped,
            'next_cursor' => $next_cursor,
        ];
    }

    private function limpiaResumen($resumen, string $type): string
    {
        $txt = trim((string) $resumen);

        if ($type === 'HECHO') {
            $txt = preg_replace('/\s+/', ' ', $txt);
            $txt = trim($txt, " ,");
            if ($txt === 'col.' || $txt === 'col') $txt = '';
        }

        return $txt !== '' ? $txt : ($type === 'HECHO' ? 'Hecho registrado' : 'Actividad registrada');
    }
}
