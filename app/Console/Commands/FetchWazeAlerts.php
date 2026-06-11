<?php

namespace App\Console\Commands;

use App\Helpers\StreetNormalizer;
use App\Models\DeviceToken;
use App\Models\WazeAlert;
use App\Services\PushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

                if (WazeAlert::where('uuid', $uuid)->exists()) {
                    continue;
                }

                $type = $item['type'] ?? null;
                $subtype = $item['subtype'] ?? null;

                if (!$this->isRelevantAlert($type, $subtype)) {
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

        if ($type === 'ACCIDENT') {
            return true;
        }

        $hay = $type . ' ' . $subtype;

        if (strpos($hay, 'ACCIDENT') !== false) {
            return true;
        }

        if (strpos($hay, 'CRASH') !== false) {
            return true;
        }

        return false;
    }

    private function isRoadClosed($type, $subtype): bool
    {
        $type = strtoupper((string) $type);
        $subtype = strtoupper((string) $subtype);

        if ($type === 'ROAD_CLOSED') {
            return true;
        }

        $hay = $type . ' ' . $subtype;

        if (strpos($hay, 'ROAD_CLOSED') !== false) {
            return true;
        }

        if (strpos($hay, 'CLOSED') !== false) {
            return true;
        }

        if (strpos($hay, 'BLOCK') !== false) {
            return true;
        }

        if (strpos($hay, 'CLOSURE') !== false) {
            return true;
        }

        return false;
    }

    private function isInsideMorelia(WazeAlert $wazeAlert): bool
    {
        $city = mb_strtoupper(trim((string) $wazeAlert->city));
        return $city === 'MORELIA';
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

    private function getTokensByUnidadId(int $unidadId, array $excludeUserIds = []): array
    {
        $query = DeviceToken::query()
            ->join('users', 'users.id', '=', 'device_tokens.user_id')
            ->whereNotNull('device_tokens.token')
            ->where('device_tokens.token', '!=', '')
            ->where('users.unidad_id', $unidadId);

        $this->excludeVialidadesUrbanasNoWazeUsers($query);

        return $query->when(!empty($excludeUserIds), function ($q) use ($excludeUserIds) {
                $q->whereNotIn('users.id', $excludeUserIds);
            })
            ->pluck('device_tokens.token')
            ->unique()
            ->values()
            ->toArray();
    }

    private function getGeneralTokensExceptUnidad(array $excludeUnidadIds = [], array $excludeUserIds = []): array
    {
        $query = DeviceToken::query()
            ->join('users', 'users.id', '=', 'device_tokens.user_id')
            ->whereNotNull('device_tokens.token')
            ->where('device_tokens.token', '!=', '');

        $this->excludeVialidadesUrbanasNoWazeUsers($query);

        return $query->when(!empty($excludeUnidadIds), function ($q) use ($excludeUnidadIds) {
                $q->where(function ($sub) use ($excludeUnidadIds) {
                    $sub->whereNull('users.unidad_id')
                        ->orWhereNotIn('users.unidad_id', $excludeUnidadIds);
                });
            })
            ->when(!empty($excludeUserIds), function ($q) use ($excludeUserIds) {
                $q->whereNotIn('users.id', $excludeUserIds);
            })
            ->pluck('device_tokens.token')
            ->unique()
            ->values()
            ->toArray();
    }

    private function getNearbyTokens(WazeAlert $wazeAlert): array
    {
        if (!is_numeric($wazeAlert->lat) || !is_numeric($wazeAlert->lng)) {
            return [];
        }

        $lat = (float) $wazeAlert->lat;
        $lng = (float) $wazeAlert->lng;
        $radiusKm = $this->wazeNotifyRadiusKm();
        $maxAgeMinutes = $this->wazeLocationMaxAgeMinutes();

        $distanceSql = '(6371.0088 * 2 * ASIN(LEAST(1, SQRT('
            . 'POWER(SIN(RADIANS(user_locations.lat - ?) / 2), 2) + '
            . 'COS(RADIANS(?)) * COS(RADIANS(user_locations.lat)) * '
            . 'POWER(SIN(RADIANS(user_locations.lng - ?) / 2), 2)'
            . '))))';

        $query = DeviceToken::query()
            ->join('users', 'users.id', '=', 'device_tokens.user_id')
            ->join('user_locations', 'user_locations.user_id', '=', 'users.id')
            ->whereNotNull('device_tokens.token')
            ->where('device_tokens.token', '!=', '');

        $this->excludeVialidadesUrbanasNoWazeUsers($query);

        return $query->where('users.compartir_ubicacion', 1)
            ->whereNotNull('user_locations.lat')
            ->whereNotNull('user_locations.lng')
            ->where('user_locations.captured_at', '>=', Carbon::now('America/Mexico_City')->subMinutes($maxAgeMinutes))
            ->select('device_tokens.token')
            ->selectRaw($distanceSql . ' AS distance_km', [$lat, $lat, $lng])
            ->whereRaw($distanceSql . ' <= ?', [$lat, $lat, $lng, $radiusKm])
            ->orderBy('distance_km')
            ->get()
            ->pluck('token')
            ->unique()
            ->values()
            ->toArray();
    }

    private function excludeVialidadesUrbanasNoWazeUsers($query): void
    {
        $query->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('model_has_roles as mhr')
                ->join('roles', 'roles.id', '=', 'mhr.role_id')
                ->whereColumn('mhr.model_id', 'users.id')
                ->where('users.unidad_id', 5)
                ->whereIn(DB::raw('UPPER(roles.name)'), [
                    'MOTOCICLISTA',
                    'AGENTE VIAL',
                    'FENIX',
                    'FÉNIX',
                ]);
        });
    }

    private function wazeNotifyRadiusKm(): float
    {
        return max(1, (float) config('services.waze.notify_radius_km', 75));
    }

    private function wazeLocationMaxAgeMinutes(): int
    {
        return max(1, (int) config('services.waze.notify_location_max_age_minutes', 720));
    }

    private function notifyRelevantTokens(WazeAlert $wazeAlert): bool
    {
        $lat = $wazeAlert->lat;
        $lng = $wazeAlert->lng;

        $mapsUrl = '';
        if ($lat !== null && $lng !== null) {
            $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng;
        }

        $basePlace = $wazeAlert->street ?: ($wazeAlert->city ?: 'zona sin calle');

        $isAccident = $this->isAccident($wazeAlert->type, $wazeAlert->subtype);
        $isRoadClosed = $this->isRoadClosed($wazeAlert->type, $wazeAlert->subtype);

        if ($isAccident) {
            $title = 'Waze: Choque reportado';
            $body  = 'Choque en ' . $basePlace;
            $payloadType = 'WAZE_ACCIDENT';
        } elseif ($isRoadClosed) {
            $title = 'Waze: Cierre reportado';
            $body  = 'Cierre en ' . $basePlace;
            $payloadType = 'WAZE_ROAD_CLOSED';
        } else {
            return false;
        }

        $payload = [
            'type'      => $payloadType,
            'waze_uuid' => (string) $wazeAlert->uuid,
            'lat'       => $lat !== null ? (string) $lat : '',
            'lng'       => $lng !== null ? (string) $lng : '',
            'maps_url'  => $mapsUrl,
        ];

        $tokensAdmin = $this->getTokensByUserId(1);

        $tokensGeneral = $this->getGeneralTokensExceptUnidad([1, 4, 5], [1]);

        $tokensCarreteras = $this->getTokensByUnidadId(4, [1]);

        $tokensSiniestros = [];
        if ($isAccident && $this->isInsideMorelia($wazeAlert)) {
            $tokensSiniestros = $this->getTokensByUnidadId(1, [1]);
        }

        $tokensVialidadesUrbanas = [];
        if ($this->isInsideMorelia($wazeAlert)) {
            $tokensVialidadesUrbanas = $this->getTokensByUnidadId(5, [1]);
        }

        $tokensNearby = $this->getNearbyTokens($wazeAlert);

        $tokens = collect(array_merge(
            $tokensAdmin,
            $tokensGeneral,
            $tokensCarreteras,
            $tokensSiniestros,
            $tokensVialidadesUrbanas,
            $tokensNearby
        ))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        Log::info('Waze notify tokens', [
            'waze_uuid' => $wazeAlert->uuid,
            'type' => $wazeAlert->type,
            'subtype' => $wazeAlert->subtype,
            'city' => $wazeAlert->city,
            'street' => $wazeAlert->street,
            'lat' => $lat,
            'lng' => $lng,
            'radius_km' => $this->wazeNotifyRadiusKm(),
            'location_max_age_minutes' => $this->wazeLocationMaxAgeMinutes(),
            'admin_tokens' => count($tokensAdmin),
            'general_tokens' => count($tokensGeneral),
            'carreteras_tokens' => count($tokensCarreteras),
            'siniestros_tokens' => count($tokensSiniestros),
            'vialidades_urbanas_tokens' => count($tokensVialidadesUrbanas),
            'nearby_tokens' => count($tokensNearby),
            'tokens_total' => count($tokens),
        ]);

        if (count($tokens) === 0) {
            Log::warning('Waze notify: no device tokens found for this alert', [
                'waze_uuid' => $wazeAlert->uuid,
                'type' => $wazeAlert->type,
                'subtype' => $wazeAlert->subtype,
                'city' => $wazeAlert->city,
                'street' => $wazeAlert->street,
                'lat' => $lat,
                'lng' => $lng,
                'radius_km' => $this->wazeNotifyRadiusKm(),
                'location_max_age_minutes' => $this->wazeLocationMaxAgeMinutes(),
            ]);
            return false;
        }

        return app(PushService::class)->sendToTokens($tokens, $title, $body, $payload);
    }
}
