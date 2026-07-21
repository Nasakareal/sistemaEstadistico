<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class C5iAudioTranscriptionService
{
    public function transcribe(string $base64Audio, string $mimeType, ?string $filename = null): array
    {
        if (!(bool) config('services.whatsapp.c5i_response_time.transcribe_audio', true)) {
            return ['status' => 'disabled', 'text' => null];
        }

        $apiKey = trim((string) config('services.openai.key', ''));

        if ($apiKey === '') {
            return ['status' => 'failed', 'text' => null, 'reason' => 'openai_key_not_configured'];
        }

        $audio = base64_decode($base64Audio, true);

        if ($audio === false || $audio === '') {
            return ['status' => 'failed', 'text' => null, 'reason' => 'invalid_audio_base64'];
        }

        $maxBytes = max(1024, (int) config(
            'services.whatsapp.c5i_response_time.audio_max_bytes',
            5 * 1024 * 1024
        ));

        if (strlen($audio) > $maxBytes) {
            return [
                'status' => 'failed',
                'text' => null,
                'reason' => 'audio_too_large',
                'bytes' => strlen($audio),
                'max_bytes' => $maxBytes,
            ];
        }

        $mimeType = $this->normalizedMimeType($mimeType);
        $filename = $this->safeFilename($filename, $mimeType);
        $model = trim((string) config(
            'services.whatsapp.c5i_response_time.transcription_model',
            'gpt-4o-mini-transcribe'
        )) ?: 'gpt-4o-mini-transcribe';
        $timeout = max(10, (int) config(
            'services.whatsapp.c5i_response_time.transcription_timeout',
            60
        ));

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout($timeout)
                ->attach('file', $audio, $filename, ['Content-Type' => $mimeType])
                ->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => $model,
                    'language' => 'es',
                    'prompt' => (string) config(
                        'services.whatsapp.c5i_response_time.transcription_prompt',
                        'Radio policial en español. Transcribe literalmente números de unidad, claves, kilómetros y códigos usando dígitos y guiones.'
                    ),
                ]);
        } catch (Throwable $e) {
            Log::error('Error transcribiendo audio C5i/Siniestros', [
                'error' => $e->getMessage(),
                'model' => $model,
            ]);

            return [
                'status' => 'failed',
                'text' => null,
                'reason' => 'transcription_exception',
                'error' => $e->getMessage(),
                'model' => $model,
            ];
        }

        if (!$response->successful()) {
            $error = (string) data_get($response->json(), 'error.message', $response->body());

            Log::warning('OpenAI rechazó transcripción C5i/Siniestros', [
                'status' => $response->status(),
                'error' => $error,
                'model' => $model,
            ]);

            return [
                'status' => 'failed',
                'text' => null,
                'reason' => 'transcription_api_error',
                'http_status' => $response->status(),
                'error' => $error,
                'model' => $model,
            ];
        }

        $text = trim((string) data_get($response->json(), 'text', ''));

        if ($text === '') {
            return [
                'status' => 'failed',
                'text' => null,
                'reason' => 'empty_transcription',
                'model' => $model,
            ];
        }

        return [
            'status' => 'transcribed',
            'text' => $text,
            'model' => $model,
            'bytes' => strlen($audio),
        ];
    }

    private function normalizedMimeType(string $mimeType): string
    {
        $mimeType = mb_strtolower(trim(explode(';', $mimeType)[0]), 'UTF-8');
        $allowed = [
            'audio/ogg',
            'audio/opus',
            'audio/mpeg',
            'audio/mp3',
            'audio/mp4',
            'audio/m4a',
            'audio/wav',
            'audio/webm',
            'application/ogg',
        ];

        return in_array($mimeType, $allowed, true) ? $mimeType : 'audio/ogg';
    }

    private function safeFilename(?string $filename, string $mimeType): string
    {
        $filename = basename(trim((string) $filename));

        if ($filename !== '' && preg_match('/\.(?:flac|mp3|mp4|mpeg|mpga|m4a|ogg|wav|webm)$/i', $filename)) {
            return mb_substr($filename, 0, 180, 'UTF-8');
        }

        $extensions = [
            'audio/mpeg' => 'mp3',
            'audio/mp3' => 'mp3',
            'audio/mp4' => 'mp4',
            'audio/m4a' => 'm4a',
            'audio/wav' => 'wav',
            'audio/webm' => 'webm',
        ];

        return 'audio-c5i.' . ($extensions[$mimeType] ?? 'ogg');
    }
}
