<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Services\EstadoFuerzaService;
use App\Services\OperativosService;
use App\Services\TurnoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    protected EstadoFuerzaService $estadoFuerzaService;
    protected TurnoService $turnoService;
    protected OperativosService $operativosService;

    public function __construct(
        EstadoFuerzaService $estadoFuerzaService,
        TurnoService $turnoService,
        OperativosService $operativosService
    ) {
        $this->middleware('auth');
        $this->estadoFuerzaService = $estadoFuerzaService;
        $this->turnoService = $turnoService;
        $this->operativosService = $operativosService;
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

        /**
         * TURNO ACTIVO:
         * No debe venir del usuario logueado (porque puede ser SUBDIRECTOR),
         * debe venir del 24x24 activo (Turno A / Turno B).
         */
        $turnoActivoNombre = '—';
        $turnoActivo = $this->turnoService->turnoActivoEn($momento);

        if ($turnoActivo && !empty($turnoActivo->nombre)) {
            $turnoActivoNombre = (string) $turnoActivo->nombre;
        }

        // Totales: todos los activos
        $personales = Personal::with([
                'turno',
                'incidencias.tipo',
            ])
            ->where('estatus', 'ACTIVO')
            ->get();

        $totalActivos = $personales->count();

        // Total en servicio (como ya lo traías)
        $enServicio = 0;
        foreach ($personales as $p) {
            if ($this->estadoFuerzaService->estado($p, $momento) === 'EN_SERVICIO') {
                $enServicio++;
            }
        }

        /**
         * Desglose Operativos / Administrativos en servicio
         */
        $operativosEnServicio = $this->operativosService->contarEnServicio($momento, $this->estadoFuerzaService);

        // Administrativos: activos, NO operativos, y EN_SERVICIO
        $administrativosEnServicio = $this->contarAdministrativosEnServicio($momento);

        return view('home', [
            'feed_items' => $data['items'],
            'feed_next_cursor' => $data['next_cursor'],
            'feed_limit' => $limit,

            // KPIs
            'turno_activo' => $turnoActivoNombre,
            'personal_en_servicio' => $enServicio,
            'total_activos' => $totalActivos,

            // Desglose
            'personal_operativos_en_servicio' => $operativosEnServicio,
            'personal_administrativos_en_servicio' => $administrativosEnServicio,
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

    private function contarAdministrativosEnServicio(Carbon $momento): int
    {
        $momento = $momento->copy()->timezone('America/Mexico_City');

        $q = Personal::query()
            ->with(['incidencias.tipo', 'turno'])
            ->where('estatus', 'ACTIVO');

        // Administrativos = NO operativos (misma lógica, pero al revés)
        if (Schema::hasColumn('personals', 'es_operativo')) {
            $q->where('es_operativo', 0);
        } elseif (Schema::hasColumn('personals', 'tipo')) {
            $q->whereRaw('UPPER(TRIM(tipo)) <> ?', ['OPERATIVO']);
        } elseif (Schema::hasColumn('personals', 'categoria')) {
            $q->whereRaw('UPPER(TRIM(categoria)) <> ?', ['OPERATIVO']);
        }
        // Si no existe ninguna columna para clasificar, no filtramos (se iría "todo" como administrativos).
        // Si eso te afecta, dime cuál columna real usan y lo amarramos exacto.

        $personales = $q->get();

        $count = 0;
        foreach ($personales as $p) {
            if ($this->estadoFuerzaService->estado($p, $momento) === 'EN_SERVICIO') {
                $count++;
            }
        }

        return $count;
    }
}
