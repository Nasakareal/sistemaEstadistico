<?php

namespace App\Console\Commands;

use App\Models\WazeAlert;
use App\Services\PushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchWazeAlerts extends Command
{
    protected $signature = 'waze:fetch-alerts';
    protected $description = 'Lee el feed JSON de Waze y manda push cuando hay choques nuevos';

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
            if (!is_array($alerts)) $alerts = [];

            $newAccidents = 0;

            foreach ($alerts as $item) {
                if (!is_array($item)) continue;

                $uuid = $item['uuid'] ?? null;
                if (!$uuid) continue;

                if (WazeAlert::where('uuid', $uuid)->exists()) {
                    continue;
                }

                $type = $item['type'] ?? null;
                $subtype = $item['subtype'] ?? null;

                $lat = $item['location']['y'] ?? null;
                $lng = $item['location']['x'] ?? null;

                $pubMillis = $item['pubMillis'] ?? null;
                $publishedAt = null;
                if (is_numeric($pubMillis)) {
                    $publishedAt = Carbon::createFromTimestampMs((int) $pubMillis);
                }

                $wazeAlert = WazeAlert::create([
                    'uuid' => $uuid,
                    'waze_id' => $item['id'] ?? null,
                    'type' => $type,
                    'subtype' => $subtype,
                    'country' => $item['country'] ?? null,
                    'city' => $item['city'] ?? null,
                    'street' => $item['street'] ?? null,
                    'lat' => is_numeric($lat) ? (float)$lat : null,
                    'lng' => is_numeric($lng) ? (float)$lng : null,
                    'pub_millis' => is_numeric($pubMillis) ? (int)$pubMillis : null,
                    'published_at' => $publishedAt,
                    'raw' => $item,
                    'notified' => false,
                ]);

                if ($this->isAccident($type, $subtype)) {
                    $sent = $this->notifyAllTokens($wazeAlert);
                    $wazeAlert->update(['notified' => $sent]);

                    if ($sent) $newAccidents++;
                }
            }

            $this->info("OK. Choques nuevos notificados: {$newAccidents}");
            return 0;

        } catch (\Throwable $e) {
            Log::error('Error leyendo Waze feed', [
                'error' => $e->getMessage(),
            ]);
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function isAccident($type, $subtype): bool
    {
        $type = strtoupper((string)$type);
        $subtype = strtoupper((string)$subtype);

        if ($type === 'ACCIDENT') return true;

        $hay = $type . ' ' . $subtype;
        if (strpos($hay, 'ACCIDENT') !== false) return true;
        if (strpos($hay, 'CRASH') !== false) return true;

        return false;
    }

    private function reverseGeocode(?float $lat, ?float $lng): ?string
    {
        if ($lat === null || $lng === null) return null;

        try {
            $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2'
                . '&lat=' . urlencode((string)$lat)
                . '&lon=' . urlencode((string)$lng)
                . '&zoom=18&addressdetails=1';

            $res = Http::timeout(8)->withHeaders([
                'User-Agent' => 'seguridadvial-mich.com/1.0 (contacto@seguridadvial-mich.com)',
                'Accept' => 'application/json',
            ])->get($url);

            if (!$res->ok()) return null;

            $j = $res->json();
            if (!is_array($j)) return null;

            $display = $j['display_name'] ?? null;
            if (!is_string($display) || trim($display) === '') return null;

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

    private function notifyAllTokens(WazeAlert $wazeAlert): bool
    {
        $tokens = \App\Models\DeviceToken::query()
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->pluck('token')
            ->unique()
            ->values()
            ->toArray();

        if (count($tokens) === 0) {
            Log::warning('Waze notify: no device tokens found');
            return false;
        }

        $lat = $wazeAlert->lat;
        $lng = $wazeAlert->lng;

        $mapsUrl = '';
        if ($lat !== null && $lng !== null) {
            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng;
        }

        $nicePlace = $this->reverseGeocode($lat, $lng);

        $basePlace = $wazeAlert->street ?: ($wazeAlert->city ?: 'zona sin calle');

        $title = 'Waze: Choque reportado';
        $body  = 'Choque en ' . ($nicePlace ?: $basePlace);

        $payload = [
            'type'      => 'WAZE_ACCIDENT',
            'waze_uuid' => (string) $wazeAlert->uuid,
            'lat'       => $lat !== null ? (string) $lat : '',
            'lng'       => $lng !== null ? (string) $lng : '',
            'maps_url'  => $mapsUrl,
        ];

        return app(PushService::class)->sendToTokens($tokens, $title, $body, $payload);
    }
}
