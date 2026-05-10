<?php

namespace App\Services\Waze;

use App\Models\Hechos;
use Carbon\Carbon;

class WazeFeedService
{
    private WazeReverseGeocodingService $reverseGeocoder;

    public function __construct(WazeReverseGeocodingService $reverseGeocoder)
    {
        $this->reverseGeocoder = $reverseGeocoder;
    }

    public function buildIncidentsFeed(): array
    {
        $hechos = $this->queryHechos();

        $incidents = [];

        foreach ($hechos as $hecho) {
            $incident = $this->mapHechoToIncident($hecho);

            if ($incident !== null) {
                $incidents[] = $incident;
            }
        }

        return [
            'incidents' => $incidents,
        ];
    }

    public function buildDebugReport(): array
    {
        $hechos = $this->queryHechos();
        $skipped = [];
        $examples = [];
        $included = 0;

        foreach ($hechos as $hecho) {
            $reason = $this->skipReason($hecho);

            if ($reason === null) {
                $included++;
                continue;
            }

            $skipped[$reason] = ($skipped[$reason] ?? 0) + 1;

            if (count($examples) < 15) {
                $examples[] = [
                    'id' => $hecho->id,
                    'reason' => $reason,
                    'fecha' => $this->normalizeDate($hecho->fecha ?? null),
                    'hora' => $this->normalizeTime($hecho->hora ?? null),
                    'situacion' => $hecho->situacion,
                    'tipo_hecho' => $hecho->tipo_hecho,
                    'calle' => $hecho->calle,
                    'lat' => $hecho->lat,
                    'lng' => $hecho->lng,
                ];
            }
        }

        ksort($skipped);

        return [
            'now' => Carbon::now('America/Mexico_City')->format('c'),
            'hours_back' => (int) config('waze.hours_back', 6),
            'require_reverse_geocoding_match' => (bool) config('waze.require_reverse_geocoding_match', false),
            'reverse_geocoding_enabled' => (bool) config('waze.reverse_geocoding_enabled', true),
            'reverse_geocoding_token_configured' => trim((string) config('waze.reverse_geocoding_token', '')) !== '',
            'candidates_after_main_filters' => $hechos->count(),
            'included' => $included,
            'skipped' => $skipped,
            'examples' => $examples,
        ];
    }

