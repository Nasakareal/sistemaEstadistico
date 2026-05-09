<?php

namespace App\Services\Waze;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WazeReverseGeocodingService
{
    public function nearestStreet(float $lat, float $lng): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $key = sprintf(
            'waze_reverse_geocoding:%s:%s',
            number_format($lat, 5, '.', ''),
            number_format($lng, 5, '.', '')
        );

        $result = Cache::remember($key, $this->cacheSeconds(), function () use ($lat, $lng) {
            return $this->fetchNearestStreet($lat, $lng) ?: ['street' => null, 'distance' => null];
        });

        return !empty($result['street']) ? $result : null;
    }

    private function fetchNearestStreet(float $lat, float $lng): ?array
    {
        try {
            $response = Http::acceptJson()
                ->timeout($this->timeoutSeconds())
                ->get($this->endpoint(), [
                    'lat' => $lat,
                    'lon' => $lng,
                    'token' => $this->token(),
                ]);

            if (!$response->successful()) {
                Log::warning('Waze reverse geocoding failed', [
                    'status' => $response->status(),
                    'lat' => $lat,
                    'lng' => $lng,
                ]);

                return null;
            }

            $items = $response->json('result');

            if (!is_array($items)) {
                return null;
            }

            foreach (array_slice($items, 0, 5) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $distance = isset($item['distance']) && is_numeric($item['distance'])
                    ? (float) $item['distance']
                    : null;

                if ($distance !== null && $distance > $this->maxDistanceMeters()) {
                    continue;
                }

                $streetNames = $item['streetNames'] ?? [];

                if (!is_array($streetNames)) {
                    continue;
                }

                foreach ($streetNames as $streetName) {
                    $street = $this->cleanStreetName($streetName);

                    if ($street !== '') {
                        return [
                            'street' => $street,
                            'distance' => $distance,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Waze reverse geocoding exception', [
                'lat' => $lat,
                'lng' => $lng,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function isEnabled(): bool
    {
        return (bool) config('waze.reverse_geocoding_enabled', true)
            && $this->token() !== '';
    }

    private function endpoint(): string
    {
        return (string) config(
            'waze.reverse_geocoding_endpoint',
            'https://www.waze.com/row-partnerhub-api/waze-map/streetsInfo'
        );
    }

    private function token(): string
    {
        return trim((string) config('waze.reverse_geocoding_token', ''));
    }

    private function timeoutSeconds(): float
    {
        return max(0.5, (float) config('waze.reverse_geocoding_timeout', 2));
    }

    private function cacheSeconds(): int
    {
        return max(60, (int) config('waze.reverse_geocoding_cache_seconds', 604800));
    }

    private function maxDistanceMeters(): float
    {
        return max(1, (float) config('waze.reverse_geocoding_max_distance_meters', 50));
    }

    private function cleanStreetName($value): string
    {
        $street = trim((string) $value);

        if ($street === '') {
            return '';
        }

        return preg_replace('/\s+/', ' ', $street) ?: '';
    }
}
