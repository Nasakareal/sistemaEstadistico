<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\DB;

class NearestUnit
{
    public static function recommendForCoords(float $lat, float $lng, int $top = 3): array
    {
        $sub = DB::table('user_locations')
            ->select('user_id', DB::raw('MAX(captured_at) as max_captured_at'))
            ->groupBy('user_id');

        $rows = DB::table('user_locations as ul')
            ->joinSub($sub, 't', function ($join) {
                $join->on('t.user_id', '=', 'ul.user_id');
                $join->on('t.max_captured_at', '=', 'ul.captured_at');
            })
            ->join('users as u', 'u.id', '=', 'ul.user_id')
            ->leftJoin('patrullas as p', 'p.id', '=', 'u.patrulla_id')
            ->where('u.compartir_ubicacion', '=', 1)
            ->whereNotNull('u.patrulla_id')
            ->whereNotNull('ul.lat')
            ->whereNotNull('ul.lng')
            ->select([
                'p.numero_economico',
                'ul.lat',
                'ul.lng',
                'ul.captured_at',
                'ul.accuracy',
            ])
            ->get();

        $items = [];
        foreach ($rows as $r) {
            if (!is_numeric($r->lat) || !is_numeric($r->lng)) continue;

            $km = self::haversineKm($lat, $lng, (float)$r->lat, (float)$r->lng);

            $items[] = [
                'numero_economico' => $r->numero_economico ? (string)$r->numero_economico : 'SIN PATRULLA',
                'distance_km' => $km,
                'captured_at' => (string)$r->captured_at,
                'accuracy_m' => $r->accuracy !== null ? (float)$r->accuracy : null,
            ];
        }

        usort($items, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
        $items = array_slice($items, 0, max(1, $top));

        return ['ok' => true, 'items' => $items];
    }

    public static function recommendationText(array $result): string
    {
        if (!($result['ok'] ?? false)) {
            return "RECOMENDACIÓN: NO DISPONIBLE.";
        }

        $items = $result['items'] ?? [];
        if (count($items) === 0) {
            return "RECOMENDACIÓN: NO HAY UNIDADES CON UBICACIÓN COMPARTIDA.";
        }

        $best = $items[0];
        $bestKm = number_format((float)$best['distance_km'], 2, '.', '');

        // ✅ Nunca “va”, solo “se recomienda / se sugiere”
        $line = "RECOMENDACIÓN: SE SUGIERE LA UNIDAD MÁS CERCANA {$best['numero_economico']} (DIST. APROX. {$bestKm} KM).";

        if (count($items) > 1) {
            $alts = [];
            foreach ($items as $i => $it) {
                $km = number_format((float)$it['distance_km'], 2, '.', '');
                $alts[] = ($i + 1) . ") {$it['numero_economico']} ({$km} km)";
            }
            $line .= " OPCIONES: " . implode(' | ', $alts) . ".";
        }

        return $line;
    }

    private static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $R * $c;
    }
}
