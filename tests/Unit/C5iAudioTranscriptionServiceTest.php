<?php

namespace Tests\Unit;

use App\Services\C5iAudioTranscriptionService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class C5iAudioTranscriptionServiceTest extends TestCase
{
    public function test_transcribe_audio_con_endpoint_oficial(): void
    {
        config([
            'services.openai.key' => 'openai-test-key',
            'services.whatsapp.c5i_response_time.transcribe_audio' => true,
            'services.whatsapp.c5i_response_time.transcription_model' => 'gpt-4o-mini-transcribe',
            'services.whatsapp.c5i_response_time.audio_max_bytes' => 1024,
        ]);
        Http::fake([
            'api.openai.com/v1/audio/transcriptions' => Http::response([
                'text' => 'Unidad 22-1110, 86 sobre el K6.',
            ], 200),
        ]);

        $result = (new C5iAudioTranscriptionService())->transcribe(
            base64_encode('contenido ogg'),
            'audio/ogg; codecs=opus'
        );

        $this->assertSame('transcribed', $result['status']);
        $this->assertSame('Unidad 22-1110, 86 sobre el K6.', $result['text']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/audio/transcriptions'
                && $request->hasHeader('Authorization', 'Bearer openai-test-key');
        });
    }
}
