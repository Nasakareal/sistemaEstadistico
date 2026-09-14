<?php

namespace App\Services\Waze;

use App\Models\WazeAlert;
use Carbon\Carbon;

class WazeAlertDeduplicationService
{
    public function hasEquivalent(
        ?string $type,
        ?string $subtype,
        float $lat,
        float $lng,
        Carbon $publishedAt
    ): bool {
        $category = $this->category($type, $subtype);
        if ($category === null) {
            return false;
        }

        $radiusKm = max(0.1, (float) config('services.waze.dedup_radius_km', 2));
        $windowMinutes = max(1, (int) config('services.waze.dedup_window_minutes', 120));
        $latDelta = $radiusKm / 110.574;
        $lngDivisor = 111.320 * max(0.01, abs(cos(deg2rad($lat))));
        $lngDelta = $radiusKm / $lngDivisor;

        return WazeAlert::query()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->whereBetween('published_at', [
                $publishedAt->copy()->subMinutes($windowMinutes),
                $publishedAt->copy()->addMinutes($windowMinutes),
            ])
            ->whereBetween('lat', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('lng', [$lng - $lngDelta, $lng + $lngDelta])
            ->get(['type', 'subtype', 'lat', 'lng'])
            ->contains(function (WazeAlert $alert) use ($category, $lat, $lng, $radiusKm) {
                return $this->category($alert->type, $alert->subtype) === $category
                    && $this->distanceKm($lat, $lng, (float) $alert->lat, (float) $alert->lng) <= $radiusKm;
            });
    }

    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return 6371.0088 * 2 * asin(min(1, sqrt($a)));
    }

    private function category(?string $type, ?string $subtype): ?string
    {
        $text = strtoupper(trim((string) $type) . ' ' . trim((string) $subtype));

        if (strpos($text, 'ACCIDENT') !== false || strpos($text, 'CRASH') !== false) {
            return 'accident';
        }

        if (strpos($text, 'ROAD_CLOSED') !== false
            || strpos($text, 'CLOSED') !== false
            || strpos($text, 'BLOCK') !== false
            || strpos($text, 'CLOSURE') !== false) {
            return 'road_closed';
        }

        return null;
    }
}
