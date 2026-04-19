<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

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
            throw new RuntimeException('Faltan WHATSAPP_ACCESS_TOKEN o WHATSAPP_PHONE_NUMBER_ID en el .env');
        }

        $url = "https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}/messages";

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

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
