<?php

namespace App\Services;

use App\Models\WhatsAppWebMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class C5iSiniestrosRecommendationService
{
    private const CONTEXT = 'c5i-siniestros-recomendacion';

    private WhatsAppCloudService $whatsApp;
    private WhatsAppSendGuard $sendGuard;

    public function __construct(WhatsAppCloudService $whatsApp, WhatsAppSendGuard $sendGuard)
    {
        $this->whatsApp = $whatsApp;
        $this->sendGuard = $sendGuard;
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

        $recipients = $this->recipientNumbers();
        $template = trim((string) config('services.whatsapp.c5i_recommendation.template', ''));
        $language = trim((string) config('services.whatsapp.c5i_recommendation.template_language', 'es_MX')) ?: 'es_MX';
        $params = $this->templateParams($message, $incident, $candidate);
        $baseMeta = [
            'template' => $template,
            'template_language' => $language,
            'recipients' => $recipients,
            'candidate' => $candidate,
            'template_params' => $params,
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

        if (empty($recipients) || $template === '') {
            $baseMeta['reason'] = empty($recipients) ? 'recipients_not_configured' : 'template_not_configured';
            $this->persistResult($message, 'failed', $incident, $candidate, $baseMeta);

            return ['status' => 'failed', 'reason' => $baseMeta['reason']];
        }

        $periodKey = 'message:' . $message->id;
        $results = [];
        $sent = 0;

        foreach ($recipients as $recipient) {
            if (!$this->sendGuard->reserve(self::CONTEXT, $periodKey, $recipient, 30)) {
                $results[] = ['recipient' => $recipient, 'status' => 'duplicate'];
                continue;
            }

            try {
                $response = $this->whatsApp->sendTemplate(
                    $recipient,
                    $template,
                    $params,
                    $language
                );

                if (!($response['ok'] ?? false)) {
                    $this->sendGuard->release(self::CONTEXT, $periodKey, $recipient);
                    $results[] = [
                        'recipient' => $recipient,
                        'status' => 'failed',
                        'http_status' => $response['status'] ?? null,
                        'error' => data_get($response, 'body.error.message'),
                    ];
                    continue;
                }

                $messageId = data_get($response, 'body.messages.0.id');
                $this->sendGuard->markSent(self::CONTEXT, $periodKey, $recipient, $messageId, 30);
                $results[] = [
                    'recipient' => $recipient,
                    'status' => 'sent',
                    'message_id' => $messageId,
                ];
                $sent++;
            } catch (Throwable $e) {
                $this->sendGuard->release(self::CONTEXT, $periodKey, $recipient);
                $results[] = [
                    'recipient' => $recipient,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        $status = $sent === count($recipients)
            ? 'sent'
            : ($sent > 0 ? 'partial' : 'failed');
        $baseMeta['send_results'] = $results;

        $this->persistResult($message, $status, $incident, $candidate, $baseMeta);

        Log::info('Resultado recomendación C5i/Siniestros', [
            'whatsapp_web_message_id' => $message->id,
            'status' => $status,
            'sent' => $sent,
            'recipients' => count($recipients),
            'patrulla_id' => $candidate['patrulla_id'],
        ]);

        return ['status' => $status, 'candidate' => $candidate, 'results' => $results];
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

    private function templateParams(WhatsAppWebMessage $message, array $incident, array $candidate): array
    {
        $timezone = (string) config('app.schedule_timezone', 'America/Mexico_City');
        $reportedAt = ($message->sent_at ? $message->sent_at->copy() : now())
            ->timezone($timezone)
            ->format('d/m/Y H:i');
        $locationUpdatedAt = Carbon::parse($candidate['captured_at'])
            ->timezone($timezone)
            ->format('d/m/Y H:i');

        return [
            $reportedAt,
            $incident['location'],
            $candidate['numero_economico'],
            number_format((float) $candidate['distance_km'], 2, '.', ''),
            $locationUpdatedAt,
            $this->mapsLink($incident['lat'], $incident['lng']),
            $this->mapsLink($candidate['lat'], $candidate['lng']),
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

    private function recipientNumbers(): array
    {
        $numbers = [];

        foreach ($this->csvConfig('services.whatsapp.c5i_recommendation.to') as $recipient) {
            $number = preg_replace('/\D+/', '', $recipient) ?: '';

            if (strlen($number) >= 10 && strlen($number) <= 15) {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
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
