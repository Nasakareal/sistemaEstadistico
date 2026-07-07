<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Fotos\HechoFotoStorage;
use Illuminate\Http\Request;
use App\Models\Delegacion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\HechoAccess;

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
        $userIdFilter = $this->parseUserIdFilter($request, $usuario);
        $dateRange = $this->parseDateRange($request);
        $limit = (int)$request->query('limit', 50);
        if ($limit < 1) $limit = 1;
        $maxLimit = $userIdFilter ? 200 : 50;
        if ($limit > $maxLimit) $limit = $maxLimit;

        $contexto = $this->resolverContextoUnidades($request, $usuario);
        $unidadIds = $contexto['unidad_ids'];
        $delegacionIds = $contexto['delegacion_ids'];
        $hechosUnidadIds = $contexto['hechos_unidad_ids'];

        $hechos = $this->obtenerRowsFeed($this->queryHechos($hechosUnidadIds, false, 1, $usuario, $delegacionIds, $userIdFilter, $dateRange), $limit);
        $actividades = $this->obtenerRowsFeed($this->queryActividades($unidadIds, false, 2, $usuario, $delegacionIds, $userIdFilter, $dateRange), $limit);
        $carreteras = $this->obtenerRowsFeed($this->queryCarreteras($unidadIds, false, 3, $usuario, $userIdFilter, $dateRange), $limit);
        $vialidades = $this->obtenerRowsFeed($this->queryVialidades($unidadIds, false, 4, $userIdFilter, $dateRange), $limit);

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
                } elseif ((string) $row->type === 'HECHO') {
                    $fotoUrl = app(HechoFotoStorage::class)->url($path);
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
                'unidad_nombre' => $this->normalizaTextoOpcional($row->unidad_nombre ?? null),
                'delegacion_id' => isset($row->delegacion_id) ? (int)$row->delegacion_id : null,
                'delegacion_nombre' => $this->normalizaTextoOpcional($row->delegacion_nombre ?? null),
                'resumen' => $this->limpiaResumen($row->resumen, $row->type),
                'categoria_nombre' => isset($row->categoria_nombre) ? $row->categoria_nombre : null,
                'subcategoria_nombre' => isset($row->subcategoria_nombre) ? $row->subcategoria_nombre : null,
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
            'delegacion_ids_aplicadas' => $delegacionIds,
            'delegaciones_filtrables' => $contexto['delegaciones_filtrables'],
            'data' => $mapped,
        ]);
    }

    private function indexV2(Request $request)
    {
        $usuario = Auth::user();
        $userIdFilter = $this->parseUserIdFilter($request, $usuario);
        $dateRange = $this->parseDateRange($request);
        $limit = (int)$request->query('limit', 20);
        if ($limit < 1) $limit = 1;
        $maxLimit = $userIdFilter ? 200 : 50;
        if ($limit > $maxLimit) $limit = $maxLimit;

        $cursor = (string)$request->query('cursor', '');
        $cursorData = $this->decodeCursor($cursor);
        $contexto = $this->resolverContextoUnidades($request, $usuario);
        $unidadIds = $contexto['unidad_ids'];
        $delegacionIds = $contexto['delegacion_ids'];
        $hechosUnidadIds = $contexto['hechos_unidad_ids'];

        $sources = collect([
            $this->queryHechos($hechosUnidadIds, true, 1, $usuario, $delegacionIds, $userIdFilter, $dateRange),
            $this->queryActividades($unidadIds, true, 2, $usuario, $delegacionIds, $userIdFilter, $dateRange),
            $this->queryCarreteras($unidadIds, true, 3, $usuario, $userIdFilter, $dateRange),
            $this->queryVialidades($unidadIds, true, 4, $userIdFilter, $dateRange),
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
                'delegacion_ids_aplicadas' => $delegacionIds,
                'delegaciones_filtrables' => $contexto['delegaciones_filtrables'],
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
                } elseif ((string) $row->type === 'HECHO') {
                    $fotoUrl = app(HechoFotoStorage::class)->url($path);
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
                'unidad_nombre' => $this->normalizaTextoOpcional($row->unidad_nombre ?? null),
                'delegacion_id' => isset($row->delegacion_id) ? (int)$row->delegacion_id : null,
                'delegacion_nombre' => $this->normalizaTextoOpcional($row->delegacion_nombre ?? null),
                'resumen' => $this->limpiaResumen($row->resumen, $row->type),
                'categoria_nombre' => isset($row->categoria_nombre) ? $row->categoria_nombre : null,
                'subcategoria_nombre' => isset($row->subcategoria_nombre) ? $row->subcategoria_nombre : null,
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
            'delegacion_ids_aplicadas' => $delegacionIds,
            'delegaciones_filtrables' => $contexto['delegaciones_filtrables'],
            'data' => $mapped,
        ]);
    }

    private function resolverContextoUnidades(Request $request, $usuario): array
    {
        $puedeFiltrar = $this->puedeFiltrarUnidades($usuario);
        $delegacionIds = $this->parseDelegacionIds($request);
        $delegacionesFiltrables = $this->obtenerDelegacionesFiltrables($usuario);

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
                'hechos_unidad_ids' => array_values(array_unique(array_map('intval', $unidadIds))),
                'unidades_filtrables' => $unidadesFiltrables->values()->all(),
                'delegacion_ids' => $delegacionIds,
                'delegaciones_filtrables' => $delegacionesFiltrables->values()->all(),
            ];
        }

        $unidadIds = $usuario && $usuario->unidad_id ? [(int)$usuario->unidad_id] : [];

        if ($usuario && (int) ($usuario->unidad_id ?? 0) === 2) {
            $visibles = $this->delegacionIdsVisibles($usuario);

            $delegacionIds = empty($delegacionIds)
                ? $visibles
                : array_values(array_intersect($delegacionIds, $visibles));
        }

        return [
            'puede_filtrar' => false,
            'unidad_ids' => $unidadIds,
            'hechos_unidad_ids' => $this->unidadIdsHechosParaFeed($usuario, $unidadIds),
            'unidades_filtrables' => [],
            'delegacion_ids' => $delegacionIds,
            'delegaciones_filtrables' => $delegacionesFiltrables->values()->all(),
        ];
    }

    private function unidadIdsHechosParaFeed($usuario, array $unidadIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $unidadIds)));

        return array_values(array_unique(array_filter($ids, fn ($id) => $id > 0)));
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

    private function parseDelegacionIds(Request $request): array
    {
        $raw = $request->query('delegacion_ids', null);

        if ($raw === null || $raw === '' || $raw === []) {
            $raw = $request->query('delegacion_id', null);
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

        return DB::table('delegaciones')
            ->where(function ($q) use ($ids) {
                $q->whereIn('id', $ids)
                    ->orWhereIn('delegacion_padre_id', $ids);
            })
            ->when(Schema::hasColumn('delegaciones', 'activa'), function ($q) {
                $q->where('activa', 1);
            })
            ->pluck('id')
            ->map(function ($id) {
                return (int)$id;
            })
            ->values()
            ->all();
    }

    private function parseUserIdFilter(Request $request, $usuario): ?int
    {
        $raw = $request->query('user_id', null);

        if ($raw === null || $raw === '' || $raw === []) {
            $raw = $request->query('created_by', null);
        }

        if ($raw === null || $raw === '' || $raw === []) {
            return null;
        }

        $requestedId = (int)$raw;
        $currentUserId = (int)($usuario->id ?? 0);

        if ($requestedId <= 0 || $currentUserId <= 0) {
            return null;
        }

        return $currentUserId;
    }

    private function parseDateRange(Request $request): array
    {
        $date = $this->parseDateOnly($request->query('date', null));

        if ($date === null) {
            $date = $this->parseDateOnly($request->query('fecha', null));
        }

        if ($date !== null) {
            return ['desde' => $date, 'hasta' => $date];
        }

        $desde = $this->parseDateOnly(
            $request->query('desde', null)
                ?? $request->query('from', null)
                ?? $request->query('start_date', null)
        );
        $hasta = $this->parseDateOnly(
            $request->query('hasta', null)
                ?? $request->query('to', null)
                ?? $request->query('end_date', null)
        );

        if ($desde !== null && $hasta === null) {
            $hasta = $desde;
        }

        if ($desde === null && $hasta !== null) {
            $desde = $hasta;
        }

        if ($desde !== null && $hasta !== null && strcmp($desde, $hasta) > 0) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return ['desde' => $desde, 'hasta' => $hasta];
    }

    private function parseDateOnly($value): ?string
    {
        $text = trim((string)$value);

        if ($text === '') {
            return null;
        }

        $text = substr($text, 0, 10);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $text)) {
            return null;
        }

        try {
            return (new \DateTimeImmutable($text))->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function applyUserFilter($query, string $column, ?int $userId): void
    {
        if ($userId === null || $userId <= 0) {
            return;
        }

        $query->where($column, $userId);
    }

    private function applyDateRange($query, string $column, array $dateRange): void
    {
        $desde = $dateRange['desde'] ?? null;
        $hasta = $dateRange['hasta'] ?? null;

        if ($desde !== null) {
            $query->whereDate($column, '>=', $desde);
        }

        if ($hasta !== null) {
            $query->whereDate($column, '<=', $hasta);
        }
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

    private function obtenerDelegacionesFiltrables($usuario): Collection
    {
        $puedeFiltrarUnidades = $this->puedeFiltrarUnidades($usuario);

        if (!$puedeFiltrarUnidades && (int) ($usuario->unidad_id ?? 0) !== 2) {
            return collect();
        }

        $q = DB::table('delegaciones')
            ->whereNull('delegacion_padre_id')
            ->orderBy('nombre');

        if (Schema::hasColumn('delegaciones', 'activa')) {
            $q->where('activa', 1);
        }

        if (!$puedeFiltrarUnidades) {
            $visibles = $this->delegacionIdsVisibles($usuario);

            if (empty($visibles)) {
                return collect();
            }

            $q->whereIn('id', $visibles);
        }

        return $q->get(['id', 'clave', 'nombre', 'municipio'])
            ->map(function ($delegacion) {
                $nombre = (string) $delegacion->nombre;
                $clave = trim((string) ($delegacion->clave ?? ''));

                return [
                    'id' => (int) $delegacion->id,
                    'clave' => $clave,
                    'nombre' => $nombre,
                    'nombre_con_clave' => $clave !== '' ? "{$nombre} ({$clave})" : $nombre,
                    'municipio' => (string) ($delegacion->municipio ?? ''),
                ];
            });
    }

    private function applyDelegacionesScope($query, $usuario, string $column): void
    {
        if (!$usuario || (int) ($usuario->unidad_id ?? 0) !== 2) {
            return;
        }

        if ($this->esRolAdministrativoUnidad($usuario)) {
            return;
        }

        $ids = $this->delegacionIdsVisibles($usuario);
        if (empty($ids)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn(DB::raw($column), $ids);
    }

    private function applyDelegacionFilter($query, string $delegacionSql, array $delegacionIds): void
    {
        $delegacionIds = array_values(array_unique(array_map('intval', $delegacionIds)));

        if (empty($delegacionIds)) {
            return;
        }

        $query->whereIn(DB::raw($delegacionSql), $delegacionIds);
    }

    private function applyActiveDelegacionFeedFilter($query, string $principalAlias, ?string $fallbackAlias = null): void
    {
        if ($fallbackAlias === null) {
            $query->where(function ($where) use ($principalAlias) {
                $where->whereNull("{$principalAlias}.id")
                    ->orWhere("{$principalAlias}.activa", 1);
            });

            return;
        }

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

    private function delegacionIdsVisibles($usuario): array
    {
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);
        if ($delegacionId <= 0) {
            return [];
        }

        return HechoAccess::delegacionIdsVisiblesParaUsuario($usuario);
    }

    private function puedeVerDelegacionesHijas($usuario): bool
    {
        return $usuario->hasAnyRole(['Delegado', 'Administrativo']);
    }

    private function esRolAdministrativoUnidad($usuario): bool
    {
        return $usuario->hasRole('Administrador')
            || $usuario->hasRole('Subdirector');
    }

    private function queryHechos(array $unidadIds, bool $forUnion = false, int $typeOrder = 1, $usuario = null, array $delegacionIds = [], ?int $userIdFilter = null, array $dateRange = [])
    {
        if (empty($unidadIds)) {
            return $forUnion ? null : collect();
        }

        $unidadSql = Schema::hasColumn('hechos', 'unidad_org_id') ? 'COALESCE(h.unidad_org_id, u.unidad_id)' : 'u.unidad_id';
        $delegacionIdSql = 'COALESCE(h.delegacion_id, u.delegacion_id)';
        $delegacionNombreSql = $this->delegacionNombreSql('dh', 'duh');

        $q = DB::table('hechos as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.created_by')
            ->leftJoin('delegaciones as dh', 'dh.id', '=', 'h.delegacion_id')
            ->leftJoin('delegaciones as duh', 'duh.id', '=', 'u.delegacion_id')
            ->leftJoin('unidades as un', DB::raw($unidadSql), '=', 'un.id');

        $q->whereIn(DB::raw($unidadSql), $unidadIds);
        $this->applyActiveDelegacionFeedFilter($q, 'dh', 'duh');
        $this->applyDelegacionesScope($q, $usuario, $delegacionIdSql);
        $this->applyDelegacionFilter($q, $delegacionIdSql, $delegacionIds);
        $this->applyUserFilter($q, 'h.created_by', $userIdFilter);
        $this->applyDateRange($q, 'h.created_at', $dateRange);

        if ($forUnion) {
            return $q->selectRaw("
                'HECHO' as type,
                ? as type_order,
                h.id as item_id,
                h.created_by as user_id,
                u.name as user_name,
                {$unidadSql} as unidad_id,
                un.nombre as unidad_nombre,
                {$delegacionIdSql} as delegacion_id,
                {$delegacionNombreSql} as delegacion_nombre,
                CONCAT(TRIM(COALESCE(h.calle,'')), ', col. ', TRIM(COALESCE(h.colonia,''))) as resumen,
                NULL as categoria_nombre,
                NULL as subcategoria_nombre,
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
            un.nombre as unidad_nombre,
            {$delegacionIdSql} as delegacion_id,
            {$delegacionNombreSql} as delegacion_nombre,
            CONCAT(TRIM(COALESCE(h.calle,'')), ', col. ', TRIM(COALESCE(h.colonia,''))) as resumen,
            NULL as categoria_nombre,
            NULL as subcategoria_nombre,
            COALESCE(h.foto_lugar, h.foto_situacion) as foto_path,
            h.created_at as created_at
        ")->orderByDesc('h.created_at')->orderByDesc('h.id');
    }

    private function queryActividades(array $unidadIds, bool $forUnion = false, int $typeOrder = 2, $usuario = null, array $delegacionIds = [], ?int $userIdFilter = null, array $dateRange = [])
    {
        if (empty($unidadIds)) {
            return $forUnion ? null : collect();
        }

        $unidadSql = Schema::hasColumn('actividades', 'unidad_org_id') ? 'COALESCE(a.unidad_org_id, u.unidad_id)' : 'u.unidad_id';
        $delegacionIdSql = 'COALESCE(a.delegacion_id, u.delegacion_id)';
        $delegacionNombreSql = $this->delegacionNombreSql('da', 'dua');

        $q = DB::table('actividades as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.created_by')
            ->leftJoin('actividad_categorias as ac', 'ac.id', '=', 'a.actividad_categoria_id')
            ->leftJoin('actividad_subcategorias as asub', 'asub.id', '=', 'a.actividad_subcategoria_id')
            ->leftJoin('delegaciones as da', 'da.id', '=', 'a.delegacion_id')
            ->leftJoin('delegaciones as dua', 'dua.id', '=', 'u.delegacion_id')
            ->leftJoin('unidades as un', DB::raw($unidadSql), '=', 'un.id');

        $fotoPathSql = $this->actividadFeedFotoPathSql();
        $resumenSql = "COALESCE(
            NULLIF(TRIM(COALESCE(a.motivo,'')), ''),
            NULLIF(TRIM(COALESCE(a.lugar,'')), ''),
            NULLIF(TRIM(COALESCE(a.municipio,'')), ''),
            NULLIF(TRIM(COALESCE(a.nombre,'')), ''),
            'Actividad registrada'
        )";
        $q->whereIn(DB::raw($unidadSql), $unidadIds);
        $this->applyActiveDelegacionFeedFilter($q, 'da', 'dua');
        $this->applyDelegacionesScope($q, $usuario, $delegacionIdSql);
        $this->applyDelegacionFilter($q, $delegacionIdSql, $delegacionIds);
        $this->applyUserFilter($q, 'a.created_by', $userIdFilter);
        $this->applyDateRange($q, 'a.created_at', $dateRange);

        if ($forUnion) {
            return $q->selectRaw("
                'ACTIVIDAD' as type,
                ? as type_order,
                a.id as item_id,
                a.created_by as user_id,
                u.name as user_name,
                {$unidadSql} as unidad_id,
                un.nombre as unidad_nombre,
                {$delegacionIdSql} as delegacion_id,
                {$delegacionNombreSql} as delegacion_nombre,
                {$resumenSql} as resumen,
                ac.nombre as categoria_nombre,
                asub.nombre as subcategoria_nombre,
                {$fotoPathSql} as foto_path,
                a.created_at as created_at
            ", [$typeOrder]);
        }

        return $q->selectRaw("
            'ACTIVIDAD' as type,
            a.id as item_id,
            a.created_by as user_id,
            u.name as user_name,
            {$unidadSql} as unidad_id,
            un.nombre as unidad_nombre,
            {$delegacionIdSql} as delegacion_id,
            {$delegacionNombreSql} as delegacion_nombre,
            {$resumenSql} as resumen,
            ac.nombre as categoria_nombre,
            asub.nombre as subcategoria_nombre,
            {$fotoPathSql} as foto_path,
            a.created_at as created_at
        ")->orderByDesc('a.created_at')->orderByDesc('a.id');
    }

    private function actividadFeedFotoPathSql(): string
    {
        if (!Schema::hasTable('actividad_fotos')) {
            return 'COALESCE(a.foto_thumbnail_path, a.foto_path)';
        }

        return "COALESCE(
            a.foto_thumbnail_path,
            (
                SELECT af.foto_thumbnail_path
                FROM actividad_fotos af
                WHERE af.actividad_id = a.id
                  AND af.foto_eliminada_at IS NULL
                  AND af.foto_thumbnail_path IS NOT NULL
                ORDER BY af.orden ASC, af.id ASC
                LIMIT 1
            ),
            a.foto_path,
            (
                SELECT af.foto_path
                FROM actividad_fotos af
                WHERE af.actividad_id = a.id
                  AND af.foto_eliminada_at IS NULL
                  AND af.foto_path IS NOT NULL
                ORDER BY af.orden ASC, af.id ASC
                LIMIT 1
            )
        )";
    }

    private function queryCarreteras(array $unidadIds, bool $forUnion = false, int $typeOrder = 3, $usuario = null, ?int $userIdFilter = null, array $dateRange = [])
    {
        if (!Schema::hasTable('operativo_dispositivos') || !in_array(4, $unidadIds, true)) {
            return $forUnion ? null : collect();
        }

        $fotoPathSql = $this->carreterasFotoPathSql();

        $q = DB::table('operativo_dispositivos as od')
            ->join('users as u', 'u.id', '=', 'od.created_by')
            ->leftJoin('unidades as un', 'un.id', '=', 'od.unidad_org_id')
            ->leftJoin('delegaciones as duod', 'duod.id', '=', 'u.delegacion_id')
            ->where('od.unidad_org_id', 4);
        $delegacionNombreSql = $this->delegacionNombreSql('duod');

        $userId = (int) ($usuario->id ?? 0);
        $q->where(function ($w) use ($userId) {
            $w->where('od.estado_revision', 'aprobado');

            if ($userId > 0) {
                $w->orWhere('od.user_id', $userId);
            }
        });
        $this->applyActiveDelegacionFeedFilter($q, 'duod');
        $this->applyUserFilter($q, 'od.created_by', $userIdFilter);
        $this->applyDateRange($q, 'od.created_at', $dateRange);

        if ($forUnion) {
            return $q->selectRaw("
                'CARRETERAS' as type,
                ? as type_order,
                od.id as item_id,
                od.created_by as user_id,
                u.name as user_name,
                od.unidad_org_id as unidad_id,
                un.nombre as unidad_nombre,
                u.delegacion_id as delegacion_id,
                {$delegacionNombreSql} as delegacion_nombre,
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
                NULL as categoria_nombre,
                NULL as subcategoria_nombre,
                {$fotoPathSql} as foto_path,
                od.created_at as created_at
            ", [$typeOrder]);
        }

        return $q->selectRaw("
            'CARRETERAS' as type,
            od.id as item_id,
            od.created_by as user_id,
            u.name as user_name,
            od.unidad_org_id as unidad_id,
            un.nombre as unidad_nombre,
            u.delegacion_id as delegacion_id,
            {$delegacionNombreSql} as delegacion_nombre,
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
            NULL as categoria_nombre,
            NULL as subcategoria_nombre,
            {$fotoPathSql} as foto_path,
            od.created_at as created_at
        ")->orderByDesc('od.created_at')->orderByDesc('od.id');
    }

    private function carreterasFotoPathSql(): string
    {
        if (!Schema::hasTable('operativo_dispositivo_fotos')) {
            return 'NULL';
        }

        return "(
            SELECT odf.ruta
            FROM operativo_dispositivo_fotos odf
            WHERE odf.operativo_dispositivo_id = od.id
              AND (odf.incluida_en_compartido = 1 OR odf.incluida_en_compartido IS NULL)
            ORDER BY odf.es_portada DESC, odf.orden ASC, odf.id ASC
            LIMIT 1
        )";
    }

    private function queryVialidades(array $unidadIds, bool $forUnion = false, int $typeOrder = 4, ?int $userIdFilter = null, array $dateRange = [])
    {
        if (!Schema::hasTable('vialidad_dispositivos') || !Schema::hasTable('vialidad_dispositivo_detalles') || !in_array(5, $unidadIds, true)) {
            return $forUnion ? null : collect();
        }

        $unidadSql = Schema::hasColumn('vialidad_dispositivos', 'unidad_id') ? 'vd.unidad_id' : 'u.unidad_id';
        $delegacionNombreSql = $this->delegacionNombreSql('duvd');

        $q = DB::table('vialidad_dispositivos as vd')
            ->join('users as u', 'u.id', '=', 'vd.created_by')
            ->leftJoin('unidades as un', DB::raw($unidadSql), '=', 'un.id')
            ->leftJoin('delegaciones as duvd', 'duvd.id', '=', 'u.delegacion_id');

        if (Schema::hasColumn('vialidad_dispositivos', 'unidad_id')) {
            $q->where('vd.unidad_id', 5);
        } elseif (Schema::hasColumn('users', 'unidad_id')) {
            $q->where('u.unidad_id', 5);
        }
        $this->applyActiveDelegacionFeedFilter($q, 'duvd');
        $this->applyUserFilter($q, 'vd.created_by', $userIdFilter);
        $this->applyDateRange($q, 'vd.created_at', $dateRange);

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
                {$unidadSql} as unidad_id,
                un.nombre as unidad_nombre,
                u.delegacion_id as delegacion_id,
                {$delegacionNombreSql} as delegacion_nombre,
                {$resumenSql} as resumen,
                NULL as categoria_nombre,
                NULL as subcategoria_nombre,
                NULL as foto_path,
                vd.created_at as created_at
            ", [$typeOrder]);
        }

        return $q->selectRaw("
            'VIALIDADES' as type,
            vd.id as item_id,
            vd.created_by as user_id,
            u.name as user_name,
            {$unidadSql} as unidad_id,
            un.nombre as unidad_nombre,
            u.delegacion_id as delegacion_id,
            {$delegacionNombreSql} as delegacion_nombre,
            {$resumenSql} as resumen,
            NULL as categoria_nombre,
            NULL as subcategoria_nombre,
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

    private function delegacionNombreSql(string $principalAlias, ?string $fallbackAlias = null): string
    {
        $principal = $this->delegacionNombreCaseSql($principalAlias);

        if ($fallbackAlias === null) {
            return $principal;
        }

        return "COALESCE({$principal}, {$this->delegacionNombreCaseSql($fallbackAlias)})";
    }

    private function delegacionNombreCaseSql(string $alias): string
    {
        return "CASE
            WHEN {$alias}.id IS NOT NULL AND COALESCE(TRIM({$alias}.clave), '') <> '' THEN CONCAT({$alias}.nombre, ' (', {$alias}.clave, ')')
            WHEN {$alias}.id IS NOT NULL THEN {$alias}.nombre
            ELSE NULL
        END";
    }

    private function normalizaTextoOpcional($valor): ?string
    {
        $texto = trim((string)$valor);

        return $texto === '' ? null : $texto;
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
