<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SierseIntegrationApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
        config()->set('sierse.enabled', true);
        config()->set('sierse.data_exchange_enabled', false);
        config()->set('sierse.require_mtls', false);
        config()->set('sierse.clients.galvan', [
            'name' => 'Gruas Galvan',
            'key_id' => 'galvan-test-key-001',
            'secret' => 'test-secret-that-is-only-used-by-the-automated-test',
            'secret_expires_at' => now()->addDay()->toIso8601String(),
            'allowed_ips' => ['127.0.0.1'],
        ]);
        Cache::flush();
    }

    public function test_integration_is_hidden_while_disabled(): void
    {
        config()->set('sierse.enabled', false);

        $this->get('/api/integraciones/sierse/v1/estado')->assertNotFound();
    }

    public function test_unsigned_request_is_rejected(): void
    {
        $this->get('/api/integraciones/sierse/v1/estado')->assertUnauthorized();
    }

    public function test_correctly_signed_request_reaches_status_endpoint(): void
    {
        $headers = $this->signedHeaders('nonce-for-valid-request-001');

        $this->withHeaders($headers)
            ->get('/api/integraciones/sierse/v1/estado')
            ->assertOk()
            ->assertJsonPath('integration', 'SIERSE')
            ->assertJsonPath('client.id', 'galvan')
            ->assertJsonPath('data_exchange_enabled', false)
            ->assertJsonPath('capabilities.excluded', [
                'drivers',
                'driver_phone_numbers',
                'driver_addresses',
            ]);
    }

    public function test_nonce_cannot_be_replayed(): void
    {
        $headers = $this->signedHeaders('nonce-for-replay-test-001');

        $this->withHeaders($headers)->get('/api/integraciones/sierse/v1/estado')->assertOk();
        $this->withHeaders($headers)->get('/api/integraciones/sierse/v1/estado')->assertStatus(409);
    }

    public function test_request_from_an_unlisted_ip_is_rejected(): void
    {
        $headers = $this->signedHeaders('nonce-for-wrong-ip-test-001');

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.20'])
            ->withHeaders($headers)
            ->get('/api/integraciones/sierse/v1/estado')
            ->assertForbidden();
    }

    public function test_client_certificate_is_required_when_mtls_is_enabled(): void
    {
        config()->set('sierse.require_mtls', true);
        $headers = $this->signedHeaders('nonce-for-mtls-required-001');

        $this->withHeaders($headers)
            ->get('/api/integraciones/sierse/v1/estado')
            ->assertForbidden();
    }

    public function test_expired_key_is_rejected_even_with_a_valid_signature(): void
    {
        config()->set('sierse.clients.galvan.secret_expires_at', now()->subMinute()->toIso8601String());
        $headers = $this->signedHeaders('nonce-for-expired-key-001');

        $this->withHeaders($headers)
            ->get('/api/integraciones/sierse/v1/estado')
            ->assertUnauthorized();
    }

    public function test_vehicle_search_is_hidden_until_data_exchange_is_enabled(): void
    {
        $uri = '/api/integraciones/sierse/v1/buscar?type=conduce_folio&value=CL-86-213';
        $headers = $this->signedHeaders('nonce-for-disabled-search-001', $uri);

        $this->withHeaders($headers)->get($uri)->assertNotFound();
    }

    private function signedHeaders(
        string $nonce,
        string $uri = '/api/integraciones/sierse/v1/estado'
    ): array
    {
        $timestamp = (string) time();
        $canonical = implode("\n", [
            'GET',
            $uri,
            'galvan',
            'galvan-test-key-001',
            $timestamp,
            $nonce,
            hash('sha256', ''),
        ]);

        return [
            'X-SIERSE-Client' => 'galvan',
            'X-SIERSE-Key-Id' => 'galvan-test-key-001',
            'X-SIERSE-Timestamp' => $timestamp,
            'X-SIERSE-Nonce' => $nonce,
            'X-SIERSE-Signature' => hash_hmac(
                'sha256',
                $canonical,
                'test-secret-that-is-only-used-by-the-automated-test'
            ),
        ];
    }
}
