<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\Delegacion;
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

        $momento = now('America/Mexico_City');

        $turnoActivoNombre = '—';
        $turnoActivo = $this->turnoService->turnoActivoEn($momento);

        if ($turnoActivo && !empty($turnoActivo->nombre)) {
            $turnoActivoNombre = (string) $turnoActivo->nombre;
        }

        $personales = Personal::with(['turno','incidencias.tipo'])
            ->where('estatus', 'ACTIVO')
            ->get();

        $totalActivos = $personales->count();

        $enServicio = 0;
        foreach ($personales as $p) {
            if ($this->estadoFuerzaService->estado($p, $momento) === 'EN_SERVICIO') {
                $enServicio++;
            }
        }

        $operativosEnServicio = $this->operativosService->contarEnServicio($momento, $this->estadoFuerzaService);
        $administrativosEnServicio = $this->contarAdministrativosEnServicio($momento);

        return view('home', [
            'feed_items' => $data['items'],
            'feed_next_cursor' => $data['next_cursor'],
            'feed_limit' => $limit,

            'turno_activo' => $turnoActivoNombre,
            'personal_en_servicio' => $enServicio,
            'total_activos' => $totalActivos,

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
        $usuario = Auth::user();

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

        $this->applyFeedVisibilityScope($hechosQ, $actividadesQ, $usuario);

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

    private function applyFeedVisibilityScope($hechosQ, $actividadesQ, $usuario): void
    {
        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Coordinador')
        ) {
            return;
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $UNIDAD_CARRETERAS_ID = 4;

        if ($UNIDAD_CARRETERAS_ID > 0 && $unidadId === $UNIDAD_CARRETERAS_ID) {
            $hechosQ->where('h.unidad_org_id', $UNIDAD_CARRETERAS_ID);

            if (Schema::hasColumn('actividades', 'unidad_org_id')) {
                $actividadesQ->where('a.unidad_org_id', $UNIDAD_CARRETERAS_ID);
            } else {
                $actividadesQ->where('u.unidad_id', $UNIDAD_CARRETERAS_ID);
            }
            return;
        }

        if ($unidadId === 2) {
            $delegacionId = (int) ($usuario->delegacion_id ?? 0);

            if ($delegacionId <= 0) {
                $hechosQ->whereRaw('1=0');
                $actividadesQ->whereRaw('1=0');
                return;
            }

            $esRegional = Delegacion::query()
                ->where('id', $delegacionId)
                ->whereNull('delegacion_padre_id')
                ->exists();

            if ($usuario->hasRole('Subdirector')) {
                if ($esRegional) {
                    $ids = Delegacion::query()
                        ->where('id', $delegacionId)
                        ->orWhere('delegacion_padre_id', $delegacionId)
                        ->pluck('id')
                        ->toArray();

                    $hechosQ->whereIn('h.delegacion_id', $ids);

                    if (Schema::hasColumn('actividades', 'delegacion_id')) {
                        $actividadesQ->whereIn('a.delegacion_id', $ids);
                    } else {
                        $actividadesQ->whereIn('u.delegacion_id', $ids);
                    }
                } else {
                    $hechosQ->where('h.delegacion_id', $delegacionId);

                    if (Schema::hasColumn('actividades', 'delegacion_id')) {
                        $actividadesQ->where('a.delegacion_id', $delegacionId);
                    } else {
                        $actividadesQ->where('u.delegacion_id', $delegacionId);
                    }
                }
            } else {
                $hechosQ->where('h.delegacion_id', $delegacionId);

                if (Schema::hasColumn('actividades', 'delegacion_id')) {
                    $actividadesQ->where('a.delegacion_id', $delegacionId);
                } else {
                    $actividadesQ->where('u.delegacion_id', $delegacionId);
                }
            }

            return;
        }

        if ($unidadId > 0) {
            $hechosQ->where('h.unidad_org_id', $unidadId);

            if (Schema::hasColumn('actividades', 'unidad_org_id')) {
                $actividadesQ->where('a.unidad_org_id', $unidadId);
            } else {
                $actividadesQ->where('u.unidad_id', $unidadId);
            }

            return;
        }

        $hechosQ->whereRaw('1=0');
        $actividadesQ->whereRaw('1=0');
    }

    private function contarAdministrativosEnServicio(Carbon $momento): int
    {
        $momento = $momento->copy()->timezone('America/Mexico_City');

        $q = Personal::query()
            ->with(['incidencias.tipo', 'turno'])
            ->where('estatus', 'ACTIVO');

        if (Schema::hasColumn('personals', 'es_operativo')) {
            $q->where('es_operativo', 0);
        } elseif (Schema::hasColumn('personals', 'tipo')) {
            $q->whereRaw('UPPER(TRIM(tipo)) <> ?', ['OPERATIVO']);
        } elseif (Schema::hasColumn('personals', 'categoria')) {
            $q->whereRaw('UPPER(TRIM(categoria)) <> ?', ['OPERATIVO']);
        }

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
