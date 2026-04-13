<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FeedController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function index(Request $request)
    {
        $v = (string)$request->query('v', '1');
        if ($v === '2') {
            return $this->indexV2($request);
        }

        $usuario = Auth::user();
        $limit = (int)$request->query('limit', 50);
        if ($limit < 1) $limit = 1;
        if ($limit > 50) $limit = 50;

        $contexto = $this->resolverContextoUnidades($request, $usuario);
        $unidadIds = $contexto['unidad_ids'];

        $hechos = $this->obtenerRowsFeed($this->queryHechos($unidadIds), $limit);
        $actividades = $this->obtenerRowsFeed($this->queryActividades($unidadIds), $limit);
        $carreteras = $this->obtenerRowsFeed($this->queryCarreteras($unidadIds), $limit);
        $vialidades = $this->obtenerRowsFeed($this->queryVialidades($unidadIds), $limit);

        $items = $hechos->concat($actividades)->concat($carreteras)->concat($vialidades)
            ->sortByDesc('created_at')
            ->values()
            ->take($limit);

        $mapped = $items->map(function ($row) {
            $fotoUrl = null;

            if (isset($row->foto_path) && !empty($row->foto_path)) {
                $path = ltrim((string)$row->foto_path, '/');

                if ($this->startsWith($path, 'http://') || $this->startsWith($path, 'https://')) {
                    $fotoUrl = $path;
                } else {
                    $fotoUrl = asset('storage/' . $path);
                }
            }

            return [
                'type' => $row->type,
                'id' => (int)$row->item_id,
                'user_id' => (int)$row->user_id,
                'user_name' => $row->user_name,
                'unidad_id' => isset($row->unidad_id) ? (int)$row->unidad_id : null,
                'resumen' => $this->limpiaResumen($row->resumen, $row->type),
                'foto_url' => $fotoUrl,
                'created_at' => $row->created_at,
                'show_url' => $this->resolverShowUrl($row),
            ];
        });

        return response()->json([
            'limit_each' => $limit,
            'count' => $mapped->count(),
            'puede_filtrar_unidades' => $contexto['puede_filtrar'],
            'unidad_ids_aplicadas' => $unidadIds,
            'unidades_filtrables' => $contexto['unidades_filtrables'],
            'data' => $mapped,
        ]);
    }

    private function indexV2(Request $request)
    {
        $usuario = Auth::user();
        $limit = (int)$request->query('limit', 20);
        if ($limit < 1) $limit = 1;
        if ($limit > 50) $limit = 50;

        $cursor = (string)$request->query('cursor', '');
        $cursorData = $this->decodeCursor($cursor);
        $contexto = $this->resolverContextoUnidades($request, $usuario);
        $unidadIds = $contexto['unidad_ids'];

        $sources = collect([
            $this->queryHechos($unidadIds, true, 1),
            $this->queryActividades($unidadIds, true, 2),
            $this->queryCarreteras($unidadIds, true, 3),
            $this->queryVialidades($unidadIds, true, 4),
        ])->filter();

        $union = null;

        foreach ($sources as $query) {
            if ($union === null) {
                $union = $query;
            } else {
                $union->unionAll($query);
            }
        }

        if ($union === null) {
            return response()->json([
                'version' => 2,
                'limit' => $limit,
                'count' => 0,
                'has_more' => false,
                'next_cursor' => null,
                'puede_filtrar_unidades' => $contexto['puede_filtrar'],
                'unidad_ids_aplicadas' => $unidadIds,
                'unidades_filtrables' => $contexto['unidades_filtrables'],
                'data' => [],
            ]);
        }

        $q = DB::query()->fromSub($union, 'f');

        if ($cursorData) {
            $createdAt = $cursorData['created_at'] ?? null;
            $typeOrder = isset($cursorData['type_order']) ? (int)$cursorData['type_order'] : null;
            $itemId = isset($cursorData['item_id']) ? (int)$cursorData['item_id'] : null;

            if ($createdAt && $typeOrder && $itemId) {
                $q->where(function ($w) use ($createdAt, $typeOrder, $itemId) {
                    $w->where('created_at', '<', $createdAt)
                        ->orWhere(function ($w2) use ($createdAt, $typeOrder, $itemId) {
                            $w2->where('created_at', '=', $createdAt)
                                ->where(function ($w3) use ($typeOrder, $itemId) {
                                    $w3->where('type_order', '>', $typeOrder)
                                        ->orWhere(function ($w4) use ($typeOrder, $itemId) {
                                            $w4->where('type_order', '=', $typeOrder)
                                                ->where('item_id', '<', $itemId);
                                        });
                                });
                        });
                });
            }
        }

        $rows = $q->orderByDesc('created_at')
            ->orderBy('type_order')
            ->orderByDesc('item_id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        if ($hasMore) {
            $rows = $rows->take($limit)->values();
        }

        $mapped = $rows->map(function ($row) {
            $fotoUrl = null;

            if (isset($row->foto_path) && !empty($row->foto_path)) {
                $path = ltrim((string)$row->foto_path, '/');

                if ($this->startsWith($path, 'http://') || $this->startsWith($path, 'https://')) {
                    $fotoUrl = $path;
                } else {
                    $fotoUrl = asset('storage/' . $path);
                }
            }

            return [
                'type' => $row->type,
                'id' => (int)$row->item_id,
                'user_id' => (int)$row->user_id,
                'user_name' => $row->user_name,
                'unidad_id' => isset($row->unidad_id) ? (int)$row->unidad_id : null,
                'resumen' => $this->limpiaResumen($row->resumen, $row->type),
                'foto_url' => $fotoUrl,
                'created_at' => $row->created_at,
                'show_url' => $this->resolverShowUrl($row),
                '_type_order' => (int)$row->type_order,
            ];
        });

        $nextCursor = null;
        if ($mapped->count() > 0) {
            $last = $mapped->last();
            $nextCursor = $this->encodeCursor([
                'created_at' => $last['created_at'],
                'type_order' => $last['_type_order'],
                'item_id' => $last['id'],
            ]);
        }

        $mapped = $mapped->map(function ($x) {
            unset($x['_type_order']);
            return $x;
        });

        return response()->json([
            'version' => 2,
            'limit' => $limit,
            'count' => $mapped->count(),
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
            'puede_filtrar_unidades' => $contexto['puede_filtrar'],
            'unidad_ids_aplicadas' => $unidadIds,
            'unidades_filtrables' => $contexto['unidades_filtrables'],
            'data' => $mapped,
        ]);
    }

    private function resolverContextoUnidades(Request $request, $usuario): array
    {
        $puedeFiltrar = $this->puedeFiltrarUnidades($usuario);

        if ($puedeFiltrar) {
            $unidadIds = $this->parseUnidadIds($request);
            $unidadesFiltrables = $this->obtenerUnidadesFiltrables();

            if (empty($unidadIds)) {
                $unidadIds = $unidadesFiltrables->pluck('id')->map(function ($id) {
                    return (int)$id;
                })->values()->all();
            }

            return [
                'puede_filtrar' => true,
                'unidad_ids' => array_values(array_unique(array_map('intval', $unidadIds))),
                'unidades_filtrables' => $unidadesFiltrables->values()->all(),
            ];
        }

        $unidadIds = $usuario && $usuario->unidad_id ? [(int)$usuario->unidad_id] : [];

        return [
            'puede_filtrar' => false,
            'unidad_ids' => $unidadIds,
            'unidades_filtrables' => [],
        ];
    }

    private function puedeFiltrarUnidades($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        if (method_exists($usuario, 'hasRole') && $usuario->hasRole('Superadmin')) {
            return true;
        }

        return (int)$usuario->unidad_id === 3;
    }

    private function parseUnidadIds(Request $request): array
    {
        $raw = $request->query('unidad_ids', null);

        if ($raw === null || $raw === '' || $raw === []) {
            $raw = $request->query('unidad_id', null);
        }

        if ($raw === null || $raw === '' || $raw === []) {
            $raw = $request->query('unidad', []);
        }

        if (is_string($raw)) {
            $raw = explode(',', $raw);
        }

        if (!is_array($raw)) {
            $raw = [];
        }

        $ids = collect($raw)
            ->map(function ($value) {
                return (int)$value;
            })
            ->filter(function ($value) {
                return $value > 0;
            })
            ->values()
            ->all();

        if (empty($ids)) {
            return [];
        }

        return DB::table('unidades')
            ->whereIn('id', $ids)
            ->where('activa', 1)
            ->pluck('id')
            ->map(function ($id) {
                return (int)$id;
            })
            ->values()
            ->all();
    }

    private function obtenerRowsFeed($source, int $limit): Collection
    {
        if ($source instanceof Collection) {
            return $source->take($limit)->values();
        }

        return $source->limit($limit)->get();
    }

    private function obtenerUnidadesFiltrables(): Collection
    {
        return DB::table('unidades')
            ->where('activa', 1)
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug'])
            ->map(function ($unidad) {
                return [
                    'id' => (int)$unidad->id,
                    'nombre' => (string)$unidad->nombre,
                    'slug' => (string)$unidad->slug,
                ];
            });
    }

    private function queryHechos(array $unidadIds, bool $forUnion = false, int $typeOrder = 1)
    {
        if (empty($unidadIds)) {
            return $forUnion ? null : collect();
        }

        $q = DB::table('hechos as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.created_by');

        $unidadSql = Schema::hasColumn('hechos', 'unidad_org_id') ? 'COALESCE(h.unidad_org_id, u.unidad_id)' : 'u.unidad_id';
        $q->whereIn(DB::raw($unidadSql), $unidadIds);

        if ($forUnion) {
            return $q->selectRaw("
                'HECHO' as type,
                ? as type_order,
                h.id as item_id,
                h.created_by as user_id,
                u.name as user_name,
                {$unidadSql} as unidad_id,
                CONCAT(TRIM(COALESCE(h.calle,'')), ', col. ', TRIM(COALESCE(h.colonia,''))) as resumen,
                COALESCE(h.foto_lugar, h.foto_situacion) as foto_path,
                h.created_at as created_at
            ", [$typeOrder]);
        }

        return $q->selectRaw("
            'HECHO' as type,
            h.id as item_id,
            h.created_by as user_id,
            u.name as user_name,
            {$unidadSql} as unidad_id,
            CONCAT(TRIM(COALESCE(h.calle,'')), ', col. ', TRIM(COALESCE(h.colonia,''))) as resumen,
            COALESCE(h.foto_lugar, h.foto_situacion) as foto_path,
            h.created_at as created_at
        ")->orderByDesc('h.created_at')->orderByDesc('h.id');
    }

    private function queryActividades(array $unidadIds, bool $forUnion = false, int $typeOrder = 2)
    {
        if (empty($unidadIds)) {
            return $forUnion ? null : collect();
        }

        $q = DB::table('actividades as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by');

        $unidadSql = Schema::hasColumn('actividades', 'unidad_org_id') ? 'COALESCE(a.unidad_org_id, u.unidad_id)' : 'u.unidad_id';
        $q->whereIn(DB::raw($unidadSql), $unidadIds);

        if ($forUnion) {
            return $q->selectRaw("
                'ACTIVIDAD' as type,
                ? as type_order,
                a.id as item_id,
                a.created_by as user_id,
                u.name as user_name,
                {$unidadSql} as unidad_id,
                a.nombre as resumen,
                a.foto_path as foto_path,
                a.created_at as created_at
            ", [$typeOrder]);
        }

        return $q->selectRaw("
            'ACTIVIDAD' as type,
            a.id as item_id,
            a.created_by as user_id,
            u.name as user_name,
            {$unidadSql} as unidad_id,
            a.nombre as resumen,
            a.foto_path as foto_path,
            a.created_at as created_at
        ")->orderByDesc('a.created_at')->orderByDesc('a.id');
    }

    private function queryCarreteras(array $unidadIds, bool $forUnion = false, int $typeOrder = 3)
    {
        if (!Schema::hasTable('operativo_dispositivos') || !in_array(4, $unidadIds, true)) {
            return $forUnion ? null : collect();
        }

        $q = DB::table('operativo_dispositivos as od')
            ->join('users as u', 'u.id', '=', 'od.created_by')
            ->where('od.unidad_org_id', 4);

        if ($forUnion) {
            return $q->selectRaw("
                'CARRETERAS' as type,
                ? as type_order,
                od.id as item_id,
                od.created_by as user_id,
                u.name as user_name,
                od.unidad_org_id as unidad_id,
                CONCAT(
                    TRIM(COALESCE(od.asunto,'')),
                    CASE
                        WHEN COALESCE(TRIM(od.carretera),'') <> '' THEN CONCAT(' - ', TRIM(od.carretera))
                        ELSE ''
                    END,
                    CASE
                        WHEN COALESCE(TRIM(od.tramo),'') <> '' THEN CONCAT(' / ', TRIM(od.tramo))
                        ELSE ''
                    END
                ) as resumen,
                NULL as foto_path,
                od.created_at as created_at
            ", [$typeOrder]);
        }

        return $q->selectRaw("
            'CARRETERAS' as type,
            od.id as item_id,
            od.created_by as user_id,
            u.name as user_name,
            od.unidad_org_id as unidad_id,
            CONCAT(
                TRIM(COALESCE(od.asunto,'')),
                CASE
                    WHEN COALESCE(TRIM(od.carretera),'') <> '' THEN CONCAT(' - ', TRIM(od.carretera))
                    ELSE ''
                END,
                CASE
                    WHEN COALESCE(TRIM(od.tramo),'') <> '' THEN CONCAT(' / ', TRIM(od.tramo))
                    ELSE ''
                END
            ) as resumen,
            NULL as foto_path,
            od.created_at as created_at
        ")->orderByDesc('od.created_at')->orderByDesc('od.id');
    }

    private function queryVialidades(array $unidadIds, bool $forUnion = false, int $typeOrder = 4)
    {
        if (!Schema::hasTable('vialidad_dispositivos') || !Schema::hasTable('vialidad_dispositivo_detalles') || !in_array(5, $unidadIds, true)) {
            return $forUnion ? null : collect();
        }

        $q = DB::table('vialidad_dispositivos as vd')
            ->join('users as u', 'u.id', '=', 'vd.created_by');

        if (Schema::hasColumn('vialidad_dispositivos', 'unidad_id')) {
            $q->where('vd.unidad_id', 5);
        } elseif (Schema::hasColumn('users', 'unidad_id')) {
            $q->where('u.unidad_id', 5);
        }

        $resumenSql = "COALESCE((
            SELECT CONCAT(
                TRIM(COALESCE(vdd.titulo,'')),
                CASE
                    WHEN COALESCE(TRIM(vdd.contenido),'') <> '' THEN CONCAT(': ', TRIM(vdd.contenido))
                    ELSE ''
                END
            )
            FROM vialidad_dispositivo_detalles vdd
            WHERE vdd.vialidad_dispositivo_id = vd.id
            ORDER BY vdd.orden ASC, vdd.id ASC
            LIMIT 1
        ), 'Registro de vialidades')";

        if ($forUnion) {
            return $q->selectRaw("
                'VIALIDADES' as type,
                ? as type_order,
                vd.id as item_id,
                vd.created_by as user_id,
                u.name as user_name,
                " . (Schema::hasColumn('vialidad_dispositivos', 'unidad_id') ? 'vd.unidad_id' : 'u.unidad_id') . " as unidad_id,
                {$resumenSql} as resumen,
                NULL as foto_path,
                vd.created_at as created_at
            ", [$typeOrder]);
        }

        return $q->selectRaw("
            'VIALIDADES' as type,
            vd.id as item_id,
            vd.created_by as user_id,
            u.name as user_name,
            " . (Schema::hasColumn('vialidad_dispositivos', 'unidad_id') ? 'vd.unidad_id' : 'u.unidad_id') . " as unidad_id,
            {$resumenSql} as resumen,
            NULL as foto_path,
            vd.created_at as created_at
        ")->orderByDesc('vd.created_at')->orderByDesc('vd.id');
    }

    private function resolverShowUrl($row): ?string
    {
        if ($row->type === 'HECHO') {
            return route('hechos.show', $row->item_id);
        }

        if ($row->type === 'ACTIVIDAD') {
            return route('actividades.show', $row->item_id);
        }

        return null;
    }

    private function limpiaResumen($resumen, string $type): string
    {
        $txt = trim((string)$resumen);

        if ($type === 'HECHO') {
            $txt = preg_replace('/\s+/', ' ', $txt);
            $txt = trim($txt, " ,");
            if ($txt === 'col.' || $txt === 'col') $txt = '';
        }

        if ($txt !== '') {
            return $txt;
        }

        switch ($type) {
            case 'HECHO':
                return 'Hecho registrado';
            case 'ACTIVIDAD':
                return 'Actividad registrada';
            case 'CARRETERAS':
                return 'Registro de carreteras';
            case 'VIALIDADES':
                return 'Registro de vialidades';
            default:
                return 'Registro';
        }
    }

    private function startsWith(string $value, string $prefix): bool
    {
        return substr($value, 0, strlen($prefix)) === $prefix;
    }

    private function encodeCursor(array $data): string
    {
        return rtrim(strtr(base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)), '+/', '-_'), '=');
    }

    private function decodeCursor(string $cursor): ?array
    {
        $cursor = trim($cursor);
        if ($cursor === '') return null;

        $b64 = strtr($cursor, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) $b64 .= str_repeat('=', 4 - $pad);

        $json = base64_decode($b64, true);
        if ($json === false) return null;

        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }
}
