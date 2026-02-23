<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class WhatsAppBot
{
    public static function sendToChat(string $chat_id, string $message, array $media_urls = []): array
    {
        $baseUrl = rtrim((string) env('WABOT_URL', 'http://127.0.0.1:3001'), '/');

        $resp = Http::timeout(25)->post($baseUrl . '/send-chat', [
            'chat_id' => $chat_id,
            'message' => $message,
            'media_urls' => array_values(array_filter($media_urls)),
        ]);

        if (!$resp->ok()) {
            return [
                'ok' => false,
                'status' => $resp->status(),
                'body' => $resp->body(),
            ];
        }

        return $resp->json();
    }
}
