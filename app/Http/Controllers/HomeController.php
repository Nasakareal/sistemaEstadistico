<?php

namespace App\Http\Controllers;

use App\Services\Fotos\HechoFotoStorage;
use App\Support\HechoAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $usuario = Auth::user();

        if ($usuario && $usuario->can('ver portal ciudadano puntos licencias') && !$usuario->can('ver puntos licencias')) {
            return redirect()->route('ciudadano.licencias_puntos.index');
        }

        $limit = (int) $request->query('limit', 12);
        if ($limit < 1) $limit = 1;
        if ($limit > 30) $limit = 30;

        $unidadFiltro = $this->resolverUnidadFiltro($request, $usuario);

        $cursorCreatedAt = $request->query('cursor_created_at');
        $cursorId = $request->query('cursor_id');

        $data = $this->getFeed($limit, $cursorCreatedAt, $cursorId, $unidadFiltro);

        return view('home', [
            'feed_items' => $data['items'],
            'feed_next_cursor' => $data['next_cursor'],
            'feed_limit' => $limit,
            'feed_unidad_id' => $unidadFiltro,
            'feed_puede_filtrar_unidades' => $this->puedeFiltrarUnidades($usuario),
            'feed_unidades' => $this->obtenerUnidadesFiltro($usuario),
        ]);
    }

    public function feed(Request $request)
    {
        $usuario = Auth::user();

        if ($usuario && $usuario->can('ver portal ciudadano puntos licencias') && !$usuario->can('ver puntos licencias')) {
            abort(403);
        }

        $limit = (int) $request->query('limit', 12);
        if ($limit < 1) $limit = 1;
        if ($limit > 30) $limit = 30;

        $unidadFiltro = $this->resolverUnidadFiltro($request, $usuario);

        $cursorCreatedAt = $request->query('cursor_created_at');
        $cursorId = $request->query('cursor_id');

        $data = $this->getFeed($limit, $cursorCreatedAt, $cursorId, $unidadFiltro);

        return response()->json([
            'limit' => $limit,
            'count' => count($data['items']),
            'next_cursor' => $data['next_cursor'],
            'unidad_id' => $unidadFiltro,
            'data' => $data['items'],
        ]);
    }

    private function getFeed(int $limit, $cursorCreatedAt = null, $cursorId = null, $unidadFiltro = null): array
    {
        $usuario = Auth::user();
        $unidadFiltro = $this->resolverUnidadFiltroDirecto($unidadFiltro, $usuario);
        $hechoDelegacionSql = 'COALESCE(h.delegacion_id, u.delegacion_id)';
        $actividadDelegacionSql = 'COALESCE(a.delegacion_id, u.delegacion_id)';

        $hechosQ = DB::table('hechos as h')
            ->join('users as u', 'u.id', '=', 'h.created_by')
            ->leftJoin('delegaciones as dh', 'dh.id', '=', 'h.delegacion_id')
            ->leftJoin('delegaciones as duh', 'duh.id', '=', 'u.delegacion_id')
            ->selectRaw("
                'HECHO' as type,
                h.id as item_id,
                h.created_by as user_id,
                u.name as user_name,
                {$hechoDelegacionSql} as delegacion_id,
                COALESCE(
                    CASE
                        WHEN dh.id IS NOT NULL AND COALESCE(TRIM(dh.clave), '') <> '' THEN CONCAT(dh.nombre, ' (', dh.clave, ')')
                        WHEN dh.id IS NOT NULL THEN dh.nombre
                        ELSE NULL
                    END,
                    CASE
                        WHEN duh.id IS NOT NULL AND COALESCE(TRIM(duh.clave), '') <> '' THEN CONCAT(duh.nombre, ' (', duh.clave, ')')
                        WHEN duh.id IS NOT NULL THEN duh.nombre
                        ELSE NULL
                    END
                ) as delegacion_nombre,
                CONCAT(TRIM(COALESCE(h.calle,'')), ', col. ', TRIM(COALESCE(h.colonia,''))) as resumen,
                COALESCE(h.foto_lugar, h.foto_situacion) as foto_path,
                h.created_at as created_at
            ");

        $actividadesQ = DB::table('actividades as a')
            ->join('users as u', 'u.id', '=', 'a.created_by')
            ->leftJoin('delegaciones as da', 'da.id', '=', 'a.delegacion_id')
            ->leftJoin('delegaciones as dua', 'dua.id', '=', 'u.delegacion_id')
            ->selectRaw("
                'ACTIVIDAD' as type,
                a.id as item_id,
                a.created_by as user_id,
                u.name as user_name,
                {$actividadDelegacionSql} as delegacion_id,
                COALESCE(
                    CASE
                        WHEN da.id IS NOT NULL AND COALESCE(TRIM(da.clave), '') <> '' THEN CONCAT(da.nombre, ' (', da.clave, ')')
                        WHEN da.id IS NOT NULL THEN da.nombre
                        ELSE NULL
                    END,
                    CASE
                        WHEN dua.id IS NOT NULL AND COALESCE(TRIM(dua.clave), '') <> '' THEN CONCAT(dua.nombre, ' (', dua.clave, ')')
                        WHEN dua.id IS NOT NULL THEN dua.nombre
                        ELSE NULL
                    END
                ) as delegacion_nombre,
                a.nombre as resumen,
                COALESCE(a.foto_path, a.foto_thumbnail_path) as foto_path,
                a.created_at as created_at
            ");

        if ($unidadFiltro !== 'TODAS') {
            $unidadId = (int) $unidadFiltro;

            if ($unidadId > 0) {
                $hechosQ->where('h.unidad_org_id', $unidadId);

                if (Schema::hasColumn('actividades', 'unidad_org_id')) {
                    $actividadesQ->where('a.unidad_org_id', $unidadId);
                } else {
                    $actividadesQ->where('u.unidad_id', $unidadId);
                }
            } else {
                $hechosQ->whereRaw('1=0');
                $actividadesQ->whereRaw('1=0');
            }
        }

        $this->applyDelegacionesScope($hechosQ, $usuario, $hechoDelegacionSql);
        $this->applyDelegacionesScope($actividadesQ, $usuario, $actividadDelegacionSql);
        $this->applyActiveDelegacionFeedFilter($hechosQ, 'dh', 'duh');
        $this->applyActiveDelegacionFeedFilter($actividadesQ, 'da', 'dua');

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
                } elseif ((string) $row->type === 'HECHO') {
                    $foto_url = app(HechoFotoStorage::class)->url($path);
                } else {
                    $foto_url = asset('storage/' . $path);
                }
            }

            return [
                'type' => $row->type,
                'id' => (int) $row->item_id,
                'user_id' => (int) $row->user_id,
                'user_name' => $row->user_name,
                'delegacion_id' => isset($row->delegacion_id) ? (int) $row->delegacion_id : null,
                'delegacion_nombre' => $this->normalizaTextoOpcional($row->delegacion_nombre ?? null),
                'resumen' => $this->limpiaResumen($row->resumen, $row->type),
                'foto_url' => $foto_url,
                'created_at' => (string) $row->created_at,
                'show_url' => $row->type === 'HECHO'
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

    private function normalizaTextoOpcional($valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function limpiaResumen($resumen, string $type): string
    {
        $txt = trim((string) $resumen);

        if ($type === 'HECHO') {
            $txt = preg_replace('/\s+/', ' ', $txt);
            $txt = trim($txt, " ,");

            if ($txt === 'col.' || $txt === 'col') {
                $txt = '';
            }
        }

        return $txt !== '' ? $txt : ($type === 'HECHO' ? 'Hecho registrado' : 'Actividad registrada');
    }

    private function resolverUnidadFiltro(Request $request, $usuario)
    {
        if ($this->puedeFiltrarUnidades($usuario)) {
            $unidadId = $request->query('unidad_id', 'TODAS');

            if ($unidadId === null || $unidadId === '' || $unidadId === 'TODAS') {
                return 'TODAS';
            }

            return (int) $unidadId;
        }

        return (int) ($usuario->unidad_id ?? 0);
    }

    private function resolverUnidadFiltroDirecto($unidadFiltro, $usuario)
    {
        if ($this->puedeFiltrarUnidades($usuario)) {
            if ($unidadFiltro === null || $unidadFiltro === '' || $unidadFiltro === 'TODAS') {
                return 'TODAS';
            }

            return (int) $unidadFiltro;
        }

        return (int) ($usuario->unidad_id ?? 0);
    }

    private function obtenerUnidadesFiltro($usuario)
    {
        if (!$this->puedeFiltrarUnidades($usuario)) {
            return collect();
        }

        if (Schema::hasTable('unidads')) {
            return DB::table('unidads')
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();
        }

        if (Schema::hasTable('unidades')) {
            return DB::table('unidades')
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();
        }

        return collect([
            (object) ['id' => 1, 'nombre' => 'Siniestros'],
            (object) ['id' => 2, 'nombre' => 'Delegaciones'],
            (object) ['id' => 3, 'nombre' => 'Seguridad Vial'],
            (object) ['id' => 4, 'nombre' => 'Carreteras'],
            (object) ['id' => 5, 'nombre' => 'Vialidades Urbanas'],
        ]);
    }

    private function puedeFiltrarUnidades($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        if (method_exists($usuario, 'hasRole') && $usuario->hasRole('Superadmin')) {
            return true;
        }

        return (int) ($usuario->unidad_id ?? 0) === 3;
    }

    private function applyDelegacionesScope($query, $usuario, string $delegacionSql): void
    {
        if (!$usuario || (int) ($usuario->unidad_id ?? 0) !== 2) {
            return;
        }

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Subdirector')
        ) {
            return;
        }

        $ids = HechoAccess::delegacionIdsVisiblesParaUsuario($usuario);

        if (empty($ids)) {
            $query->whereRaw('1=0');
            return;
        }

        $query->whereIn(DB::raw($delegacionSql), $ids);
    }

    private function applyActiveDelegacionFeedFilter($query, string $principalAlias, string $fallbackAlias): void
    {
        $query->where(function ($where) use ($principalAlias, $fallbackAlias) {
            $where->where(function ($directa) use ($principalAlias) {
                $directa->whereNotNull("{$principalAlias}.id")
                    ->where("{$principalAlias}.activa", 1);
            })->orWhere(function ($fallback) use ($principalAlias, $fallbackAlias) {
                $fallback->whereNull("{$principalAlias}.id")
                    ->where(function ($fallbackActiva) use ($fallbackAlias) {
                        $fallbackActiva->whereNull("{$fallbackAlias}.id")
                            ->orWhere("{$fallbackAlias}.activa", 1);
                    });
            });
        });
    }
}