    protected function queryHechos()
    {
        $hoursBack = (int) config('waze.hours_back', 6);
        $since = Carbon::now('America/Mexico_City')->subHours($hoursBack);

        return Hechos::query()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereIn('situacion', ['PENDIENTE', 'RESUELTO'])
            ->where(function ($q) use ($since) {
                $q->where('created_at', '>=', $since)
                  ->orWhere('updated_at', '>=', $since)
                  ->orWhereDate('fecha', '>=', $since->toDateString());
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get();
    }

    protected function mapHechoToIncident($hecho): ?array
    {
        if (!$this->isSupportedTrafficEvent((string) $hecho->tipo_hecho)) {
            return null;
        }

        $startTime = $this->resolveStartTime($hecho);

        if ($startTime === null) {
            return null;
        }

        $lat = is_numeric($hecho->lat) ? (float) $hecho->lat : null;
        $lng = is_numeric($hecho->lng) ? (float) $hecho->lng : null;

        if ($lat === null || $lng === null) {
            return null;
        }

        if (!$this->isValidCoordinatePair($lat, $lng)) {
            return null;
        }

        $wazeStreet = $this->resolveWazeStreet($lat, $lng);

        if ($wazeStreet === null && (bool) config('waze.require_reverse_geocoding_match', false)) {
            return null;
        }

        $type = $this->resolveFeedType($hecho);

        $polyline = $this->buildPolyline($lat, $lng, $hecho, $type);

        if ($polyline === null) {
            return null;
        }

        $street = $this->buildStreet($hecho, $wazeStreet);

        if ($street === null) {
            return null;
        }

        $payload = [
            'id' => 'hecho_' . $hecho->id,
            'type' => $type,
            'polyline' => $polyline,
            'direction' => $this->resolveDirection($hecho, $type),
            'street' => $street,
            'starttime' => $startTime->format('c'),
            'creationtime' => $this->resolveCreationTime($hecho, $startTime)->format('c'),
            'updatetime' => $this->resolveUpdateTime($hecho, $startTime)->format('c'),
            'description' => $this->buildDescription($hecho, $type),
        ];

        $subtype = $this->resolveSubtype($hecho, $type);

        if ($subtype !== null) {
            $payload['subtype'] = $subtype;
        }

        $payload['endtime'] = $this->resolveEndTime($hecho, $startTime, $type)->format('c');

        return $payload;
    }

    protected function skipReason($hecho): ?string
    {
        if (!$this->isSupportedTrafficEvent((string) $hecho->tipo_hecho)) {
            return 'unsupported_tipo_hecho';
        }

        $startTime = $this->resolveStartTime($hecho);

        if ($startTime === null) {
            return 'invalid_start_time';
        }

        $lat = is_numeric($hecho->lat) ? (float) $hecho->lat : null;
        $lng = is_numeric($hecho->lng) ? (float) $hecho->lng : null;

        if ($lat === null || $lng === null) {
            return 'missing_coordinates';
        }

        if (!$this->isValidCoordinatePair($lat, $lng)) {
            return 'invalid_coordinates';
        }

        $wazeStreet = $this->resolveWazeStreet($lat, $lng);

        if ($wazeStreet === null && (bool) config('waze.require_reverse_geocoding_match', false)) {
            return 'reverse_geocoding_no_match';
        }

        $type = $this->resolveFeedType($hecho);

        if ($this->buildPolyline($lat, $lng, $hecho, $type) === null) {
            return 'missing_polyline';
        }

        if ($this->buildStreet($hecho, $wazeStreet) === null) {
            return 'missing_street';
        }

        return null;
    }

    protected function isSupportedTrafficEvent(string $tipoHecho): bool
    {
        $tipo = mb_strtoupper(trim($tipoHecho), 'UTF-8');

        if ($tipo === '') {
            return false;
        }

        return
            str_contains($tipo, 'COLISIÓN') ||
            str_contains($tipo, 'COLISION') ||
            str_contains($tipo, 'CHOQUE') ||
            str_contains($tipo, 'VOLCADURA') ||
            str_contains($tipo, 'ATROPELLAMIENTO') ||
            str_contains($tipo, 'PEATÓN') ||
            str_contains($tipo, 'PEATON') ||
            str_contains($tipo, 'CAÍDA') ||
            str_contains($tipo, 'CAIDA') ||
            str_contains($tipo, 'SALIDA DE SUPERFICIE');
    }

    protected function resolveFeedType($hecho): string
    {
        if ($this->shouldPublishAsRoadClosure($hecho) && $this->storedPolyline($hecho) !== null) {
            return 'ROAD_CLOSED';
        }

        return 'ACCIDENT';
    }

    protected function resolveSubtype($hecho, string $type): ?string
    {
        if ($type === 'ROAD_CLOSED') {
            return 'ROAD_CLOSED_HAZARD';
        }

        if ($type !== 'ACCIDENT') {
            return null;
        }

        $tipo = mb_strtoupper(trim((string) ($hecho->tipo_hecho ?? '')), 'UTF-8');

        if (
            str_contains($tipo, 'VOLCADURA') ||
            str_contains($tipo, 'ATROPELLAMIENTO') ||
            str_contains($tipo, 'PEATÓN') ||
            str_contains($tipo, 'PEATON')
        ) {
            return 'ACCIDENT_MAJOR';
        }

        return 'ACCIDENT_MINOR';
    }

    protected function shouldPublishAsRoadClosure($hecho): bool
    {
        if ((bool) config('waze.publish_accidents_as_closures', false)) {
            return true;
        }

        $situacion = mb_strtoupper(trim((string) ($hecho->situacion ?? '')), 'UTF-8');

        if ($situacion === 'RESUELTO') {
            return false;
        }

        $haystack = mb_strtoupper(implode(' ', array_filter([
            $hecho->tipo_hecho ?? null,
            $hecho->calle ?? null,
            $hecho->entre_calles ?? null,
            $hecho->causas ?? null,
            $hecho->danos_patrimoniales ?? null,
            $hecho->propiedades_afectadas ?? null,
        ], function ($value) {
            return trim((string) $value) !== '';
        })), 'UTF-8');

        if ($haystack === '') {
            return false;
        }

        foreach ([
            'CIERRE',
            'CERRAD',
            'BLOQUE',
            'OBSTRUC',
            'SIN PASO',
            'NO HAY PASO',
            'CORTE A LA CIRCULACION',
            'CORTE A LA CIRCULACIÓN',
            'CARRIL CERRADO',
            'VIALIDAD CERRADA',
        ] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function resolveStartTime($hecho): ?Carbon
    {
        try {
            if (!empty($hecho->fecha) && !empty($hecho->hora)) {
                $fecha = $this->normalizeDate($hecho->fecha);
                $hora = $this->normalizeTime($hecho->hora);

                if ($fecha === null || $hora === null) {
                    return null;
                }

                return Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $fecha . ' ' . $hora,
                    'America/Mexico_City'
                );
            }

            if (!empty($hecho->fecha)) {
                $fecha = $this->normalizeDate($hecho->fecha);

                if ($fecha !== null) {
                    return Carbon::createFromFormat(
                        'Y-m-d H:i:s',
                        $fecha . ' 00:00:00',
                        'America/Mexico_City'
                    );
                }
            }

            if (!empty($hecho->created_at)) {
                return Carbon::parse($hecho->created_at)->setTimezone('America/Mexico_City');
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected function resolveCreationTime($hecho, Carbon $fallback): Carbon
    {
        if (!empty($hecho->created_at)) {
            return Carbon::parse($hecho->created_at)->setTimezone('America/Mexico_City');
        }

        return $fallback->copy();
    }

    protected function resolveUpdateTime($hecho, Carbon $fallback): Carbon
    {
        if (!empty($hecho->updated_at)) {
            return Carbon::parse($hecho->updated_at)->setTimezone('America/Mexico_City');
        }

        return $fallback->copy();
    }

    protected function normalizeDate($value): ?string
    {
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->format('Y-m-d');
            }

            $value = trim((string) $value);

            if ($value === '') {
                return null;
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $matches)) {
                return $matches[0];
            }

            return Carbon::parse($value, 'America/Mexico_City')->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function normalizeTime($value): ?string
    {
        try {
            if ($value instanceof \DateTimeInterface) {
                return Carbon::instance($value)->format('H:i:s');
            }

            $value = trim((string) $value);

            if ($value === '') {
                return null;
            }

            if (preg_match('/^\d{2}:\d{2}$/', $value)) {
                return $value . ':00';
            }

            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
                return $value;
            }

            return Carbon::parse($value, 'America/Mexico_City')->format('H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function resolveEndTime($hecho, Carbon $startTime, string $type): Carbon
    {
        $minutes = $type === 'ROAD_CLOSED'
            ? (int) config('waze.default_closure_minutes', 30)
            : (int) config('waze.default_incident_minutes', 120);

        if ($minutes < 5) {
            $minutes = $type === 'ROAD_CLOSED' ? 30 : 120;
        }

        $endTime = $startTime->copy()->addMinutes($minutes);
        $updateBasedEndTime = $this->resolveUpdateTime($hecho, $startTime)
            ->copy()
            ->addMinutes($minutes);

        if ($updateBasedEndTime->gt($endTime)) {
            $endTime = $updateBasedEndTime;
        }

        $situacion = mb_strtoupper(trim((string) ($hecho->situacion ?? '')), 'UTF-8');
        $now = Carbon::now('America/Mexico_City');

        if ($situacion !== 'RESUELTO' && $endTime->lte($now)) {
            $endTime = $now->copy()->addMinutes($minutes);
        }

        return $endTime;
    }

    protected function resolveDirection($hecho, string $type): string
    {
        if ($type === 'ROAD_CLOSED') {
            return 'BOTH_DIRECTIONS';
        }

        return 'BOTH_DIRECTIONS';
    }

    protected function buildPolyline(float $lat, float $lng, $hecho, string $type): ?string
    {
        $storedPolyline = $this->storedPolyline($hecho);

        if ($type === 'ROAD_CLOSED') {
            return $storedPolyline;
        }

        // Incidents do not affect routing. A single-point polyline plus direction
        // avoids fabricating short segments that Waze may reject during road matching.
        return $this->formatPolyline([
            [$lat, $lng],
        ]);
    }

    protected function storedPolyline($hecho): ?string
    {
        foreach (['waze_polyline', 'road_polyline', 'polyline'] as $field) {
            $polyline = trim((string) ($hecho->{$field} ?? ''));

            if ($polyline === '') {
                continue;
            }

            $normalized = $this->normalizePolyline($polyline);

            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    protected function buildPointPolyline(float $lat, float $lng, $hecho = null): string
    {
        $tramoPolyline = $this->buildPolylineFromNearbyTramo($lat, $lng);

        if ($tramoPolyline !== null) {
            return $tramoPolyline;
        }

        $street = mb_strtoupper(trim((string) ($hecho->calle ?? '')), 'UTF-8');
        $bearing = $this->bearingFromStreet($street);
        $halfMeters = max(8, (float) config('waze.generated_polyline_half_meters', 18));

        $start = $this->offsetCoordinate($lat, $lng, $bearing + 180, $halfMeters);
        $end = $this->offsetCoordinate($lat, $lng, $bearing, $halfMeters);

        return $this->formatPolyline([
            $start,
            $end,
        ]);
    }

    protected function resolveWazeStreet(float $lat, float $lng): ?string
    {
        $match = $this->reverseGeocoder->nearestStreet($lat, $lng);

        if (!is_array($match)) {
            return null;
        }

        $street = trim((string) ($match['street'] ?? ''));

        return $street !== '' ? $street : null;
    }

    protected function buildPolylineFromNearbyTramo(float $lat, float $lng): ?string
    {
        $columns = $this->tramosGeometryColumns();
        $hasPuntosJson = in_array('puntos_json', $columns, true);
        $hasEndpointCoords = count(array_intersect(['lat_inicio', 'lng_inicio', 'lat_fin', 'lng_fin'], $columns)) === 4;

        if (!$hasPuntosJson && !$hasEndpointCoords) {
            return null;
        }

        $query = \App\Models\Tramo::query();

        if (in_array('activo', $columns, true)) {
            $query->where('activo', 1);
        }

        $query->where(function ($where) use ($hasPuntosJson, $hasEndpointCoords) {
            if ($hasPuntosJson) {
                $where->whereNotNull('puntos_json');
            }

            if ($hasEndpointCoords) {
                $method = $hasPuntosJson ? 'orWhere' : 'where';
                $where->{$method}(function ($q) {
                    $q->whereNotNull('lat_inicio')
                        ->whereNotNull('lng_inicio')
                        ->whereNotNull('lat_fin')
                        ->whereNotNull('lng_fin');
                });
            }
        });

        $tramos = $query->get(array_values(array_unique(array_merge(['id'], $columns))));

        $best = null;
        $originLat = $lat;
        $point = $this->latLngToMeters($lat, $lng, $originLat);

        foreach ($tramos as $tramo) {
            $points = $this->pointsFromTramo($tramo);

            if (count($points) < 2) {
                continue;
            }

            for ($i = 0; $i < count($points) - 1; $i++) {
                [$aLat, $aLng] = $points[$i];
                [$bLat, $bLng] = $points[$i + 1];

                $a = $this->latLngToMeters($aLat, $aLng, $originLat);
                $b = $this->latLngToMeters($bLat, $bLng, $originLat);
                $projection = $this->projectPointOnSegment($point, $a, $b);

                if ($projection === null) {
                    continue;
                }

                if ($best === null || $projection['distance'] < $best['distance']) {
                    $best = $projection;
                }
            }
        }

        if ($best === null || $best['distance'] > (float) config('waze.tramo_polyline_match_meters', 60)) {
            return null;
        }

        $halfMeters = max(8, (float) config('waze.generated_polyline_half_meters', 18));
        $start = [
            'x' => $best['x'] - ($best['ux'] * $halfMeters),
            'y' => $best['y'] - ($best['uy'] * $halfMeters),
        ];
        $end = [
            'x' => $best['x'] + ($best['ux'] * $halfMeters),
            'y' => $best['y'] + ($best['uy'] * $halfMeters),
        ];

        return $this->formatPolyline([
            $this->metersToLatLng($start['x'], $start['y'], $originLat),
            $this->metersToLatLng($end['x'], $end['y'], $originLat),
        ]);
    }

    protected function pointsFromTramo($tramo): array
    {
        $points = [];
        $puntosJson = $tramo->getAttribute('puntos_json');

        if (is_array($puntosJson)) {
            foreach ($puntosJson as $point) {
                if (!is_array($point)) {
                    continue;
                }

                $lat = $point['lat'] ?? ($point[0] ?? null);
                $lng = $point['lng'] ?? ($point[1] ?? null);

                if (is_numeric($lat) && is_numeric($lng)) {
                    $points[] = [(float) $lat, (float) $lng];
                }
            }
        }

        if (count($points) < 2 && is_numeric($tramo->lat_inicio) && is_numeric($tramo->lng_inicio) && is_numeric($tramo->lat_fin) && is_numeric($tramo->lng_fin)) {
            $points = [
                [(float) $tramo->lat_inicio, (float) $tramo->lng_inicio],
                [(float) $tramo->lat_fin, (float) $tramo->lng_fin],
            ];
        }

        return $points;
    }

    protected function tramosGeometryColumns(): array
    {
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        $columns = [];

        foreach (['activo', 'puntos_json', 'lat_inicio', 'lng_inicio', 'lat_fin', 'lng_fin'] as $column) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('tramos', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    protected function projectPointOnSegment(array $point, array $a, array $b): ?array
    {
        $dx = $b['x'] - $a['x'];
        $dy = $b['y'] - $a['y'];
        $lengthSquared = ($dx * $dx) + ($dy * $dy);

        if ($lengthSquared <= 0.000001) {
            return null;
        }

        $t = (($point['x'] - $a['x']) * $dx + ($point['y'] - $a['y']) * $dy) / $lengthSquared;
        $t = max(0, min(1, $t));

        $x = $a['x'] + ($t * $dx);
        $y = $a['y'] + ($t * $dy);
        $distance = sqrt((($point['x'] - $x) ** 2) + (($point['y'] - $y) ** 2));
        $length = sqrt($lengthSquared);

        return [
            'x' => $x,
            'y' => $y,
            'ux' => $dx / $length,
            'uy' => $dy / $length,
            'distance' => $distance,
        ];
    }

    protected function bearingFromStreet(string $street): float
    {
        if (preg_match('/\b(NORTE|SUR|TORREON NUEVO|VENTURA PUENTE|ALDAMA|MORELOS|HIDALGO|ALLENDE|JUAREZ|ABASOLO|GALEANA|MATAMOROS|GUERRERO|NICOLAS BRAVO)\b/u', $street) === 1) {
            return 0.0;
        }

        return 90.0;
    }

    protected function offsetCoordinate(float $lat, float $lng, float $bearingDegrees, float $meters): array
    {
        $radius = 6378137.0;
        $bearing = deg2rad($bearingDegrees);
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);
        $angularDistance = $meters / $radius;

        $newLat = asin(
            (sin($latRad) * cos($angularDistance)) +
            (cos($latRad) * sin($angularDistance) * cos($bearing))
        );
        $newLng = $lngRad + atan2(
            sin($bearing) * sin($angularDistance) * cos($latRad),
            cos($angularDistance) - (sin($latRad) * sin($newLat))
        );

        return [rad2deg($newLat), rad2deg($newLng)];
    }

    protected function latLngToMeters(float $lat, float $lng, float $originLat): array
    {
        return [
            'x' => $lng * 111320.0 * cos(deg2rad($originLat)),
            'y' => $lat * 110540.0,
        ];
    }

    protected function metersToLatLng(float $x, float $y, float $originLat): array
    {
        return [
            $y / 110540.0,
            $x / (111320.0 * cos(deg2rad($originLat))),
        ];
    }

    protected function formatPolyline(array $points): string
    {
        $chunks = [];

        foreach ($points as [$lat, $lng]) {
            $chunks[] = $this->formatCoord($lat) . ' ' . $this->formatCoord($lng);
        }

        return implode(' ', $chunks);
    }

    protected function normalizePolyline(string $polyline): ?string
    {
        preg_match_all('/-?\d+(?:\.\d+)?/', $polyline, $matches);

        $numbers = $matches[0] ?? [];

        if (count($numbers) < 4 || count($numbers) % 2 !== 0) {
            return null;
        }

        $points = [];

        for ($i = 0; $i < count($numbers); $i += 2) {
            $lat = (float) $numbers[$i];
            $lng = (float) $numbers[$i + 1];

            if (!$this->isValidCoordinatePair($lat, $lng)) {
                return null;
            }

            $points[] = [$lat, $lng];
        }

        if ($this->allPointsAreSame($points)) {
            return null;
        }

        return $this->formatPolyline($points);
    }

    protected function isValidCoordinatePair(float $lat, float $lng): bool
    {
        return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
    }

    protected function allPointsAreSame(array $points): bool
    {
        if (count($points) < 2) {
            return true;
        }

        [$firstLat, $firstLng] = $points[0];

        foreach ($points as [$lat, $lng]) {
            if (abs($lat - $firstLat) > 0.0000001 || abs($lng - $firstLng) > 0.0000001) {
                return false;
            }
        }

        return true;
    }

    protected function formatCoord(float $value): string
    {
        return number_format($value, 7, '.', '');
    }

    protected function buildStreet($hecho, ?string $wazeStreet = null): ?string
    {
        if ($wazeStreet !== null && trim($wazeStreet) !== '') {
            return $this->cleanText($wazeStreet);
        }

        if (!empty($hecho->calle)) {
            $street = $this->cleanStreetName($hecho->calle);

            return $street !== 'SIN CALLE' ? $street : null;
        }

        return null;
    }

    protected function buildDescription($hecho, string $type): string
    {
        $partes = [];

        if ($type === 'ROAD_CLOSED') {
            $partes[] = 'Cierre por hecho vial';
        } elseif (!empty($hecho->tipo_hecho)) {
            $partes[] = $this->cleanText($hecho->tipo_hecho);
        } else {
            $partes[] = 'Hecho vial';
        }

        if (!empty($hecho->folio_c5i)) {
            $partes[] = 'Folio ' . trim((string) $hecho->folio_c5i);
        } elseif (!empty($hecho->id)) {
            $partes[] = 'ID ' . $hecho->id;
        }

        return $this->limitText(implode(' | ', $partes), 40);
    }

    protected function cleanText($value, string $default = ''): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return $default;
        }

        return preg_replace('/\s+/', ' ', $text);
    }

    protected function cleanStreetName($value): string
    {
        $street = $this->cleanText($value);

        if ($street === '') {
            return 'SIN CALLE';
        }

        $street = preg_replace('/,\s*(ENTRE|COL\.?|COLONIA)\b.*$/iu', '', $street) ?: $street;
        $street = preg_replace('/\s+/', ' ', trim($street)) ?: '';

        return $street !== '' ? $street : 'SIN CALLE';
    }

    protected function limitText(string $text, int $limit): string
    {
        $text = $this->cleanText($text);

        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $limit - 3), 'UTF-8')) . '...';
    }
}
