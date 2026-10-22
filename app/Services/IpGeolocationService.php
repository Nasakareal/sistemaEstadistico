<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpGeolocationService
{
    public function locate(string $ip): ?array
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['status' => 'non_public', 'accuracy' => 'not_applicable'];
        }

        if (!config('honeypot_api.geolocation.enabled')) {
            return null;
        }

        $cacheKey = 'honeypot-geo:' . hash('sha256', $ip);
        $missing = '__honeypot_geo_cache_miss__';
        $cached = Cache::get($cacheKey, $missing);

        if ($cached !== $missing) {
            return is_array($cached) ? $cached : null;
        }

        $location = $this->requestLocation($ip);
        Cache::put(
            $cacheKey,
            $location ?: false,
            $location
                ? now()->addDays((int) config('honeypot_api.geolocation.cache_days', 7))
                : now()->addHour()
        );

        return $location;
    }

    private function requestLocation(string $ip): ?array
    {
        $template = trim((string) config('honeypot_api.geolocation.url', ''));
        $token = trim((string) config('honeypot_api.geolocation.token', ''));

        if ($template === '' || strpos($template, 'https://') !== 0) {
            Log::warning('La geolocalización del honeypot requiere una URL HTTPS válida.');
            return null;
        }

        $url = str_replace('{ip}', rawurlencode($ip), $template);
        $query = $token !== '' ? ['token' => $token] : [];

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('honeypot_api.geolocation.timeout_seconds', 4))
                ->get($url, $query);

            if (!$response->successful()) {
                Log::notice('No se pudo geolocalizar una IP del honeypot.', [
                    'ip_hash' => hash('sha256', $ip),
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $this->normalize((array) $response->json());
        } catch (\Throwable $exception) {
            Log::notice('Falló la consulta de geolocalización del honeypot.', [
                'ip_hash' => hash('sha256', $ip),
                'error' => $exception->getMessage(),
            ]);
            return null;
        }
    }

    private function normalize(array $data): ?array
    {
        $latitude = null;
        $longitude = null;

        if (!empty($data['loc']) && is_string($data['loc'])) {
            [$latitude, $longitude] = array_pad(explode(',', $data['loc'], 2), 2, null);
        } elseif (isset($data['latitude'], $data['longitude'])) {
            $latitude = $data['latitude'];
            $longitude = $data['longitude'];
        } elseif (isset($data['geo']) && is_array($data['geo'])) {
            $latitude = $data['geo']['latitude'] ?? null;
            $longitude = $data['geo']['longitude'] ?? null;
        }

        $geo = isset($data['geo']) && is_array($data['geo']) ? $data['geo'] : $data;
        $latitude = is_numeric($latitude) ? (float) $latitude : null;
        $longitude = is_numeric($longitude) ? (float) $longitude : null;

        if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
            $latitude = null;
        }
        if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
            $longitude = null;
        }

        $countryCode = strtoupper(substr((string) ($geo['country_code'] ?? $data['country'] ?? ''), 0, 2));
        $countryName = $geo['country'] ?? $data['country_name'] ?? null;

        if ($latitude === null && $longitude === null && $countryCode === '') {
            return null;
        }

        return [
            'status' => 'located',
            'accuracy' => 'approximate_ip_network',
            'country_code' => $countryCode !== '' ? $countryCode : null,
            'country' => is_string($countryName) ? $countryName : null,
            'region' => $geo['region'] ?? null,
            'city' => $geo['city'] ?? $data['city'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => $geo['timezone'] ?? $data['timezone'] ?? null,
            'network' => $data['org'] ?? $data['as_name'] ?? null,
            'provider' => (string) config('honeypot_api.geolocation.provider', 'ipinfo'),
            'looked_up_at' => now()->toIso8601String(),
        ];
    }
}
