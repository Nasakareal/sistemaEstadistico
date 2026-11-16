<?php

namespace Tests\Feature;

use App\Models\Grua;
use App\Models\Servicio;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SierseInboundEventApiTest extends TestCase
{
    use DatabaseTransactions;

    private Grua $grua;
    private Vehiculo $vehicle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
        Storage::fake('local');
        Cache::flush();

        $this->grua = Grua::create(['nombre' => 'GALVAN API TEST ' . uniqid()]);
        $this->vehicle = Vehiculo::create([
            'marca' => 'NISSAN',
            'linea' => 'VERSA',
            'tipo' => 'SEDAN',
            'color' => 'BLANCO',
            'placas' => 'API' . random_int(1000, 9999),
            'estado_placas' => 'MICHOACAN',
            'serie' => strtoupper(substr(uniqid('VIN'), 0, 17)),
            'capacidad_personas' => 5,
            'tipo_servicio' => 'PARTICULAR',
            'tarjeta_circulacion_nombre' => 'PRUEBA',
            'grua_id' => $this->grua->id,
            'grua' => $this->grua->nombre,
            'corralon' => $this->grua->nombre,
        ]);
        Servicio::create([
            'grua_id' => $this->grua->id,
            'vehiculo_id' => $this->vehicle->id,
            'tipo_vehiculo' => 'SEDAN',
        ]);

        config()->set('sierse.enabled', true);
        config()->set('sierse.data_exchange_enabled', true);
        config()->set('sierse.require_mtls', false);
        config()->set('sierse.clients.galvan', [
            'name' => 'Gruas Galvan',
            'grua_name' => $this->grua->nombre,
            'key_id' => 'galvan-test-key-001',
            'secret' => 'test-secret-that-is-only-used-by-the-automated-test',
            'secret_expires_at' => now()->addDay()->toIso8601String(),
            'allowed_ips' => ['127.0.0.1'],
        ]);
    }

    public function test_cost_is_applied_and_duplicate_is_idempotent(): void
    {
        $payload = [
            'external_event_id' => 'COST-' . uniqid(),
            'event_type' => 'cost_update',
            'data' => [
                'total_amount' => 2450.50,
                'currency' => 'MXN',
                'payment_method' => 'TRANSFERENCIA',
                'payment_status' => 'PAID',
            ],
        ];

        $first = $this->postSigned($payload, 'cost-event-nonce-001')
            ->assertStatus(202)
            ->assertJsonPath('event.status', 'APPLIED');
        $this->assertFalse((bool) $first->json('duplicate'));
        $this->assertDatabaseHas('servicios', [
            'vehiculo_id' => $this->vehicle->id,
            'importe_total' => 2450.50,
            'forma_pago' => 'TRANSFERENCIA',
        ]);

        $this->postSigned($payload, 'cost-event-nonce-002')
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('event.status', 'APPLIED');
    }

    public function test_release_and_documents_require_internal_review(): void
    {
        $pdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
        $payload = [
            'external_event_id' => 'RELEASE-' . uniqid(),
            'event_type' => 'release_update',
            'data' => [
                'released' => true,
                'released_at' => now()->toIso8601String(),
                'sierse_folio' => 'SIERSE-TEST-001',
                'delivery_condition' => 'CONFORME_INVENTARIO',
                'documents' => [
                    'memory_description' => [
                        'filename' => 'memoria.pdf',
                        'mime' => 'application/pdf',
                        'content_base64' => base64_encode($pdf),
                    ],
                    'payment_receipt' => [
                        'filename' => 'pago.pdf',
                        'mime' => 'application/pdf',
                        'content_base64' => base64_encode($pdf),
                    ],
                ],
            ],
        ];

        $response = $this->postSigned($payload, 'release-event-nonce-001')
            ->assertStatus(202)
            ->assertJsonPath('event.status', 'REVIEW_REQUIRED');

        $this->assertDatabaseHas('liberaciones_corralon', [
            'vehiculo_id' => $this->vehicle->id,
            'estado' => 'PENDIENTE',
            'sierse_reported_released' => true,
            'sierse_folio' => 'SIERSE-TEST-001',
        ]);
        $event = \App\Models\SierseIntegrationEvent::query()
            ->where('external_event_id', $response->json('event.external_event_id'))
            ->firstOrFail();
        $this->assertTrue(data_get($event->payload, 'documents.memory_description.received'));
        $this->assertNull(data_get($event->payload, 'documents.memory_description.content_base64'));
        $this->assertSame('PENDIENTE', $this->vehicle->fresh()->liberacionCorralon->estado);
    }

    private function postSigned(array $payload, string $nonce)
    {
        $uri = '/api/integraciones/sierse/v1/vehiculos/vehicle_registry/'
            . $this->vehicle->id . '/eventos';
        $body = json_encode($payload);
        $timestamp = (string) time();
        $canonical = implode("\n", [
            'POST', $uri, 'galvan', 'galvan-test-key-001', $timestamp, $nonce,
            hash('sha256', $body),
        ]);

        return $this->postJson($uri, $payload, [
            'X-SIERSE-Client' => 'galvan',
            'X-SIERSE-Key-Id' => 'galvan-test-key-001',
            'X-SIERSE-Timestamp' => $timestamp,
            'X-SIERSE-Nonce' => $nonce,
            'X-SIERSE-Signature' => hash_hmac(
                'sha256',
                $canonical,
                'test-secret-that-is-only-used-by-the-automated-test'
            ),
        ]);
    }
}
