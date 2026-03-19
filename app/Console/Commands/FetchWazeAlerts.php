<?php

namespace App\Console\Commands;

use App\Helpers\StreetNormalizer;
use App\Models\DeviceToken;
use App\Models\WazeAlert;
use App\Services\PushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchWazeAlerts extends Command
{
    protected $signature = 'waze:fetch-alerts';
    protected $description = 'Lee el feed JSON de Waze y manda push cuando hay alertas relevantes nuevas';

    public function handle()
    {
        $feedUrl = config('services.waze.feed_url');

        if (!$feedUrl) {
            $this->error('Falta configurar services.waze.feed_url');
            return 1;
        }

        try {
            $res = Http::withOptions([
                    'decode_content' => false,
                ])
                ->timeout(20)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                    'Accept' => 'application/json,text/plain,*/*',
                ])
                ->get($feedUrl);

            if (!$res->ok()) {
                Log::warning('Waze feed no OK', ['status' => $res->status()]);
                $this->error('Waze feed no OK: ' . $res->status());
                return 1;
            }

            $body = $res->body();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                Log::error('Waze feed: JSON inválido', ['sample' => substr($body, 0, 200)]);
                $this->error('Waze feed: JSON inválido');
                return 1;
            }

            $alerts = $data['alerts'] ?? [];
            if (!is_array($alerts)) {
                $alerts = [];
            }

            $savedTotal = 0;
            $notifiedTotal = 0;

            foreach ($alerts as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $uuid = $item['uuid'] ?? null;
                if (!$uuid) {
                    continue;
                }

                $type = $item['type'] ?? null;
                $subtype = $item['subtype'] ?? null;

                Log::info('Waze feed alert recibido', [
                    'uuid' => $uuid,
                    'type' => $type,
                    'subtype' => $subtype,
                    'city' => $item['city'] ?? null,
                    'street' => $item['street'] ?? null,
                ]);

                if (WazeAlert::where('uuid', $uuid)->exists()) {
                    Log::info('Waze alert omitida por uuid existente', [
                        'uuid' => $uuid,
                        'type' => $type,
                        'subtype' => $subtype,
                    ]);
                    continue;
                }

                if (!$this->isRelevantAlert($type, $subtype)) {
                    Log::info('Waze alert omitida por no ser relevante', [
                        'uuid' => $uuid,
                        'type' => $type,
                        'subtype' => $subtype,
                    ]);
                    continue;
                }

                $lat = data_get($item, 'location.y');
                $lng = data_get($item, 'location.x');

                $pubMillis = $item['pubMillis'] ?? null;
                $publishedAt = null;

                if (is_numeric($pubMillis)) {
                    $publishedAt = Carbon::createFromTimestampMs((int) $pubMillis, 'UTC')
                        ->setTimezone('America/Mexico_City');
                }

                $street = $item['street'] ?? null;

                $wazeAlert = WazeAlert::create([
                    'uuid' => $uuid,
                    'waze_id' => $item['id'] ?? null,
                    'type' => $type,
                    'subtype' => $subtype,
                    'country' => $item['country'] ?? null,
                    'city' => $item['city'] ?? null,
                    'street' => $street,
                    'street_norm' => StreetNormalizer::normalize($street),
                    'lat' => is_numeric($lat) ? (float) $lat : null,
                    'lng' => is_numeric($lng) ? (float) $lng : null,
                    'pub_millis' => is_numeric($pubMillis) ? (int) $pubMillis : null,
                    'published_at' => $publishedAt,
                    'raw' => $item,
                    'notified' => false,
                ]);

                $savedTotal++;

                $sent = $this->notifyRelevantTokens($wazeAlert);
                $wazeAlert->update(['notified' => $sent]);

                if ($sent) {
                    $notifiedTotal++;
                }
            }

            $this->info("OK. Alertas nuevas guardadas: {$savedTotal}. Alertas notificadas: {$notifiedTotal}");
            return 0;
        } catch (\Throwable $e) {
            Log::error('Error leyendo Waze feed', [
                'error' => $e->getMessage(),
                'trace' => substr((string) $e->getTraceAsString(), 0, 1200),
            ]);
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function isRelevantAlert($type, $subtype): bool
    {
        return $this->isAccident($type, $subtype) || $this->isRoadClosed($type, $subtype);
    }

    private function isAccident($type, $subtype): bool
    {
        $type = strtoupper((string) $type);
        $subtype = strtoupper((string) $subtype);
        $hay = $type . ' ' . $subtype;

        return $type === 'ACCIDENT'
            || strpos($hay, 'ACCIDENT') !== false
            || strpos($hay, 'CRASH') !== false;
    }

    private function isRoadClosed($type, $subtype): bool
    {
        $type = strtoupper((string) $type);
        $subtype = strtoupper((string) $subtype);
        $hay = $type . ' ' . $subtype;

        return $type === 'ROAD_CLOSED'
            || strpos($hay, 'ROAD_CLOSED') !== false
            || strpos($hay, 'ROAD CLOS') !== false
            || strpos($hay, 'CLOSED') !== false
            || strpos($hay, 'BLOCK') !== false
            || strpos($hay, 'CLOSURE') !== false;
    }

    private function isInsideMoreliaMunicipality(WazeAlert $wazeAlert): bool
    {
        $lat = $wazeAlert->lat;
        $lng = $wazeAlert->lng;

        if ($lat === null || $lng === null) {
            return false;
        }

        $polygon = config('services.waze.morelia_polygon', []);

        if (is_array($polygon) && count($polygon) >= 3) {
            return $this->pointInPolygon($lat, $lng, $polygon);
        }

        $city = mb_strtoupper(trim((string) $wazeAlert->city));

        return $city === 'MORELIA';
    }

    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $points = [];

        foreach ($polygon as $point) {
            if (is_array($point) && array_key_exists('lat', $point) && array_key_exists('lng', $point)) {
                $points[] = [
                    'lat' => (float) $point['lat'],
                    'lng' => (float) $point['lng'],
                ];
                continue;
            }

            if (is_array($point) && count($point) >= 2) {
                $values = array_values($point);
                $points[] = [
                    'lat' => (float) $values[0],
                    'lng' => (float) $values[1],
                ];
            }
        }

        $count = count($points);

        if ($count < 3) {
            return false;
        }

        $inside = false;
        $j = $count - 1;

        for ($i = 0; $i < $count; $i++) {
            $xi = $points[$i]['lng'];
            $yi = $points[$i]['lat'];
            $xj = $points[$j]['lng'];
            $yj = $points[$j]['lat'];

            $intersects = (($yi > $lat) !== ($yj > $lat))
                && ($lng < (($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 0.0000000001) + $xi));

            if ($intersects) {
                $inside = !$inside;
            }

            $j = $i;
        }

        return $inside;
    }

    private function reverseGeocode(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        try {
            $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2'
                . '&lat=' . urlencode((string) $lat)
                . '&lon=' . urlencode((string) $lng)
                . '&zoom=18&addressdetails=1';

            $res = Http::timeout(8)->withHeaders([
                'User-Agent' => 'seguridadvial-mich.com/1.0 (contacto@seguridadvial-mich.com)',
                'Accept' => 'application/json',
            ])->get($url);

            if (!$res->ok()) {
                return null;
            }

            $j = $res->json();

            if (!is_array($j)) {
                return null;
            }

            $display = $j['display_name'] ?? null;

            if (!is_string($display) || trim($display) === '') {
                return null;
            }

            $display = trim($display);

            if (mb_strlen($display) > 120) {
                $display = mb_substr($display, 0, 120) . '...';
            }

            return $display;
        } catch (\Throwable $e) {
            Log::warning('reverseGeocode failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function getTokensByUserId(int $userId): array
    {
        return DeviceToken::query()
            ->where('user_id', $userId)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->pluck('token')
            ->unique()
            ->values()
            ->toArray();
    }

    private function getValidTokensQuery()
    {
        return DeviceToken::query()
            ->join('users', 'users.id', '=', 'device_tokens.user_id')
            ->whereNotNull('device_tokens.token')
            ->where('device_tokens.token', '!=', '');
    }

    private function notifyRelevantTokens(WazeAlert $wazeAlert): bool
    {
        $lat = $wazeAlert->lat;
        $lng = $wazeAlert->lng;

        $mapsUrl = '';
        if ($lat !== null && $lng !== null) {
            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng;
        }

        $nicePlace = $this->reverseGeocode($lat, $lng);
        $basePlace = $wazeAlert->street ?: ($wazeAlert->city ?: 'zona sin calle');

        $isAccident = $this->isAccident($wazeAlert->type, $wazeAlert->subtype);
        $isRoadClosed = $this->isRoadClosed($wazeAlert->type, $wazeAlert->subtype);

        if ($isAccident) {
            $title = 'Waze: Choque reportado';
            $body = 'Choque en ' . ($nicePlace ?: $basePlace);
            $payloadType = 'WAZE_ACCIDENT';
        } elseif ($isRoadClosed) {
            $title = 'Waze: Cierre reportado';
            $body = 'Cierre en ' . ($nicePlace ?: $basePlace);
            $payloadType = 'WAZE_ROAD_CLOSED';
        } else {
            return false;
        }

        $payload = [
            'type' => $payloadType,
            'waze_uuid' => (string) $wazeAlert->uuid,
            'lat' => $lat !== null ? (string) $lat : '',
            'lng' => $lng !== null ? (string) $lng : '',
            'maps_url' => $mapsUrl,
        ];

        $tokensAdminPruebas = $this->getTokensByUserId(1);

        if (count($tokensAdminPruebas) === 0) {
            Log::warning('Usuario 1 no tiene tokens válidos');
            return false;
        }

        Log::info('Waze notify enviando a admin pruebas', [
            'waze_uuid' => $wazeAlert->uuid,
            'type' => $wazeAlert->type,
            'subtype' => $wazeAlert->subtype,
            'city' => $wazeAlert->city,
            'street' => $wazeAlert->street,
            'payload_type' => $payloadType,
            'tokens_admin_pruebas' => count($tokensAdminPruebas),
        ]);

        return app(PushService::class)->sendToTokens($tokensAdminPruebas, $title, $body, $payload);
    }
}
