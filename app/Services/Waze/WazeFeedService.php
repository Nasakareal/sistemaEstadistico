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
        $type = $this->resolveType((string) $hecho->tipo_hecho);

        if ($type === null) {
            return null;
        }

        $startTime = $this->resolveStartTime($hecho);

        if ($startTime === null) {
            return null;
        }

        return [
            'id' => 'hecho_' . $hecho->id,
            'type' => $type,
            'confidence' => 0.9,
            'reliability' => $this->resolveReliability($hecho),
            'location' => [
                'x' => (float) $hecho->lng,
                'y' => (float) $hecho->lat,
            ],
            'street' => $this->buildStreet($hecho),
            'city' => $this->cleanText($hecho->municipio, 'MORELIA'),
            'country' => 'MX',
            'starttime' => $startTime->utc()->toIso8601String(),
        ];
    }

    protected function resolveType(string $tipoHecho): ?string
    {
        $tipo = mb_strtoupper(trim($tipoHecho), 'UTF-8');

        if ($tipo === '') {
            return null;
        }

        if (str_contains($tipo, 'COLISIÓN') || str_contains($tipo, 'COLISION')) {
            return 'ACCIDENT';
        }

        if (str_contains($tipo, 'CHOQUE')) {
            return 'ACCIDENT';
        }

        if (str_contains($tipo, 'VOLCADURA')) {
            return 'ACCIDENT';
        }

        if (str_contains($tipo, 'ATROPELLAMIENTO')) {
            return 'ACCIDENT';
        }

        return null;
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
                return Carbon::parse($hecho->created_at, 'America/Mexico_City');
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
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

    protected function buildStreet($hecho): string
    {
        $partes = [];

        if (!empty($hecho->calle)) {
            $partes[] = trim((string) $hecho->calle);
        }

        if (!empty($hecho->entre_calles)) {
            $partes[] = 'ENTRE ' . trim((string) $hecho->entre_calles);
        }

        if (!empty($hecho->colonia)) {
            $partes[] = 'COL. ' . trim((string) $hecho->colonia);
        }

        $texto = trim(implode(', ', $partes));

        return $texto !== '' ? $texto : 'SIN CALLE';
    }

    protected function cleanText($value, string $default = ''): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : $default;
    }
}
