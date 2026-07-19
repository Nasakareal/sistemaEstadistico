<?php

namespace App\Services;

use App\Models\Patrulla;
use App\Models\SuspiciousPlaceVisit;
use App\Models\User;
use App\Models\UserLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SuspiciousPlaceDwellService
{
    private const ENTRY_CONTEXT = 'siniestros-suspicious-place-entry';
    private const EXIT_CONTEXT = 'siniestros-suspicious-place-exit';

    private WhatsAppCloudService $whatsApp;
    private WhatsAppSendGuard $sendGuard;

    public function __construct(WhatsAppCloudService $whatsApp, WhatsAppSendGuard $sendGuard)
    {
        $this->whatsApp = $whatsApp;
        $this->sendGuard = $sendGuard;
    }

    public function processLocation(User $user, UserLocation $location): array
    {
        if (!(bool) config('services.whatsapp.suspicious_place.enabled', false)) {
            return ['status' => 'disabled'];
        }

        try {
            $unitId = (int) config('services.whatsapp.suspicious_place.unit_id', 1);
            if ((int) $user->unidad_id !== $unitId) {
                return ['status' => 'ignored', 'reason' => 'user_not_siniestros'];
            }

            $user->loadMissing(['patrulla', 'personal.patrulla']);
            $patrulla = $user->personal && $user->personal->patrulla
                ? $user->personal->patrulla
                : $user->patrulla;

            if (!$patrulla) {
                return ['status' => 'ignored', 'reason' => 'patrulla_not_resolved'];
            }

            if ($location->accuracy !== null
                && (float) $location->accuracy > $this->configInt('max_accuracy_meters', 100, 1)) {
                return ['status' => 'ignored', 'reason' => 'gps_accuracy_too_low'];
            }

            $capturedAt = $location->captured_at
                ? $location->captured_at->copy()
                : now();
            $now = now($capturedAt->getTimezone());
            $maxAgeMinutes = $this->configInt('location_max_age_minutes', 3, 1);

            if ($capturedAt->lt($now->copy()->subMinutes($maxAgeMinutes))
                || $capturedAt->gt($now->copy()->addMinutes(2))) {
                return ['status' => 'ignored', 'reason' => 'location_not_current'];
            }

            $distance = $this->haversineMeters(
                (float) config('services.whatsapp.suspicious_place.latitude', 19.6603522),
                (float) config('services.whatsapp.suspicious_place.longitude', -101.2373983),
                (float) $location->lat,
                (float) $location->lng
            );

            $entryRadius = $this->configInt('entry_radius_meters', 120, 25);
            $exitRadius = max(
                $entryRadius + 20,
                $this->configInt('exit_radius_meters', 180, 45)
            );

            if ($distance <= $entryRadius
                || ($distance < $exitRadius && $this->activeVisit($patrulla))) {
                return $this->processInside($user, $patrulla, $capturedAt, $distance);
            }

            if ($distance >= $exitRadius) {
                return $this->processOutside($patrulla, $capturedAt, $distance);
            }

            return $this->processBoundary($patrulla, $capturedAt, $distance);
        } catch (Throwable $e) {
            Log::error('Error procesando permanencia en lugar vigilado', [
                'user_id' => $user->id,
                'user_location_id' => $location->id,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'reason' => 'processing_exception'];
        }
    }

    public function processClientEvent(User $user, array $event): array
    {
        if (!(bool) config('services.whatsapp.suspicious_place.enabled', false)) {
            return ['status' => 'disabled'];
        }

        try {
            $unitId = (int) config('services.whatsapp.suspicious_place.unit_id', 1);
            if ((int) $user->unidad_id !== $unitId) {
                return ['status' => 'ignored', 'reason' => 'user_not_siniestros'];
            }

            if ((string) $event['place_key'] !== $this->placeKey()) {
                return ['status' => 'ignored', 'reason' => 'place_not_configured'];
            }

            $user->loadMissing(['patrulla', 'personal.patrulla']);
            $patrulla = $user->personal && $user->personal->patrulla
                ? $user->personal->patrulla
                : $user->patrulla;
            if (!$patrulla) {
                return ['status' => 'ignored', 'reason' => 'patrulla_not_resolved'];
            }

            if ((float) $event['accuracy'] > $this->configInt('max_accuracy_meters', 100, 1)) {
                return ['status' => 'ignored', 'reason' => 'gps_accuracy_too_low'];
            }

            $enteredAt = Carbon::parse($event['entered_at']);
            $occurredAt = Carbon::parse($event['occurred_at']);
            $now = now($occurredAt->getTimezone());
            $maxAgeHours = $this->configInt('client_event_max_age_hours', 24, 1);

            if ($occurredAt->lt($now->copy()->subHours($maxAgeHours))
                || $occurredAt->gt($now->copy()->addMinutes(2))) {
                return ['status' => 'ignored', 'reason' => 'client_event_not_current'];
            }

            $durationSeconds = $enteredAt->diffInSeconds($occurredAt, false);
            $dwellSeconds = $this->configInt('dwell_minutes', 5, 1) * 60;
            if ($durationSeconds < $dwellSeconds
                || abs($durationSeconds - (int) $event['duration_seconds']) > 120) {
                return ['status' => 'ignored', 'reason' => 'invalid_duration'];
            }

            $distance = $this->haversineMeters(
                (float) config('services.whatsapp.suspicious_place.latitude', 19.6603522),
                (float) config('services.whatsapp.suspicious_place.longitude', -101.2373983),
                (float) $event['lat'],
                (float) $event['lng']
            );
            $eventType = (string) $event['event_type'];

            if ($eventType === 'dwell') {
                $dwellRadius = max(
                    $this->configInt('entry_radius_meters', 120, 25) + 20,
                    $this->configInt('exit_radius_meters', 180, 45)
                );
                if ($distance >= $dwellRadius) {
                    return ['status' => 'ignored', 'reason' => 'dwell_outside_place'];
                }

                return $this->processClientDwell(
                    $user,
                    $patrulla,
                    (string) $event['visit_id'],
                    $enteredAt,
                    $occurredAt,
                    $distance
                );
            }

            $exitRadius = max(
                $this->configInt('entry_radius_meters', 120, 25) + 20,
                $this->configInt('exit_radius_meters', 180, 45)
            );
            if ($distance < $exitRadius) {
                return ['status' => 'ignored', 'reason' => 'exit_still_near_place'];
            }

            return $this->processClientExit(
                $user,
                $patrulla,
                (string) $event['visit_id'],
                $enteredAt,
                $occurredAt,
                $durationSeconds,
                $distance
            );
        } catch (Throwable $e) {
            Log::error('Error procesando evento local de permanencia', [
                'user_id' => $user->id,
                'visit_id' => $event['visit_id'] ?? null,
                'event_type' => $event['event_type'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'reason' => 'processing_exception'];
        }
    }

    private function processClientDwell(
        User $user,
        Patrulla $patrulla,
        string $clientVisitId,
        Carbon $enteredAt,
        Carbon $occurredAt,
        float $distance
    ): array {
        $visit = $this->resolveClientVisit(
            $user,
            $patrulla,
            $clientVisitId,
            $enteredAt,
            $occurredAt
        );

        if ($visit->dwell_alerted_at) {
            $visit->forceFill(['client_entry_received_at' => now()])->save();
            return ['status' => 'duplicate', 'visit_id' => $visit->id, 'event' => 'dwell'];
        }

        $dwellMinutes = $this->configInt('dwell_minutes', 5, 1);
        $notification = $this->notify(
            self::ENTRY_CONTEXT,
            'entry:' . $visit->id,
            (string) config(
                'services.whatsapp.suspicious_place.entry_template',
                'alerta_permanencia_siniestros_v1'
            ),
            [(string) $patrulla->numero_economico, (string) $dwellMinutes, $this->placeName()]
        );

        $visit->forceFill([
            'user_id' => $user->id,
            'client_visit_id' => $visit->client_visit_id ?: $clientVisitId,
            'client_entry_received_at' => now(),
            'last_inside_at' => $occurredAt,
            'last_location_at' => $occurredAt,
            'last_distance_meters' => round($distance, 2),
            'status' => $notification['successful'] ? 'alerted' : $visit->status,
            'dwell_alerted_at' => $notification['successful'] ? $occurredAt : null,
            'entry_notification_status' => $notification['status'],
            'notification_meta' => $this->mergedNotificationMeta($visit, 'entry', $notification),
        ])->save();

        return [
            'status' => $notification['successful'] ? 'dwell_alerted' : 'notification_failed',
            'visit_id' => $visit->id,
            'source' => 'client',
        ];
    }

    private function processClientExit(
        User $user,
        Patrulla $patrulla,
        string $clientVisitId,
        Carbon $enteredAt,
        Carbon $occurredAt,
        int $durationSeconds,
        float $distance
    ): array {
        $visit = $this->resolveClientVisit(
            $user,
            $patrulla,
            $clientVisitId,
            $enteredAt,
            $occurredAt
        );

        if ($visit->exit_alerted_at) {
            $visit->forceFill(['client_exit_received_at' => now()])->save();
            return ['status' => 'duplicate', 'visit_id' => $visit->id, 'event' => 'exit'];
        }

        if (!$visit->dwell_alerted_at) {
            $entry = $this->processClientDwell(
                $user,
                $patrulla,
                $clientVisitId,
                $enteredAt,
                $enteredAt->copy()->addMinutes($this->configInt('dwell_minutes', 5, 1)),
                0.0
            );
            $visit->refresh();

            if (!$visit->dwell_alerted_at) {
                return [
                    'status' => 'notification_failed',
                    'visit_id' => $visit->id,
                    'event' => 'dwell_before_exit',
                    'entry_result' => $entry,
                ];
            }
        }

        $visit->forceFill([
            'active_key' => null,
            'user_id' => $user->id,
            'client_visit_id' => $visit->client_visit_id ?: $clientVisitId,
            'client_exit_received_at' => now(),
            'last_location_at' => $occurredAt,
            'last_distance_meters' => round($distance, 2),
            'exited_at' => $occurredAt,
            'duration_seconds' => $durationSeconds,
            'status' => 'completed',
        ])->save();

        return $this->notifyExit($visit, $patrulla) + ['source' => 'client'];
    }

    private function resolveClientVisit(
        User $user,
        Patrulla $patrulla,
        string $clientVisitId,
        Carbon $enteredAt,
        Carbon $occurredAt
    ): SuspiciousPlaceVisit {
        $visit = SuspiciousPlaceVisit::query()
            ->where('client_visit_id', $clientVisitId)
            ->first();

        if (!$visit) {
            $visit = $this->activeVisit($patrulla);
        }

        if (!$visit) {
            $visit = SuspiciousPlaceVisit::query()
                ->where('patrulla_id', $patrulla->id)
                ->where('place_key', $this->placeKey())
                ->whereBetween('entered_at', [
                    $enteredAt->copy()->subMinutes(3),
                    $enteredAt->copy()->addMinutes(3),
                ])
                ->latest('entered_at')
                ->first();
        }

        if (!$visit) {
            $visit = SuspiciousPlaceVisit::query()->create([
                'active_key' => $this->activeKey($patrulla),
                'client_visit_id' => $clientVisitId,
                'user_id' => $user->id,
                'patrulla_id' => $patrulla->id,
                'place_key' => $this->placeKey(),
                'place_name' => $this->placeName(),
                'entered_at' => $enteredAt,
                'last_inside_at' => $enteredAt,
                'last_location_at' => $occurredAt,
                'status' => 'monitoring',
            ]);
        } elseif (!$visit->client_visit_id) {
            $visit->forceFill(['client_visit_id' => $clientVisitId])->save();
        }

        return $visit;
    }

    private function processInside(
        User $user,
        Patrulla $patrulla,
        Carbon $capturedAt,
        float $distance
    ): array {
        $visit = $this->activeVisit($patrulla);

        if ($visit && $this->sampleGapExceeded($visit, $capturedAt)) {
            $this->closeForTrackingGap($visit);
            $visit = null;
        }

        if (!$visit) {
            $visit = SuspiciousPlaceVisit::query()->firstOrCreate(
                ['active_key' => $this->activeKey($patrulla)],
                [
                    'user_id' => $user->id,
                    'patrulla_id' => $patrulla->id,
                    'place_key' => $this->placeKey(),
                    'place_name' => $this->placeName(),
                    'entered_at' => $capturedAt,
                    'last_inside_at' => $capturedAt,
                    'last_location_at' => $capturedAt,
                    'last_distance_meters' => $distance,
                    'status' => 'monitoring',
                ]
            );
        }

        if (!$visit->wasRecentlyCreated
            && $visit->last_location_at
            && $capturedAt->lte($visit->last_location_at)) {
            return ['status' => 'ignored', 'reason' => 'location_out_of_order'];
        }

        $visit->forceFill([
            'user_id' => $user->id,
            'last_inside_at' => $capturedAt,
            'last_location_at' => $capturedAt,
            'last_distance_meters' => round($distance, 2),
        ])->save();

        $dwellMinutes = $this->configInt('dwell_minutes', 5, 1);
        $elapsedSeconds = $visit->entered_at->diffInSeconds($capturedAt, false);

        if ($elapsedSeconds < $dwellMinutes * 60 || $visit->dwell_alerted_at) {
            return [
                'status' => 'monitoring',
                'visit_id' => $visit->id,
                'elapsed_seconds' => max(0, $elapsedSeconds),
            ];
        }

        $notification = $this->notify(
            self::ENTRY_CONTEXT,
            'entry:' . $visit->id,
            (string) config(
                'services.whatsapp.suspicious_place.entry_template',
                'alerta_permanencia_siniestros_v1'
            ),
            [
                (string) $patrulla->numero_economico,
                (string) $dwellMinutes,
                $this->placeName(),
            ]
        );

        $visit->forceFill([
            'status' => $notification['successful'] ? 'alerted' : 'monitoring',
            'dwell_alerted_at' => $notification['successful'] ? $capturedAt : null,
            'entry_notification_status' => $notification['status'],
            'notification_meta' => $this->mergedNotificationMeta($visit, 'entry', $notification),
        ])->save();

        return [
            'status' => $notification['successful'] ? 'dwell_alerted' : 'notification_failed',
            'visit_id' => $visit->id,
            'notification_status' => $notification['status'],
        ];
    }

    private function processOutside(Patrulla $patrulla, Carbon $capturedAt, float $distance): array
    {
        $pendingExit = SuspiciousPlaceVisit::query()
            ->where('patrulla_id', $patrulla->id)
            ->where('place_key', $this->placeKey())
            ->where('status', 'completed')
            ->whereNotNull('exited_at')
            ->whereNotNull('dwell_alerted_at')
            ->whereNull('exit_alerted_at')
            ->latest('exited_at')
            ->first();

        if ($pendingExit) {
            return $this->notifyExit($pendingExit, $patrulla);
        }

        $visit = $this->activeVisit($patrulla);
        if (!$visit) {
            return ['status' => 'outside'];
        }

        if ($visit->last_location_at && $capturedAt->lte($visit->last_location_at)) {
            return ['status' => 'ignored', 'reason' => 'location_out_of_order'];
        }

        if ($this->sampleGapExceeded($visit, $capturedAt)) {
            $this->closeForTrackingGap($visit);
            return ['status' => 'tracking_gap', 'visit_id' => $visit->id];
        }

        $durationSeconds = max(0, $visit->entered_at->diffInSeconds($capturedAt, false));
        $visit->forceFill([
            'active_key' => null,
            'last_location_at' => $capturedAt,
            'last_distance_meters' => round($distance, 2),
            'exited_at' => $capturedAt,
            'duration_seconds' => $durationSeconds,
            'status' => $visit->dwell_alerted_at ? 'completed' : 'discarded',
        ])->save();

        if (!$visit->dwell_alerted_at) {
            return ['status' => 'passed_without_dwell', 'visit_id' => $visit->id];
        }

        return $this->notifyExit($visit, $patrulla);
    }

    private function processBoundary(Patrulla $patrulla, Carbon $capturedAt, float $distance): array
    {
        $visit = $this->activeVisit($patrulla);
        if (!$visit) {
            return ['status' => 'boundary_without_visit'];
        }

        if ($visit->last_location_at && $capturedAt->lte($visit->last_location_at)) {
            return ['status' => 'ignored', 'reason' => 'location_out_of_order'];
        }

        if ($this->sampleGapExceeded($visit, $capturedAt)) {
            $this->closeForTrackingGap($visit);
            return ['status' => 'tracking_gap', 'visit_id' => $visit->id];
        }

        $visit->forceFill([
            'last_location_at' => $capturedAt,
            'last_distance_meters' => round($distance, 2),
        ])->save();

        return ['status' => 'boundary', 'visit_id' => $visit->id];
    }

    private function notifyExit(SuspiciousPlaceVisit $visit, Patrulla $patrulla): array
    {
        $minutes = max(1, (int) floor(((int) $visit->duration_seconds) / 60));
        $notification = $this->notify(
            self::EXIT_CONTEXT,
            'exit:' . $visit->id,
            (string) config(
                'services.whatsapp.suspicious_place.exit_template',
                'alerta_salida_permanencia_siniestros_v1'
            ),
            [
                (string) $patrulla->numero_economico,
                (string) $minutes,
                $this->placeName(),
            ]
        );

        $visit->forceFill([
            'exit_alerted_at' => $notification['successful'] ? now() : null,
            'exit_notification_status' => $notification['status'],
            'notification_meta' => $this->mergedNotificationMeta($visit, 'exit', $notification),
        ])->save();

        return [
            'status' => $notification['successful'] ? 'exit_alerted' : 'notification_failed',
            'visit_id' => $visit->id,
            'duration_minutes' => $minutes,
            'notification_status' => $notification['status'],
        ];
    }

    private function notify(
        string $context,
        string $periodKey,
        string $template,
        array $parameters
    ): array {
        $recipients = $this->recipientNumbers();
        if (empty($recipients)) {
            return ['successful' => false, 'status' => 'no_recipients', 'results' => []];
        }

        if ((bool) config('services.whatsapp.suspicious_place.dry_run', true)) {
            return [
                'successful' => true,
                'status' => 'dry_run',
                'template' => $template,
                'parameters' => $parameters,
                'recipients_count' => count($recipients),
                'results' => [],
            ];
        }

        $language = (string) config(
            'services.whatsapp.suspicious_place.template_language',
            'es_MX'
        );
        $results = [];
        $failed = 0;

        foreach ($recipients as $recipient) {
            if (!$this->sendGuard->reserve($context, $periodKey, $recipient, 30)) {
                $results[] = ['recipient' => $recipient, 'status' => 'duplicate'];
                continue;
            }

            try {
                $result = $this->whatsApp->sendTemplate(
                    $recipient,
                    $template,
                    $parameters,
                    $language
                );

                if (!($result['ok'] ?? false)) {
                    $failed++;
                    $this->sendGuard->release($context, $periodKey, $recipient);
                    $results[] = [
                        'recipient' => $recipient,
                        'status' => 'failed',
                        'error' => data_get($result, 'body.error.message'),
                    ];
                    continue;
                }

                $messageId = data_get($result, 'body.messages.0.id');
                $this->sendGuard->markSent($context, $periodKey, $recipient, $messageId, 30);
                $results[] = [
                    'recipient' => $recipient,
                    'status' => 'sent',
                    'message_id' => $messageId,
                ];
            } catch (Throwable $e) {
                $failed++;
                $this->sendGuard->release($context, $periodKey, $recipient);
                $results[] = [
                    'recipient' => $recipient,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'successful' => $failed === 0,
            'status' => $failed === 0 ? 'sent' : ($failed < count($recipients) ? 'partial' : 'failed'),
            'template' => $template,
            'parameters' => $parameters,
            'results' => $results,
        ];
    }

    private function activeVisit(Patrulla $patrulla): ?SuspiciousPlaceVisit
    {
        return SuspiciousPlaceVisit::query()
            ->where('active_key', $this->activeKey($patrulla))
            ->first();
    }

    private function closeForTrackingGap(SuspiciousPlaceVisit $visit): void
    {
        $duration = max(0, $visit->entered_at->diffInSeconds($visit->last_inside_at, false));
        $visit->forceFill([
            'active_key' => null,
            'exited_at' => $visit->last_inside_at,
            'duration_seconds' => $duration,
            'status' => 'tracking_lost',
        ])->save();
    }

    private function sampleGapExceeded(SuspiciousPlaceVisit $visit, Carbon $capturedAt): bool
    {
        if (!$visit->last_location_at) {
            return false;
        }

        $gapSeconds = $visit->last_location_at->diffInSeconds($capturedAt, false);
        return $gapSeconds > $this->configInt('max_sample_gap_minutes', 3, 1) * 60;
    }

    private function activeKey(Patrulla $patrulla): string
    {
        return 'patrulla:' . $patrulla->id . ':' . $this->placeKey();
    }

    private function placeKey(): string
    {
        return (string) config('services.whatsapp.suspicious_place.place_key', 'gruas-munoz');
    }

    private function placeName(): string
    {
        return (string) config('services.whatsapp.suspicious_place.place_name', 'Grúas Muñoz');
    }

    private function recipientNumbers(): array
    {
        $parts = preg_split(
            '/[\s,;|]+/',
            (string) config('services.whatsapp.suspicious_place.to', ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        $numbers = [];

        foreach ($parts ?: [] as $recipient) {
            $number = preg_replace('/\D+/', '', (string) $recipient) ?: '';
            if (strlen($number) >= 10 && strlen($number) <= 15) {
                $numbers[] = $number;
            }
        }

        return array_values(array_unique($numbers));
    }

    private function configInt(string $key, int $default, int $minimum): int
    {
        return max($minimum, (int) config('services.whatsapp.suspicious_place.' . $key, $default));
    }

    private function mergedNotificationMeta(
        SuspiciousPlaceVisit $visit,
        string $event,
        array $notification
    ): array {
        $meta = (array) ($visit->notification_meta ?: []);
        $meta[$event] = $notification;
        return $meta;
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusMeters = 6371008.8;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
