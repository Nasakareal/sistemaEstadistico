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

        if ($storedPolyline !== null) {
            return $storedPolyline;
        }

        return $this->buildPointPolyline($lat, $lng, $hecho);
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
        return $this->formatPolyline([
            [$lat, $lng],
            [$lat, $lng],
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
