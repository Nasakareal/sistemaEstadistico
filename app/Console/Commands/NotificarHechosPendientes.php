<?php

namespace App\Console\Commands;

use App\Models\Hechos;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificarHechosPendientes extends Command
{
    protected $signature = 'hechos:notificar-pendientes';
    protected $description = 'Notifica al creador del hecho cuando pasan 48h/72h y sigue PENDIENTE (y recordatorios tras 72h).';

    public function handle(): int
    {
        $now = Carbon::now('America/Mexico_City');

        try {
            $this->procesar48h($now);
            $this->procesar72h($now);

            $this->info('OK - NotificarHechosPendientes finalizó.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Error en NotificarHechosPendientes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error('ERROR - Ocurrió un error al ejecutar NotificarHechosPendientes.');
            return self::FAILURE;
        }
    }

    private function procesar48h(Carbon $now): void
    {
        $hechos = Hechos::query()
            ->where('situacion', 'PENDIENTE')
            ->whereNull('notificado_48_at')
            ->where('created_at', '<=', $now->copy()->subHours(48))
            ->orderBy('created_at')
            ->limit(500)
            ->get(['id', 'folio_c5i', 'created_by', 'created_at']);

        foreach ($hechos as $hecho) {
            if (empty($hecho->created_by)) {
                Hechos::where('id', $hecho->id)->update(['notificado_48_at' => $now]);
                continue;
            }

            $this->enviarPush((int) $hecho->created_by, [
                'title' => 'Hecho pendiente: 48 horas',
                'body' => "El hecho {$hecho->folio_c5i} sigue PENDIENTE. Ya pasaron 48 horas.",
                'data' => [
                    'type' => 'HECHO_48H',
                    'hecho_id' => (string) $hecho->id,
                    'folio_c5i' => (string) $hecho->folio_c5i,
                ],
            ]);

            Hechos::where('id', $hecho->id)->update(['notificado_48_at' => $now]);
        }

        $this->info("48h: {$hechos->count()} notificación(es) procesada(s).");
    }

    private function procesar72h(Carbon $now): void
    {
        $reminderMinutes = 60;

        $hechos = Hechos::query()
            ->where('situacion', 'PENDIENTE')
            ->where('created_at', '<=', $now->copy()->subHours(72))
            ->orderBy('created_at')
            ->limit(500)
            ->get([
                'id',
                'folio_c5i',
                'created_by',
                'created_at',
                'notificado_72_at',
                'ultimo_recordatorio_72_at',
            ]);

        foreach ($hechos as $hecho) {
            if (empty($hecho->created_by)) {
                if (is_null($hecho->notificado_72_at)) {
                    Hechos::where('id', $hecho->id)->update([
                        'notificado_72_at' => $now,
                        'ultimo_recordatorio_72_at' => $now,
                    ]);
                }
                continue;
            }

            $deboNotificar = false;

            if (is_null($hecho->notificado_72_at)) {
                $deboNotificar = true;
            } elseif (is_null($hecho->ultimo_recordatorio_72_at)) {
                $deboNotificar = true;
            } else {
                $ultima = Carbon::parse($hecho->ultimo_recordatorio_72_at, 'America/Mexico_City');

                if ($ultima->lte($now->copy()->subMinutes($reminderMinutes))) {
                    $deboNotificar = true;
                }
            }

            if (!$deboNotificar) {
                continue;
            }

            $this->enviarPush((int) $hecho->created_by, [
                'title' => 'URGENTE: Turnar hecho (72 horas)',
                'body' => "El hecho {$hecho->folio_c5i} lleva más de 72 horas PENDIENTE. Debes TURNARLO para continuar.",
                'data' => [
                    'type' => 'HECHO_72H',
                    'hecho_id' => (string) $hecho->id,
                    'folio_c5i' => (string) $hecho->folio_c5i,
                    'lock' => '1',
                ],
            ]);

            Hechos::where('id', $hecho->id)->update([
                'notificado_72_at' => $hecho->notificado_72_at ?? $now,
                'ultimo_recordatorio_72_at' => $now,
            ]);
        }

        $this->info("72h: {$hechos->count()} hecho(s) revisado(s).");
    }

    private function enviarPush(int $userId, array $payload): void
    {
        $tokens = DB::table('device_tokens')
            ->where('user_id', $userId)
            ->orderByDesc('last_seen_at')
            ->limit(10)
            ->get(['id', 'user_id', 'token', 'platform', 'last_seen_at']);

        if ($tokens->isEmpty()) {
            Log::info('FCM: sin tokens para usuario', [
                'user_id' => $userId,
            ]);

            return;
        }

        $projectId = (string) env('FIREBASE_PROJECT_ID', '');
        $clientEmail = (string) env('FIREBASE_CLIENT_EMAIL', '');
        $privateKey = (string) env('FIREBASE_PRIVATE_KEY', '');

        if ($projectId === '' || $clientEmail === '' || $privateKey === '') {
            Log::warning('FCM no configurado en .env', [
                'user_id' => $userId,
                'project_id' => $projectId !== '',
                'client_email' => $clientEmail !== '',
                'private_key' => $privateKey !== '',
            ]);

            return;
        }

        $privateKey = str_replace("\\n", "\n", $privateKey);

        $accessToken = $this->getGoogleAccessToken($clientEmail, $privateKey);

        if ($accessToken === null) {
            Log::warning('FCM: no se pudo obtener access token', [
                'user_id' => $userId,
            ]);

            return;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        foreach ($tokens as $deviceToken) {
            $token = (string) $deviceToken->token;

            Log::info('FCM enviando token', [
                'device_token_id' => $deviceToken->id,
                'user_id' => $deviceToken->user_id,
                'platform' => $deviceToken->platform,
                'last_seen_at' => $deviceToken->last_seen_at,
                'token_preview' => substr($token, 0, 25) . '...',
            ]);

            try {
                $res = Http::withToken($accessToken)
                    ->acceptJson()
                    ->post($url, [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => (string) ($payload['title'] ?? ''),
                                'body' => (string) ($payload['body'] ?? ''),
                            ],
                            'data' => array_map('strval', (array) ($payload['data'] ?? [])),
                            'android' => [
                                'priority' => 'HIGH',
                                'ttl' => '3600s',
                                'notification' => [
                                    'channel_id' => 'hechos_alertas',
                                    'sound' => 'default',
                                    'default_sound' => true,
                                    'default_vibrate_timings' => true,
                                    'notification_priority' => 'PRIORITY_MAX',
                                ],
                            ],
                            'apns' => [
                                'headers' => [
                                    'apns-priority' => '10',
                                ],
                                'payload' => [
                                    'aps' => [
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ],
                    ]);

                if (!$res->ok()) {
                    Log::warning('FCM respondio error', [
                        'device_token_id' => $deviceToken->id,
                        'user_id' => $deviceToken->user_id,
                        'platform' => $deviceToken->platform,
                        'status' => $res->status(),
                        'body' => $res->body(),
                    ]);

                    $body = (string) $res->body();

                    if (str_contains($body, 'UNREGISTERED') || str_contains($body, 'NOT_FOUND')) {
                        DB::table('device_tokens')->where('id', $deviceToken->id)->delete();

                        Log::info('FCM token eliminado', [
                            'device_token_id' => $deviceToken->id,
                            'user_id' => $deviceToken->user_id,
                            'platform' => $deviceToken->platform,
                        ]);
                    }
                } else {
                    Log::info('FCM enviado correctamente', [
                        'device_token_id' => $deviceToken->id,
                        'user_id' => $deviceToken->user_id,
                        'platform' => $deviceToken->platform,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('FCM fallo send', [
                    'device_token_id' => $deviceToken->id,
                    'user_id' => $deviceToken->user_id,
                    'platform' => $deviceToken->platform,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function getGoogleAccessToken(string $clientEmail, string $privateKey): ?string
    {
        try {
            $now = time();

            $header = $this->base64UrlEncode((string) json_encode([
                'alg' => 'RS256',
                'typ' => 'JWT',
            ]));

            $claims = $this->base64UrlEncode((string) json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $toSign = $header . '.' . $claims;

            $signature = '';
            $ok = openssl_sign($toSign, $signature, $privateKey, 'SHA256');

            if (!$ok) {
                Log::warning('No se pudo firmar JWT (openssl_sign)');
                return null;
            }

            $jwt = $toSign . '.' . $this->base64UrlEncode($signature);

            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$res->ok()) {
                Log::warning('OAuth token fallo', [
                    'status' => $res->status(),
                    'body' => $res->body(),
                ]);

                return null;
            }

            $json = $res->json();
            $token = $json['access_token'] ?? null;

            return is_string($token) && $token !== '' ? $token : null;
        } catch (\Throwable $e) {
            Log::warning('Error getGoogleAccessToken', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
