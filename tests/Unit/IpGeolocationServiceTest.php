<?php

namespace Tests\Unit;

use App\Services\IpGeolocationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IpGeolocationServiceTest extends TestCase
{
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
}
