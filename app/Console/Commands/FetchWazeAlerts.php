<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\User;
use App\Models\WazeAlert;
use App\Services\PushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchWazeAlerts extends Command
{
    protected $signature = 'waze:fetch-alerts';
    protected $description = 'Lee el feed JSON de Waze y manda push a roles cuando hay choques nuevos';

    public function handle()
    {
        $feedUrl = config('services.waze.feed_url');

        if (!$feedUrl) {
            $this->error('Falta configurar services.waze.feed_url');
            return 1;
        }

        try {
            $res = Http::timeout(20)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0',
                'Accept' => 'application/json,text/plain,*/*',
                'Accept-Encoding' => 'gzip, deflate, br, zstd',
            ])
            ->get($feedUrl);


            if (!$res->ok()) {
                Log::warning('Waze feed no OK', ['status' => $res->status()]);
                $this->error('Waze feed no OK: '.$res->status());
                return 1;
            }

            $data = $res->json();
            $alerts = $data['alerts'] ?? [];

            $newAccidents = 0;

            foreach ($alerts as $item) {
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
                $publishedAt = $pubMillis ? Carbon::createFromTimestampMs((int)$pubMillis) : null;

                $wazeAlert = WazeAlert::create([
                    'uuid' => $uuid,
                    'waze_id' => $item['id'] ?? null,
                    'type' => $type,
                    'subtype' => $subtype,
                    'country' => $item['country'] ?? null,
                    'city' => $item['city'] ?? null,
                    'street' => $item['street'] ?? null,
                    'lat' => $lat,
                    'lng' => $lng,
                    'pub_millis' => $pubMillis,
                    'published_at' => $publishedAt,
                    'raw' => $item,
                    'notified' => false,
                ]);

                if ($this->isAccident($type, $subtype)) {
                    $sent = $this->notifyRoles($wazeAlert);
                    $wazeAlert->update(['notified' => $sent]);
                    $newAccidents++;
                }
            }

            $this->info("OK. Choques nuevos procesados: {$newAccidents}");
            return 0;

        } catch (\Throwable $e) {
            Log::error('Error leyendo Waze feed', ['error' => $e->getMessage()]);
            $this->error('Error: '.$e->getMessage());
            return 1;
        }
    }

    private function isAccident($type, $subtype)
    {
        $type = strtoupper((string)$type);
        $subtype = strtoupper((string)$subtype);

        if ($type === 'ACCIDENT') return true;

        $hay = $type.' '.$subtype;
        if (strpos($hay, 'ACCIDENT') !== false) return true;
        if (strpos($hay, 'CRASH') !== false) return true;

        return false;
    }

    private function notifyRoles(WazeAlert $wazeAlert): bool
    {
        $tokens = \App\Models\DeviceToken::whereNotNull('token')
            ->pluck('token')
            ->unique()
            ->values()
            ->toArray();

        if (count($tokens) === 0) {
            \Log::warning('Waze notify: no device tokens found');
            return false;
        }

        $title = 'Waze: Choque reportado';
        $body = 'Choque en '.($wazeAlert->street ?: ($wazeAlert->city ?: 'zona sin calle'));

        $data = [
            'type' => 'WAZE_ACCIDENT',
            'waze_uuid' => $wazeAlert->uuid,
            'lat' => (string)$wazeAlert->lat,
            'lng' => (string)$wazeAlert->lng,
        ];

        return app(\App\Services\PushService::class)->sendToTokens($tokens, $title, $body, $data);
    }
}
