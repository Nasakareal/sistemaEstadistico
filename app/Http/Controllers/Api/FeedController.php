<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
    }

    public function index(Request $request)
    {
        $v = (string) $request->query('v', '1');
        if ($v === '2') {
            return $this->indexV2($request);
        }

        $limit = (int) $request->query('limit', 50);
        if ($limit < 1) $limit = 1;
        if ($limit > 50) $limit = 50;

        $hechos = DB::table('hechos as h')
            ->join('users as u', 'u.id', '=', 'h.created_by')
            ->selectRaw("
                'HECHO' as type,
                h.id as item_id,
                h.created_by as user_id,
                u.name as user_name,
                CONCAT(TRIM(COALESCE(h.calle,'')), ', col. ', TRIM(COALESCE(h.colonia,''))) as resumen,
                COALESCE(h.foto_lugar, h.foto_situacion) as foto_path,
                h.created_at as created_at
            ")
            ->orderByDesc('h.created_at')
            ->orderByDesc('h.id')
            ->limit($limit)
            ->get();

        $actividades = DB::table('actividades as a')
            ->join('users as u', 'u.id', '=', 'a.created_by')
            ->selectRaw("
                'ACTIVIDAD' as type,
                a.id as item_id,
                a.created_by as user_id,
                u.name as user_name,
                a.nombre as resumen,
                a.foto_path as foto_path,
                a.created_at as created_at
            ")
            ->orderByDesc('a.created_at')
            ->orderByDesc('a.id')
            ->limit($limit)
            ->get();

        $items = $hechos->concat($actividades)
            ->sortByDesc('created_at')
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
                'created_at' => $row->created_at,
                'show_url'   => $row->type === 'HECHO'
                    ? route('hechos.show', $row->item_id)
                    : route('actividades.show', $row->item_id),
            ];
        });

        return response()->json([
            'limit_each' => $limit,
            'count' => $mapped->count(),
            'data' => $mapped,
        ]);
    }

    private function indexV2(Request $request)
    {
        $limit = (int) $request->query('limit', 20);
        if ($limit < 1) $limit = 1;
        if ($limit > 50) $limit = 50;

        $cursor = (string) $request->query('cursor', '');
        $cursorData = $this->decodeCursor($cursor);

        $hechos = DB::table('hechos as h')
            ->join('users as u', 'u.id', '=', 'h.created_by')
            ->selectRaw("
                'HECHO' as type,
                1 as type_order,
                h.id as item_id,
                h.created_by as user_id,
                u.name as user_name,
                CONCAT(TRIM(COALESCE(h.calle,'')), ', col. ', TRIM(COALESCE(h.colonia,''))) as resumen,
                COALESCE(h.foto_lugar, h.foto_situacion) as foto_path,
                h.created_at as created_at
            ");

        $actividades = DB::table('actividades as a')
            ->join('users as u', 'u.id', '=', 'a.created_by')
            ->selectRaw("
                'ACTIVIDAD' as type,
                2 as type_order,
                a.id as item_id,
                a.created_by as user_id,
                u.name as user_name,
                a.nombre as resumen,
                a.foto_path as foto_path,
                a.created_at as created_at
            ");

        $union = $hechos->unionAll($actividades);

        $q = DB::query()
            ->fromSub($union, 'f');

        if ($cursorData) {
            $created_at = $cursorData['created_at'] ?? null;
            $type_order = isset($cursorData['type_order']) ? (int) $cursorData['type_order'] : null;
            $item_id    = isset($cursorData['item_id']) ? (int) $cursorData['item_id'] : null;

            if ($created_at && $type_order && $item_id) {
                $q->where(function ($w) use ($created_at, $type_order, $item_id) {
                    $w->where('created_at', '<', $created_at)
                      ->orWhere(function ($w2) use ($created_at, $type_order, $item_id) {
                          $w2->where('created_at', '=', $created_at)
                             ->where('type_order', '>', $type_order)
                             ->orWhere(function ($w3) use ($created_at, $type_order, $item_id) {
                                 $w3->where('created_at', '=', $created_at)
                                    ->where('type_order', '=', $type_order)
                                    ->where('item_id', '<', $item_id);
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

        $has_more = $rows->count() > $limit;
        if ($has_more) {
            $rows = $rows->take($limit)->values();
        }

        $mapped = $rows->map(function ($row) {
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
                'created_at' => $row->created_at,
                'show_url'   => $row->type === 'HECHO'
                    ? route('hechos.show', $row->item_id)
                    : route('actividades.show', $row->item_id),
                '_type_order' => (int) $row->type_order,
            ];
        });

        $next_cursor = null;
        if ($mapped->count() > 0) {
            $last = $mapped->last();
            $next_cursor = $this->encodeCursor([
                'created_at' => $last['created_at'],
                'type_order' => $last['_type_order'],
                'item_id'    => $last['id'],
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
            'has_more' => $has_more,
            'next_cursor' => $next_cursor,
            'data' => $mapped,
        ]);
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
