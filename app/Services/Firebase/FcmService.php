<?php

namespace App\Services\Firebase;

use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;

class FcmService
{
    public function sendToToken(string $deviceToken, array $notification, array $data = []): void
    {
        $projectId = (string) env('FIREBASE_PROJECT_ID');
        $clientEmail = (string) env('FIREBASE_CLIENT_EMAIL');
        $privateKey = (string) env('FIREBASE_PRIVATE_KEY');

        if ($projectId === '' || $clientEmail === '' || $privateKey === '') {
            throw new \RuntimeException('Faltan variables FIREBASE_* en .env');
        }

        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            [
                'type' => 'service_account',
                'client_email' => $clientEmail,
                'private_key' => str_replace("\\n", "\n", $privateKey),
                'token_uri' => 'https://oauth2.googleapis.com/token',
            ]
        );

        $token = $credentials->fetchAuthToken();
        if (!isset($token['access_token'])) {
            throw new \RuntimeException('No se pudo obtener access_token de Google');
        }

        $http = new Client();

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => (string) ($notification['title'] ?? ''),
                    'body'  => (string) ($notification['body'] ?? ''),
                ],
                'data' => array_map('strval', $data),
                'android' => [
                    'priority' => 'HIGH',
                ],
            ],
        ];

        $resp = $http->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token['access_token'],
                'Content-Type'  => 'application/json',
            ],
            'json' => $payload,
            'timeout' => 15,
        ]);

        if ($resp->getStatusCode() >= 300) {
            throw new \RuntimeException('FCM respondió con error: ' . $resp->getBody()->getContents());
        }
    }
}
