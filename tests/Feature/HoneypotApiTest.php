<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HoneypotApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');
        Cache::flush();
    }

    public function test_it_returns_only_synthetic_records_and_logs_the_interaction(): void
    {
        $response = $this->getJson('/api/v1/legacy/personnel');

        $response->assertOk()
            ->assertJsonPath('data.0.id', 'EMP-90017')
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->assertDatabaseHas('security_events', [
            'event_code' => 'honeypot_interaction',
            'category' => 'deception',
            'path' => '/api/v1/legacy/personnel',
        ]);
    }

    public function test_it_detects_sql_injection_without_executing_or_echoing_the_payload(): void
    {
        $payload = "' UNION SELECT password FROM users -- ";
        $response = $this->getJson('/api/v1/legacy/personnel?search=' . urlencode($payload));

        $response->assertOk();
        $this->assertStringNotContainsString('UNION SELECT', $response->getContent());

        $event = SecurityEvent::where('event_code', 'honeypot_sql_injection_attempt')->firstOrFail();
        $this->assertSame('critical', $event->severity);
        $this->assertContains('union_select', $event->metadata['sql_pattern_labels']);
        $this->assertArrayNotHasKey('search', $event->metadata);
    }

    public function test_it_detects_mass_queries_and_restricted_resources(): void
    {
        $this->getJson('/api/v1/legacy/reports?limit=10000&export=true')
            ->assertOk();

        $this->assertDatabaseHas('security_events', [
            'event_code' => 'honeypot_mass_query_attempt',
            'category' => 'exfiltration',
        ]);

        $this->getJson('/api/v1/legacy/backups/latest')
            ->assertForbidden()
            ->assertJsonPath('error', 'restricted_resource');

        $this->assertDatabaseHas('security_events', [
            'event_code' => 'honeypot_restricted_access',
            'category' => 'authorization',
        ]);
        $this->assertDatabaseMissing('security_events', [
            'event_code' => 'access_denied',
            'path' => '/api/v1/legacy/backups/latest',
        ]);
    }

    public function test_it_detects_enumeration_of_distinct_endpoints(): void
    {
        config(['honeypot_api.enumeration_threshold' => 3]);

        $this->getJson('/api/v1/legacy/personnel');
        $this->getJson('/api/v1/legacy/incidents');
        $this->getJson('/api/v1/legacy/reports');

        $this->assertDatabaseHas('security_events', [
            'event_code' => 'honeypot_endpoint_enumeration',
            'category' => 'reconnaissance',
        ]);
    }
}
