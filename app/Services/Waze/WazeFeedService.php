<?php

namespace App\Services\Waze;

use App\Models\Hechos;
use Carbon\Carbon;

class WazeFeedService
{
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

        $type = $this->resolveFeedType($hecho);

        $polyline = $this->buildPolyline($lat, $lng, $hecho, $type);

        if ($polyline === null) {
            return null;
        }

        $payload = [
            'id' => 'hecho_' . $hecho->id,
            'type' => $type,
            'polyline' => $polyline,
            'direction' => $this->resolveDirection($hecho, $type),
            'street' => $this->buildStreet($hecho),
            'starttime' => $startTime->format('c'),
            'creationtime' => $this->resolveCreationTime($hecho, $startTime)->format('c'),
            'updatetime' => $this->resolveUpdateTime($hecho, $startTime)->format('c'),
            'description' => $this->buildDescription($hecho, $type),
        ];

        $subtype = $this->resolveSubtype($hecho, $type);

        if ($subtype !== null) {
            $payload['subtype'] = $subtype;
        }

        if ($type === 'ROAD_CLOSED') {
            $payload['endtime'] = $this->resolveEndTime($hecho, $startTime)->format('c');
        }

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
        return 'ACCIDENT';
    }

    protected function resolveSubtype($hecho, string $type): ?string
    {
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

    protected function resolveEndTime($hecho, Carbon $startTime): Carbon
    {
        $minutes = (int) config('waze.default_closure_minutes', 30);

        if ($minutes < 5) {
            $minutes = 30;
        }

        return $startTime->copy()->addMinutes($minutes);
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
        $street = mb_strtoupper(trim((string) ($hecho->calle ?? '')), 'UTF-8');
        $entre = mb_strtoupper(trim((string) ($hecho->entre_calles ?? '')), 'UTF-8');

        if (
            str_contains($street, ' Y ') ||
            str_contains($street, ' ESQ') ||
            str_contains($street, ' ESQUINA') ||
            str_contains($street, '&') ||
            str_contains($street, '#')
        ) {
            return $this->buildPointPolyline($lat, $lng);
        }

        if (
            str_contains($entre, 'FRENTE') ||
            str_contains($entre, 'A LA ALTURA') ||
            str_contains($entre, 'A UN COSTADO')
        ) {
            return $this->buildPointPolyline($lat, $lng);
        }

        $isMajorRoad =
            str_contains($street, 'PERIFERICO') ||
            str_contains($street, 'LIBRAMIENTO') ||
            str_contains($street, 'CARRETERA') ||
            str_contains($street, 'AUTOPISTA') ||
            str_contains($street, 'BLVD') ||
            str_contains($street, 'BULEVAR') ||
            str_contains($street, 'CALZADA') ||
            str_contains($street, 'AVENIDA') ||
            str_contains($street, 'AV.') ||
            preg_match('/\bAV\b/u', $street);

        $delta = $type === 'ROAD_CLOSED'
            ? ($isMajorRoad ? 0.00045 : 0.00028)
            : ($isMajorRoad ? 0.00030 : 0.00018);

        if ($this->looksEastWest($street)) {
            $points = [
                [$lat, $lng - $delta],
                [$lat, $lng],
                [$lat, $lng + $delta],
            ];
        } elseif ($this->looksNorthSouth($street)) {
            $points = [
                [$lat - $delta, $lng],
                [$lat, $lng],
                [$lat + $delta, $lng],
            ];
        } else {
            if ($isMajorRoad) {
                $points = [
                    [$lat, $lng - $delta],
                    [$lat, $lng],
                    [$lat, $lng + $delta],
                ];
            } else {
                return $this->buildPointPolyline($lat, $lng);
            }
        }

        return $this->formatPolyline($points);
    }

    protected function buildPointPolyline(float $lat, float $lng): string
    {
        return $this->formatPolyline([
            [$lat, $lng],
            [$lat, $lng],
        ]);
    }

    protected function looksEastWest(string $street): bool
    {
        return preg_match('/\b(PERIFERICO|LIBRAMIENTO|CAMELINAS|MADERO|VENTURA PUENTE|ACUEDUCTO|BULEVAR|BLVD|CALZADA|AVENIDA|AV\.?|CARRETERA|ENRIQUE RAMIREZ|ENRIQUE RAMÍREZ)\b/u', $street) === 1;
    }

    protected function looksNorthSouth(string $street): bool
    {
        return preg_match('/\b(HIDALGO|MORELOS|ALLENDE|JUAREZ|ABASOLO|ALDAMA|GALEANA|MATAMOROS|GUERRERO|NICOLAS BRAVO)\b/u', $street) === 1;
    }

    protected function formatPolyline(array $points): string
    {
        $chunks = [];

        foreach ($points as [$lat, $lng]) {
            $chunks[] = $this->formatCoord($lat) . ' ' . $this->formatCoord($lng);
        }

        return implode(' ', $chunks);
    }

    protected function formatCoord(float $value): string
    {
        return number_format($value, 7, '.', '');
    }

    protected function buildStreet($hecho): string
    {
        $partes = [];

        if (!empty($hecho->calle)) {
            $partes[] = $this->cleanText($hecho->calle);
        }

        if (!empty($hecho->entre_calles)) {
            $partes[] = 'ENTRE ' . $this->cleanText($hecho->entre_calles);
        }

        if (!empty($hecho->colonia)) {
            $partes[] = 'COL. ' . $this->cleanText($hecho->colonia);
        }

        $texto = trim(implode(', ', $partes));

        return $texto !== '' ? $texto : 'SIN CALLE';
    }

    protected function buildDescription($hecho, string $type): string
    {
        $partes = [];

        if (!empty($hecho->tipo_hecho)) {
            $partes[] = $this->cleanText($hecho->tipo_hecho);
        }

        if (!empty($hecho->folio_c5i)) {
            $partes[] = 'Folio ' . trim((string) $hecho->folio_c5i);
        } elseif (!empty($hecho->id)) {
            $partes[] = 'ID ' . $hecho->id;
        }

        return $this->limitText(implode(' | ', $partes), 80);
    }

    protected function cleanText($value, string $default = ''): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return $default;
        }

        return preg_replace('/\s+/', ' ', $text);
    }

    protected function limitText(string $text, int $limit): string
    {
        $text = $this->cleanText($text);

        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, max(1, $limit - 1), 'UTF-8')) . '…';
    }
}
