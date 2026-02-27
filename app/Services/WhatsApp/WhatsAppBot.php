<?php

namespace App\Services\WhatsApp;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WhatsAppBot
{
    public static function sendToChat(string $chat_id, string $message, array $media_urls = []): array
    {
        $baseUrl = rtrim((string) env('WABOT_URL', 'http://127.0.0.1:3001'), '/');

        $payload = [
            'chat_id' => $chat_id,
            'message' => $message,
            'media_urls' => array_values(array_filter($media_urls)),
        ];

        try {
            $resp = Http::timeout(120)
                ->withOptions([
                    'connect_timeout' => 10,
                ])
                ->retry(2, 500)
                ->post($baseUrl . '/send-chat', $payload);

            if ($resp->status() === 503) {
                usleep(800000);

                $resp = Http::timeout(20)
                    ->withOptions([
                        'connect_timeout' => 10,
                    ])
                    ->retry(2, 500)
                    ->post($baseUrl . '/send-chat', $payload);
            }

            $status = $resp->status();

            $json = null;
            try {
                $json = $resp->json();
            } catch (\Throwable $e) {
                $json = null;
            }

            if ($status === 202 && is_array($json) && (($json['queued'] ?? false) === true)) {
                return [
                    'ok' => true,
                    'queued' => true,
                    'job_id' => $json['job_id'] ?? null,
                    'queue_len' => $json['queue_len'] ?? null,
                    'status' => 202,
                ];
            }

            if ($resp->successful()) {
                if (!is_array($json)) {
                    return [
                        'ok' => true,
                        'queued' => false,
                        'status' => $status,
                        'body' => $resp->body(),
                    ];
                }

                return array_merge(
                    ['ok' => true, 'queued' => ($json['queued'] ?? false), 'status' => $status],
                    $json
                );
            }

            return [
                'ok' => false,
                'queued' => false,
                'status' => $status,
                'body' => $resp->body(),
                'json' => is_array($json) ? $json : null,
            ];
        } catch (ConnectionException $e) {
            return [
                'ok' => false,
                'queued' => false,
                'status' => 0,
                'error' => 'CONNECTION_EXCEPTION',
                'message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'queued' => false,
                'status' => 0,
                'error' => 'UNEXPECTED_EXCEPTION',
                'message' => $e->getMessage(),
            ];
        }
    }
}
