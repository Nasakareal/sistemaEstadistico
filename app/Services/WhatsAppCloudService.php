<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudService
{
    public function sendText(string $to, string $body): array
    {
        return $this->request([
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeTo($to),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ]);
    }

    public function sendTemplate(string $to, string $templateName, array $bodyParameters = [], string $language = 'es_MX'): array
    {
        $parameters = [];

        foreach ($bodyParameters as $value) {
            $parameters[] = [
                'type' => 'text',
                'text' => (string) $value,
            ];
        }

        return $this->request([
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeTo($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language,
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $parameters,
                    ],
                ],
            ],
        ]);
    }

    public function sendInteractive(string $to, array $interactive): array
    {
        return $this->request([
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeTo($to),
            'type' => 'interactive',
            'interactive' => $interactive,
        ]);
    }

    public function sendImage(string $to, string $imageUrl): array
    {
        return $this->request([
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizeTo($to),
            'type' => 'image',
            'image' => [
                'link' => $imageUrl,
            ],
        ]);
    }

    protected function request(array $payload): array
    {
        $graphVersion = (string) config('services.whatsapp.graph_version', 'v19.0');
        $accessToken = (string) (
            config('services.whatsapp.token')
            ?: config('services.whatsapp.access_token')
            ?: env('WHATSAPP_ACCESS_TOKEN')
        );
        $phoneNumberId = (string) config('services.whatsapp.phone_number_id', '');

        if ($accessToken === '' || $phoneNumberId === '') {
            Log::warning('WA Cloud sin configuración', [
                'to' => $payload['to'] ?? null,
                'type' => $payload['type'] ?? null,
            ]);

            return [
                'ok' => false,
                'status' => 0,
                'body' => ['error' => 'Configuración incompleta de WhatsApp.'],
                'raw' => null,
                'payload' => $payload,
                'url' => null,
            ];
        }

        $url = "https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}/messages";

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        Log::info('WA Cloud response', [
            'to' => $payload['to'] ?? null,
            'type' => $payload['type'] ?? null,
            'status' => $response->status(),
            'body' => $response->json(),
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json(),
            'raw' => $response->body(),
            'payload' => $payload,
            'url' => $url,
        ];
    }

    protected function normalizeTo(string $to): string
    {
        return preg_replace('/\D+/', '', $to ?? '');
    }
}
