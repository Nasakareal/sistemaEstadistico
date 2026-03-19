<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgenteUpecHomeController extends Controller
{
    public function mapa(Request $request)
    {
        $tz = config('app.timezone', 'America/Mexico_City');

        $wazeHours = (int) $request->get('waze_hours', 12);
        $wazeHours = $wazeHours > 0 ? $wazeHours : 12;

        $tipo = strtoupper(trim((string) $request->get('tipo', 'TODOS')));
        $tiposPermitidos = ['TODOS', 'CHOQUES', 'CIERRES'];

        if (!in_array($tipo, $tiposPermitidos, true)) {
            $tipo = 'TODOS';
        }

        $wazeStart = Carbon::now($tz)->subHours($wazeHours);

        $rows = DB::table('waze_alerts')
            ->select([
                'id',
                'uuid',
                'waze_id',
                'type',
                'subtype',
                'country',
                'city',
                'street',
                'street_norm',
                'lat',
                'lng',
                'cell_key',
                'pub_millis',
                'published_at',
                'notified',
                'is_read',
                'created_at',
                'updated_at',
            ])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where(function ($q) use ($wazeStart) {
                $q->where('published_at', '>=', $wazeStart)
                    ->orWhere(function ($q2) use ($wazeStart) {
                        $q2->whereNull('published_at')
                            ->where('created_at', '>=', $wazeStart);
                    });
            })
            ->where(function ($q) use ($tipo) {
                if ($tipo === 'CHOQUES') {
                    $q->where(function ($qq) {
                        $qq->whereRaw('UPPER(type) = "ACCIDENT"')
                            ->orWhereRaw('UPPER(subtype) = "ACCIDENT"')
                            ->orWhereRaw('UPPER(subtype) = "CRASH"')
                            ->orWhereRaw('UPPER(type) LIKE "%ACCIDENT%"')
                            ->orWhereRaw('UPPER(subtype) LIKE "%ACCIDENT%"')
                            ->orWhereRaw('UPPER(subtype) LIKE "%CRASH%"');
                    });
                    return;
                }

                if ($tipo === 'CIERRES') {
                    $q->where(function ($qq) {
                        $qq->whereRaw('UPPER(type) = "ROAD_CLOSED"')
                            ->orWhereRaw('UPPER(subtype) = "ROAD_CLOSED"')
                            ->orWhereRaw('UPPER(type) LIKE "%ROAD_CLOSED%"')
                            ->orWhereRaw('UPPER(subtype) LIKE "%ROAD_CLOSED%"')
                            ->orWhereRaw('UPPER(type) LIKE "%CLOSED%"')
                            ->orWhereRaw('UPPER(subtype) LIKE "%CLOSED%"');
                    });
                    return;
                }

                $q->where(function ($qq) {
                    $qq->whereRaw('UPPER(type) = "ACCIDENT"')
                        ->orWhereRaw('UPPER(subtype) = "ACCIDENT"')
                        ->orWhereRaw('UPPER(subtype) = "CRASH"')
                        ->orWhereRaw('UPPER(type) LIKE "%ACCIDENT%"')
                        ->orWhereRaw('UPPER(subtype) LIKE "%ACCIDENT%"')
                        ->orWhereRaw('UPPER(subtype) LIKE "%CRASH%"')
                        ->orWhereRaw('UPPER(type) = "ROAD_CLOSED"')
                        ->orWhereRaw('UPPER(subtype) = "ROAD_CLOSED"')
                        ->orWhereRaw('UPPER(type) LIKE "%ROAD_CLOSED%"')
                        ->orWhereRaw('UPPER(subtype) LIKE "%ROAD_CLOSED%"')
                        ->orWhereRaw('UPPER(type) LIKE "%CLOSED%"')
                        ->orWhereRaw('UPPER(subtype) LIKE "%CLOSED%"');
                });
            })
            ->orderByDesc(DB::raw('COALESCE(published_at, created_at)'))
            ->limit(200)
            ->get();

        $items = [];

        foreach ($rows as $row) {
            $alertType = $this->resolveAlertType($row->type, $row->subtype);
            $published = $row->published_at ?: $row->created_at;

            $items[] = [
                'type' => $alertType === 'cierre' ? 'waze_road_closed' : 'waze_accident',
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'waze_id' => $row->waze_id,
                'waze_type' => $row->type,
                'waze_subtype' => $row->subtype,
                'country' => $row->country,
                'lat' => round((float) $row->lat, 6),
                'lng' => round((float) $row->lng, 6),
                'street' => $row->street,
                'street_norm' => $row->street_norm,
                'city' => $row->city,
                'cell_key' => $row->cell_key,
                'pub_millis' => $row->pub_millis,
                'notified' => (bool) ($row->notified ?? false),
                'is_read' => (bool) ($row->is_read ?? false),
                'title' => $alertType === 'cierre' ? 'CIERRE CARRETERO WAZE' : 'CHOQUE WAZE',
                'subtitle' => $row->street ?: ($row->city ?: 'Ubicación sin calle'),
                'published_at' => $published
                    ? Carbon::parse($published)->timezone($tz)->toDateTimeString()
                    : null,
                'style' => [
                    'kind' => 'marker',
                    'icon' => $alertType === 'cierre' ? 'waze_road_closed' : 'waze_accident',
                    'marker_color' => $alertType === 'cierre' ? '#FF6F00' : '#FFD600',
                    'border_color' => $alertType === 'cierre' ? '#BF360C' : '#D50000',
                    'pulse' => true,
                    'z_index' => $alertType === 'cierre' ? 110 : 100,
                ],
            ];
        }

        $center = $this->resolveCenter($items);

        return response()->json([
            'meta' => [
                'waze_hours' => $wazeHours,
                'tipo' => $tipo,
                'generated_at' => Carbon::now($tz)->toDateTimeString(),
                'timezone' => $tz,
            ],
            'map' => [
                'center' => $center,
                'zoom' => 12,
            ],
            'layers' => [
                'waze_alerts' => $items,
            ],
            'counts' => [
                'total' => count($items),
                'choques' => count(array_filter($items, fn ($i) => $i['type'] === 'waze_accident')),
                'cierres' => count(array_filter($items, fn ($i) => $i['type'] === 'waze_road_closed')),
            ],
        ], 200);
    }

    public function filtros(Request $request)
    {
        return response()->json([
            'tipo_options' => [
                ['value' => 'TODOS', 'label' => 'Todos'],
                ['value' => 'CHOQUES', 'label' => 'Choques'],
                ['value' => 'CIERRES', 'label' => 'Cierres carreteros'],
            ],
            'waze_hours_options' => [1, 3, 6, 12, 24],
            'default_values' => [
                'tipo' => 'TODOS',
                'waze_hours' => 12,
            ],
        ], 200);
    }

    public function show($id)
    {
        $row = DB::table('waze_alerts')
            ->select([
                'id',
                'uuid',
                'waze_id',
                'type',
                'subtype',
                'country',
                'city',
                'street',
                'street_norm',
                'lat',
                'lng',
                'cell_key',
                'pub_millis',
                'published_at',
                'notified',
                'is_read',
                'raw',
                'created_at',
                'updated_at',
            ])
            ->where('id', $id)
            ->first();

        if (!$row) {
            return response()->json([
                'message' => 'Alerta Waze no encontrada.',
            ], 404);
        }

        $tz = config('app.timezone', 'America/Mexico_City');
        $alertType = $this->resolveAlertType($row->type, $row->subtype);
        $published = $row->published_at ?: $row->created_at;

        return response()->json([
            'alerta' => [
                'id' => (int) $row->id,
                'uuid' => (string) $row->uuid,
                'waze_id' => $row->waze_id,
                'tipo' => $alertType,
                'waze_type' => $row->type,
                'waze_subtype' => $row->subtype,
                'title' => $alertType === 'cierre' ? 'CIERRE CARRETERO WAZE' : 'CHOQUE WAZE',
                'country' => $row->country,
                'street' => $row->street,
                'street_norm' => $row->street_norm,
                'city' => $row->city,
                'cell_key' => $row->cell_key,
                'pub_millis' => $row->pub_millis,
                'lat' => $row->lat !== null ? (float) $row->lat : null,
                'lng' => $row->lng !== null ? (float) $row->lng : null,
                'notified' => (bool) ($row->notified ?? false),
                'is_read' => (bool) ($row->is_read ?? false),
                'raw' => $row->raw ? json_decode($row->raw, true) : null,
                'published_at' => $published
                    ? Carbon::parse($published)->timezone($tz)->toDateTimeString()
                    : null,
                'created_at' => $row->created_at
                    ? Carbon::parse($row->created_at)->timezone($tz)->toDateTimeString()
                    : null,
                'updated_at' => $row->updated_at
                    ? Carbon::parse($row->updated_at)->timezone($tz)->toDateTimeString()
                    : null,
            ],
        ], 200);
    }

    private function resolveAlertType(?string $type, ?string $subtype): string
    {
        $type = strtoupper(trim((string) $type));
        $subtype = strtoupper(trim((string) $subtype));

        if (
            $type === 'ROAD_CLOSED' ||
            $subtype === 'ROAD_CLOSED' ||
            str_contains($type, 'ROAD_CLOSED') ||
            str_contains($subtype, 'ROAD_CLOSED') ||
            str_contains($type, 'CLOSED') ||
            str_contains($subtype, 'CLOSED')
        ) {
            return 'cierre';
        }

        return 'choque';
    }

    private function resolveCenter(array $items): array
    {
        if (!empty($items)) {
            return [
                'lat' => (float) $items[0]['lat'],
                'lng' => (float) $items[0]['lng'],
            ];
        }

        return [
            'lat' => 19.705950,
            'lng' => -101.194983,
        ];
    }
}
