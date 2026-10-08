<?php

namespace App\Services;

use Google\Auth\ApplicationDefaultCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushService
{
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        $projectId = config('services.firebase.project_id');
        $serviceAccountPath = config('services.firebase.service_account');

        if (!$projectId || !$serviceAccountPath) {
            Log::error('FCM config missing', ['project_id' => $projectId, 'service_account' => $serviceAccountPath]);
            return false;
        }

        if (!file_exists($serviceAccountPath)) {
            Log::error('FCM service account file not found', ['path' => $serviceAccountPath]);
            return false;
        }

        try {
            putenv('GOOGLE_APPLICATION_CREDENTIALS='.$serviceAccountPath);

            $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
            $creds = ApplicationDefaultCredentials::getCredentials($scopes);
            $tokenArr = $creds->fetchAuthToken();

            $accessToken = $tokenArr['access_token'] ?? null;
            if (!$accessToken) {
                Log::error('FCM access token missing');
                return false;
            }

            $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $allOk = true;

            foreach ($tokens as $t) {
                $isCommunication = ($data['modulo'] ?? '') === 'comunicaciones';
                $pushType = strtoupper((string) ($data['type'] ?? ''));
                $isWaze = in_array($pushType, ['WAZE_ACCIDENT', 'WAZE_ROAD_CLOSED'], true);
                $messageData = $data;
                if ($isCommunication || $isWaze) {
                    $messageData['push_title'] = $title;
                    $messageData['push_body'] = $body;
                }

                $message = [
                    'token' => $t,
                    'data' => $this->stringifyData($messageData),
                ];
                if ($isCommunication || $isWaze) {
                    // Android receives a high-priority data push so the app can
                    // render communications and Waze in independent groups.
                    $message += self::platformOptions($data, $title, $body);
                } else {
                    $message['notification'] = [
                        'title' => $title,
                        'body'  => $body,
                    ];
                }

                $payload = ['message' => $message];

                $res = Http::timeout(15)
                    ->withToken($accessToken)
                    ->post($endpoint, $payload);

                if (!$res->ok()) {
                    $allOk = false;
                    Log::warning('FCM send failed', [
                        'status' => $res->status(),
                        'body' => $res->body(),
                    ]);
                }
            }

            return $allOk;

        } catch (\Throwable $e) {
            Log::error('FCM send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public static function platformOptions(array $data, string $title = '', string $body = ''): array
    {
        $isCommunication = ($data['modulo'] ?? '') === 'comunicaciones';
        $pushType = strtoupper((string) ($data['type'] ?? ''));
        $isWaze = in_array($pushType, ['WAZE_ACCIDENT', 'WAZE_ROAD_CLOSED'], true);

        if (!$isCommunication && !$isWaze) {
            return [];
        }

        $threadId = $isWaze ? 'alertas_waze' : 'comunicaciones_prioritarias';
        $category = $isWaze ? 'ALERTA_WAZE' : 'COMUNICACION_PRIORITARIA';

        return [
            'android' => [
                'priority' => 'high',
            ],
            'apns' => [
                'headers' => ['apns-priority' => '10', 'apns-push-type' => 'alert'],
                'payload' => ['aps' => [
                    'alert' => ['title' => $title, 'body' => $body],
                    'sound' => 'default',
                    'thread-id' => $threadId,
                    'category' => $category,
                ]],
            ],
        ];
    }

    private function stringifyData(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            $out[$k] = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
        }
        return $out;
    }
}
