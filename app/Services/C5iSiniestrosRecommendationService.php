<?php

namespace App\Services;

use App\Models\Comunicacion;
use App\Models\ComunicacionDestinatario;
use App\Models\User;
use App\Models\WhatsAppWebMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class C5iSiniestrosRecommendationService
{
    private ComunicacionPushService $pushService;

    public function __construct(
        WhatsAppCloudService $whatsApp,
        WhatsAppSendGuard $sendGuard,
        ?ComunicacionPushService $pushService = null
    )
    {
        // Los dos primeros argumentos se conservan temporalmente para no romper
        // consumidores existentes mientras la salida migra de Meta a mensajería interna.
        $this->pushService = $pushService ?: app(ComunicacionPushService::class);
    }

    public function process(WhatsAppWebMessage $message): array
    {
        try {
            return $this->processSafely($message);
        } catch (Throwable $e) {
            Log::error('Error procesando recomendación C5i/Siniestros', [
                'whatsapp_web_message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            $this->persistResult($message, 'failed', null, null, [
                'reason' => 'processing_exception',
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'reason' => 'processing_exception'];
        }
    }

    public function parseIncident(string $body): ?array
    {
        $number = '(-?\d{1,3}(?:[\.,]\d+)?)';
        $pattern = '/\bLATITUD\s*:\s*' . $number . '\s*LONGITUD\s*:\s*' . $number . '\b/iu';

        if (!preg_match($pattern, $body, $matches)) {
            return null;
        }

        $lat = (float) str_replace(',', '.', $matches[1]);
        $lng = (float) str_replace(',', '.', $matches[2]);

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        $location = preg_replace($pattern, '', $body, 1) ?? $body;
        $location = preg_replace('/\s+/u', ' ', trim($location)) ?? trim($location);

        if (mb_strlen($location, 'UTF-8') > 500) {
            $location = mb_substr($location, 0, 497, 'UTF-8') . '...';
        }

        return [
            'lat' => $lat,
            'lng' => $lng,
            'location' => $location !== '' ? $location : 'Ubicación sin descripción',
        ];
    }

    public function isExcludedIncident(string $body): bool
    {
        $header = mb_substr(trim($body), 0, 160, 'UTF-8');

        return preg_match(
            '/^\s*(?:[^\p{L}\p{N}]+\s*)?(?:UBICACI[ÓO]N\s*:\s*)?(L4)(?:\s+\1)?\s+LLEGA\b/iu',
            $header
        ) === 1;
    }

    private function processSafely(WhatsAppWebMessage $message): array
    {
        if (!(bool) config('services.whatsapp.c5i_recommendation.enabled', false)) {
            return ['status' => 'disabled'];
        }

        $message->loadMissing('group');

        if (!$this->groupAllowed((string) optional($message->group)->whatsapp_id)) {
            return $this->ignored($message, 'group_not_allowed');
        }

        if (!$this->sourceAllowed((string) $message->author_whatsapp_id)) {
            return $this->ignored($message, 'source_not_allowed');
        }

        if ($this->isExcludedIncident((string) $message->body)) {
            return $this->ignored($message, 'arrival_code_not_relevant');
        }

        $incident = $this->parseIncident((string) $message->body);

        if ($incident === null) {
            return $this->ignored($message, 'coordinates_not_found');
        }

        $candidate = $this->nearestSiniestrosPatrulla($incident['lat'], $incident['lng']);

        if ($candidate === null) {
            $this->persistResult($message, 'no_candidate', $incident, null, [
                'reason' => 'no_fresh_siniestros_location',
            ]);

            return ['status' => 'no_candidate'];
        }

        $recipients = $this->recipientUserIds();
        $content = $this->internalMessageContent($message, $incident, $candidate);
        $baseMeta = [
            'channel' => 'internal_messaging',
            'recipients' => $recipients,
            'candidate' => $candidate,
            'content' => $content,
        ];

        if ((bool) config('services.whatsapp.c5i_recommendation.dry_run', true)) {
            $this->persistResult($message, 'dry_run', $incident, $candidate, $baseMeta);

            Log::info('Simulación recomendación C5i/Siniestros', [
                'whatsapp_web_message_id' => $message->id,
                'patrulla_id' => $candidate['patrulla_id'],
                'distance_km' => $candidate['distance_km'],
            ]);

            return ['status' => 'dry_run', 'candidate' => $candidate];
        }

        if (empty($recipients)) {
            $baseMeta['reason'] = 'recipients_not_configured';
            $this->persistResult($message, 'failed', $incident, $candidate, $baseMeta);

            return ['status' => 'failed', 'reason' => $baseMeta['reason']];
        }

        $delivery = $this->sendInternalMessage($recipients, $content);
        $status = $delivery['status'];
        $baseMeta = array_merge($baseMeta, $delivery);

        $this->persistResult($message, $status, $incident, $candidate, $baseMeta);

        Log::info('Resultado recomendación C5i/Siniestros', [
            'whatsapp_web_message_id' => $message->id,
            'status' => $status,
            'sent' => count($delivery['delivered_user_ids'] ?? []),
            'recipients' => count($recipients),
            'patrulla_id' => $candidate['patrulla_id'],
        ]);

        return ['status' => $status, 'candidate' => $candidate, 'delivery' => $delivery];
    }

    private function nearestSiniestrosPatrulla(float $incidentLat, float $incidentLng): ?array
    {
        $maxAgeMinutes = max(1, (int) config(
            'services.whatsapp.c5i_recommendation.location_max_age_minutes',
            10
        ));
        $maxAccuracyMeters = max(0, (int) config(
            'services.whatsapp.c5i_recommendation.max_accuracy_meters',
            200
        ));
        $unitSlug = trim((string) config(
            'services.whatsapp.c5i_recommendation.unit_slug',
            'siniestros'
        )) ?: 'siniestros';

        $query = \App\Models\UserLocation::query()
            ->join('users', 'users.id', '=', 'user_locations.user_id')
            ->join('unidades', 'unidades.id', '=', 'users.unidad_id')
            ->join('patrullas', 'patrullas.id', '=', 'users.patrulla_id')
            ->where('unidades.slug', $unitSlug)
            ->where('unidades.activa', 1)
            ->where('patrullas.activa', 1)
            ->whereColumn('patrullas.unidad_id', 'unidades.id')
            ->where('users.compartir_ubicacion', 1)
            ->whereNotNull('user_locations.captured_at')
            ->where('user_locations.captured_at', '>=', now()->subMinutes($maxAgeMinutes));

        if ($maxAccuracyMeters > 0) {
            $query->where(function ($builder) use ($maxAccuracyMeters) {
                $builder->whereNull('user_locations.accuracy')
                    ->orWhere('user_locations.accuracy', '<=', $maxAccuracyMeters);
            });
        }

        $rows = $query->get([
            'users.id as user_id',
            'patrullas.id as patrulla_id',
            'patrullas.numero_economico',
            'user_locations.lat',
            'user_locations.lng',
            'user_locations.accuracy',
            'user_locations.captured_at',
        ]);

        $latestByPatrulla = [];

        foreach ($rows as $row) {
            $key = (int) $row->patrulla_id;
            $capturedAt = Carbon::parse($row->captured_at);

            if (!isset($latestByPatrulla[$key])
                || $capturedAt->gt(Carbon::parse($latestByPatrulla[$key]->captured_at))) {
                $latestByPatrulla[$key] = $row;
            }
        }

        $nearest = null;

        foreach ($latestByPatrulla as $row) {
            $distance = $this->haversineKm(
                $incidentLat,
                $incidentLng,
                (float) $row->lat,
                (float) $row->lng
            );

            if ($nearest === null || $distance < $nearest['distance_km']) {
                $nearest = [
                    'patrulla_id' => (int) $row->patrulla_id,
                    'numero_economico' => (string) $row->numero_economico,
                    'user_id' => (int) $row->user_id,
                    'lat' => (float) $row->lat,
                    'lng' => (float) $row->lng,
                    'accuracy' => $row->accuracy !== null ? (float) $row->accuracy : null,
                    'captured_at' => Carbon::parse($row->captured_at)->toIso8601String(),
                    'distance_km' => round($distance, 3),
                ];
            }
        }

        return $nearest;
    }

    private function internalMessageContent(
        WhatsAppWebMessage $message,
        array $incident,
        array $candidate
    ): string
    {
        $timezone = (string) config('app.schedule_timezone', 'America/Mexico_City');
        $reportedAt = ($message->sent_at ? $message->sent_at->copy() : now())
            ->timezone($timezone)
            ->format('d/m/Y H:i');
        $locationUpdatedAt = Carbon::parse($candidate['captured_at'])
            ->timezone($timezone)
            ->format('d/m/Y H:i');

        return implode("\n", [
            'Se recomienda enviar la unidad más cercana al reporte C5i.',
            '',
            'Reporte: ' . $reportedAt,
            'Ubicación: ' . $incident['location'],
            'Unidad sugerida: ' . $candidate['numero_economico'],
            'Distancia aproximada: ' . number_format((float) $candidate['distance_km'], 2, '.', '') . ' km',
            'Ubicación de la unidad actualizada: ' . $locationUpdatedAt,
            'Mapa del incidente: ' . $this->mapsLink($incident['lat'], $incident['lng']),
            'Mapa de la unidad: ' . $this->mapsLink($candidate['lat'], $candidate['lng']),
        ]);
    }

    private function sendInternalMessage(array $requestedUserIds, string $content): array
    {
        $senderUserId = (int) config(
            'services.whatsapp.c5i_recommendation.internal_sender_user_id',
            21
        );

        if ($senderUserId <= 0 || !User::query()->whereKey($senderUserId)->exists()) {
            return [
                'status' => 'failed',
                'reason' => 'internal_sender_not_found',
                'delivered_user_ids' => [],
                'missing_user_ids' => $requestedUserIds,
            ];
        }

        $deliveredUserIds = User::query()
            ->whereIn('id', $requestedUserIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
        $missingUserIds = array_values(array_diff($requestedUserIds, $deliveredUserIds));

        if (empty($deliveredUserIds)) {
            return [
                'status' => 'failed',
                'reason' => 'internal_recipients_not_found',
                'delivered_user_ids' => [],
                'missing_user_ids' => $missingUserIds,
            ];
        }

        $communication = DB::transaction(function () use ($senderUserId, $deliveredUserIds, $content) {
            $communication = Comunicacion::query()->create([
                'remitente_user_id' => $senderUserId,
                'tipo' => 'aviso',
                'asunto' => 'Recomendación de unidad cercana',
                'contenido' => $content,
                'alcance' => 'usuarios',
                'unidad_id' => null,
                'turno_id' => null,
                'role_id' => null,
                'destinatario_user_id' => null,
                'requiere_enterado' => false,
                'enviado_at' => now(),
            ]);
            $now = now();

            ComunicacionDestinatario::query()->insert(array_map(
                fn (int $userId) => [
                    'comunicacion_id' => $communication->id,
                    'user_id' => $userId,
                    'leido_at' => null,
                    'enterado_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $deliveredUserIds
            ));

            return $communication;
        });

        $this->pushService->schedule($communication->id);

        return [
            'status' => empty($missingUserIds) ? 'sent' : 'partial',
            'communication_id' => $communication->id,
            'delivered_user_ids' => $deliveredUserIds,
            'missing_user_ids' => $missingUserIds,
        ];
    }

    private function groupAllowed(string $groupId): bool
    {
        return in_array(mb_strtolower(trim($groupId), 'UTF-8'), $this->normalizedConfigValues(
            'services.whatsapp.c5i_recommendation.group_ids'
        ), true);
    }

    private function sourceAllowed(string $authorId): bool
    {
        $authorId = mb_strtolower(trim($authorId), 'UTF-8');
        $authorDigits = preg_replace('/\D+/', '', $authorId) ?: '';

        foreach ($this->normalizedConfigValues('services.whatsapp.c5i_recommendation.source_author_ids') as $allowed) {
            if ($authorId === $allowed) {
                return true;
            }

            $allowedDigits = preg_replace('/\D+/', '', $allowed) ?: '';

            if ($authorDigits !== '' && $allowedDigits !== '' && hash_equals($allowedDigits, $authorDigits)) {
                return true;
            }
        }

        return false;
    }

    private function normalizedConfigValues(string $key): array
    {
        return array_map(
            fn (string $value) => mb_strtolower($value, 'UTF-8'),
            $this->csvConfig($key)
        );
    }

    private function csvConfig(string $key): array
    {
        $parts = preg_split('/[\s,;|]+/', (string) config($key, ''), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(array_map(
            fn ($value) => trim((string) $value),
            $parts ?: []
        ))));
    }

    private function recipientUserIds(): array
    {
        $configured = config(
            'services.whatsapp.c5i_recommendation.internal_recipient_user_ids',
            [1, 2, 21, 42, 47, 74]
        );
        $values = is_array($configured)
            ? $configured
            : preg_split('/[\s,;|]+/', (string) $configured, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter(array_map(
            fn ($value) => (int) $value,
            $values ?: []
        ), fn (int $value) => $value > 0)));
    }

    private function ignored(WhatsAppWebMessage $message, string $reason): array
    {
        $this->persistResult($message, 'ignored', null, null, ['reason' => $reason]);

        return ['status' => 'ignored', 'reason' => $reason];
    }

    private function persistResult(
        WhatsAppWebMessage $message,
        string $status,
        ?array $incident,
        ?array $candidate,
        array $meta
    ): void {
        $message->forceFill([
            'incident_lat' => $incident['lat'] ?? null,
            'incident_lng' => $incident['lng'] ?? null,
            'recommended_patrulla_id' => $candidate['patrulla_id'] ?? null,
            'recommendation_distance_km' => $candidate['distance_km'] ?? null,
            'recommendation_status' => $status,
            'recommendation_meta' => $meta,
            'recommendation_processed_at' => now(),
        ])->save();
    }

    private function mapsLink(float $lat, float $lng): string
    {
        return 'https://www.google.com/maps?q=' . $lat . ',' . $lng;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0088;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
