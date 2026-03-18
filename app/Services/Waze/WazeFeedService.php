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
        $street = mb_strtoupper(trim((string) ($hecho->calle ?? '')), 'UTF-8');

        if (str_contains($street, 'PERIFERICO')) {
            return null;
        }

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
            'confidence' => 0.9,
            'reliability' => $this->resolveReliability($hecho),
            'location' => [
                'x' => $lng,
                'y' => $lat,
            ],
            'polyline' => $polyline,
            'direction' => $this->resolveDirection($hecho, $type),
            'street' => $this->buildStreet($hecho),
            'city' => $this->resolveCity($hecho),
            'country' => 'MX',
            'starttime' => $startTime->format('c'),
            'description' => $this->buildDescription($hecho, $type),
        ];

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
            str_contains($tipo, 'ATROPELLAMIENTO');
    }

    protected function resolveFeedType($hecho): string
    {
        return $this->isSpecialLargeRoad($hecho) ? 'ACCIDENT' : 'ROAD_CLOSED';
    }

    protected function isSpecialLargeRoad($hecho): bool
    {
        $street = mb_strtoupper(trim((string) ($hecho->calle ?? '')), 'UTF-8');

        if ($street === '') {
            return false;
        }

        return
            str_contains($street, 'MADERO') ||
            str_contains($street, 'LIBRAMIENTO') ||
            str_contains($street, 'ENRIQUE RAMIREZ') ||
            str_contains($street, 'ENRIQUE RAMÍREZ') ||
            str_contains($street, 'PERIFERICO');
    }

    protected function resolveStartTime($hecho): ?Carbon
    {
        try {
            if (!empty($hecho->fecha) && !empty($hecho->hora)) {
                return Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $hecho->fecha . ' ' . $hecho->hora,
                    'America/Mexico_City'
                );
            }

            if (!empty($hecho->created_at)) {
                return Carbon::parse($hecho->created_at)->setTimezone('America/Mexico_City');
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected function resolveEndTime($hecho, Carbon $startTime): Carbon
    {
        $minutes = (int) config('waze.default_closure_minutes', 30);

        if ($minutes < 5) {
            $minutes = 30;
        }

        return $startTime->copy()->addMinutes($minutes);
    }

    protected function resolveReliability($hecho): int
    {
        $fuente = mb_strtoupper(trim((string) ($hecho->fuente_ubicacion ?? '')), 'UTF-8');
        $calidad = is_numeric($hecho->calidad_geo) ? (float) $hecho->calidad_geo : null;

        if ($fuente === 'GPS_APP' && $calidad !== null) {
            if ($calidad <= 10) {
                return 9;
            }

            if ($calidad <= 25) {
                return 8;
            }

            if ($calidad <= 60) {
                return 7;
            }
        }

        if ($fuente === 'GPS_WEB') {
            return 7;
        }

        return 6;
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

        if ($street === '') {
            return null;
        }

        if (
            str_contains($street, ' Y ') ||
            str_contains($street, ' ESQ') ||
            str_contains($street, ' ESQUINA') ||
            str_contains($street, '&') ||
            str_contains($street, '#')
        ) {
            return null;
        }

        if (
            str_contains($entre, 'FRENTE') ||
            str_contains($entre, 'A LA ALTURA') ||
            str_contains($entre, 'A UN COSTADO')
        ) {
            return null;
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
                return null;
            }
        }

        return $this->formatPolyline($points);
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

        if ($type === 'ROAD_CLOSED') {
            $partes[] = 'Cierre temporal de vialidad';
        }

        if (!empty($hecho->situacion)) {
            $partes[] = 'Situación: ' . $this->cleanText($hecho->situacion);
        }

        if (!empty($hecho->folio_c5i)) {
            $partes[] = 'Folio: ' . trim((string) $hecho->folio_c5i);
        } elseif (!empty($hecho->id)) {
            $partes[] = 'ID interno: ' . $hecho->id;
        }

        return implode(' | ', $partes);
    }

    protected function resolveCity($hecho): string
    {
        $city = mb_strtoupper(trim((string) ($hecho->municipio ?? '')), 'UTF-8');

        if ($city === '' || $city === 'MOTELIA') {
            return 'MORELIA';
        }

        return $city;
    }

    protected function cleanText($value, string $default = ''): string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return $default;
        }

        return preg_replace('/\s+/', ' ', $text);
    }
}
