<?php

namespace Tests\Unit;

use App\Models\SecurityEvent;
use App\Services\IpGeolocationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IpGeolocationServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'honeypot_api.geolocation.enabled' => true,
            'honeypot_api.geolocation.url' => 'https://ipinfo.io/{ip}/json',
            'honeypot_api.geolocation.token' => 'test-token',
            'honeypot_api.geolocation.provider' => 'ipinfo',
        ]);
    }

    public function test_it_normalizes_and_caches_an_approximate_public_ip_location(): void
    {
        Http::fake([
            'https://ipinfo.io/*' => Http::response([
                'city' => 'Mountain View',
                'region' => 'California',
                'country' => 'US',
                'loc' => '37.4056,-122.0775',
                'org' => 'AS15169 Example Network',
                'timezone' => 'America/Los_Angeles',
            ]),
        ]);

        $service = app(IpGeolocationService::class);
        $first = $service->locate('8.8.8.8');
        $second = $service->locate('8.8.8.8');

        $this->assertSame('located', $first['status']);
        $this->assertSame('approximate_ip_network', $first['accuracy']);
        $this->assertSame('Mountain View', $first['city']);
        $this->assertSame(37.4056, $first['latitude']);
        $this->assertSame(-122.0775, $first['longitude']);
        $this->assertSame($first, $second);
        Http::assertSentCount(1);
    }

    public function test_it_never_sends_private_or_reserved_ips_to_the_provider(): void
    {
        Http::fake();

        $location = app(IpGeolocationService::class)->locate('192.168.1.25');

        $this->assertSame('non_public', $location['status']);
        Http::assertNothingSent();
    }

    public function test_scheduled_command_geolocates_suspicious_path_probes(): void
    {
        Http::fake([
            'https://ipinfo.io/*' => Http::response([
                'city' => 'Morelia',
                'region' => 'Michoacán',
                'country' => 'MX',
                'loc' => '19.7060,-101.1950',
                'org' => 'AS64500 Red de prueba',
            ]),
        ]);

        $event = SecurityEvent::create([
            'occurred_at' => now(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'bucket_at' => now()->startOfMinute(),
            'occurrences' => 1,
            'severity' => 'high',
            'category' => 'reconnaissance',
            'event_code' => 'suspicious_path_probe',
            'description' => 'Sonda pendiente de geolocalización',
            'ip_address' => '8.8.4.4',
            'method' => 'GET',
            'path' => '/.env',
            'status_code' => 404,
            'metadata' => [],
            'fingerprint' => hash('sha256', 'geolocate-suspicious-probe-test'),
        ]);

        $this->artisan('security:geolocate-honeypot --limit=1')
            ->expectsOutput('Se geolocalizaron 1 eventos de actividad maliciosa.')
            ->assertExitCode(0);

        $event->refresh();

        $this->assertSame('Morelia', $event->metadata['source_geo']['city']);
        $this->assertSame(19.706, $event->metadata['source_geo']['latitude']);
    }
}
