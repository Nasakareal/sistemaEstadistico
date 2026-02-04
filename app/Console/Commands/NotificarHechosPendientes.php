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

    private ?string $cachedAccessToken = null;

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
                'body'  => "El hecho {$hecho->folio_c5i} sigue PENDIENTE. Ya pasaron 48 horas.",
                'data'  => [
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
            } else {
                if (is_null($hecho->ultimo_recordatorio_72_at)) {
                    $deboNotificar = true;
                } else {
                    $ultima = Carbon::parse($hecho->ultimo_recordatorio_72_at, 'America/Mexico_City');
                    if ($ultima->lte($now->copy()->subHours(2))) {
                        $deboNotificar = true;
                    }
                }
            }

            if (!$deboNotificar) {
                continue;
            }

            $this->enviarPush((int) $hecho->created_by, [
                'title' => 'URGENTE: Turnar hecho (72 horas)',
                'body'  => "El hecho {$hecho->folio_c5i} lleva más de 72 horas PENDIENTE. Debes TURNARLO para continuar.",
                'data'  => [
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
            ->pluck('token')
            ->filter()
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        $projectId = (string) env('FIREBASE_PROJECT_ID', '');
        $clientEmail = (string) env('FIREBASE_CLIENT_EMAIL', '');
        $privateKey = (string) env('FIREBASE_PRIVATE_KEY', '');

        if ($projectId === '' || $clientEmail === '' || $privateKey === '') {
            Log::warning('FCM no configurado en .env');
            return;
        }

        $accessToken = $this->getCachedGoogleAccessToken($clientEmail, $privateKey);
        if ($accessToken === null) {
            return;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        foreach ($tokens as $token) {
            try {
                $res = Http::withToken($accessToken)
                    ->acceptJson()
                    ->timeout(15)
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
                            ],
                        ],
                    ]);

                if (!$res->ok()) {
                    Log::warning('FCM respondió error', [
                        'user_id' => $userId,
                        'status' => $res->status(),
                        'body' => $res->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('FCM fallo send', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function getCachedGoogleAccessToken(string $clientEmail, string $privateKeyEnv): ?string
    {
        if (is_string($this->cachedAccessToken) && $this->cachedAccessToken !== '') {
            return $this->cachedAccessToken;
        }

        $privateKey = str_replace("\\n", "\n", $privateKeyEnv);
        $token = $this->getGoogleAccessToken($clientEmail, $privateKey);

        if ($token !== null) {
            $this->cachedAccessToken = $token;
        }

        return $token;
    }

    private function getGoogleAccessToken(string $clientEmail, string $privateKey): ?string
    {
        try {
            $now = time();

            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_UNESCAPED_SLASHES));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_UNESCAPED_SLASHES));

            $toSign = $header . '.' . $claims;

            $signature = '';
            $ok = openssl_sign($toSign, $signature, $privateKey, 'sha256');

            if (!$ok) {
                Log::warning('No se pudo firmar JWT (openssl_sign)');
                return null;
            }

            $jwt = $toSign . '.' . $this->base64UrlEncode($signature);

            $res = Http::asForm()
                ->timeout(15)
                ->post('https://oauth2.googleapis.com/token', [
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
