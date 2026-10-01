<?php

namespace Tests\Unit;

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\BlockSuspiciousRequests;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function test_authenticated_api_has_a_separate_capacity_from_guests(): void
    {
        $limiter = RateLimiter::limiter('api');
        $request = Request::create('/api/me', 'GET');
        $request->setUserResolver(fn () => (object) ['id' => 11]);

        $limits = $limiter($request);

        $this->assertIsArray($limits);
        $this->assertSame([180, 3000], array_map(fn (Limit $limit) => $limit->maxAttempts, $limits));
        $this->assertSame(['api-user-minute:11', 'api-user-hour:11'], array_map(fn (Limit $limit) => $limit->key, $limits));
    }

    public function test_login_limiter_also_tracks_the_account_across_ips(): void
    {
        $limiter = RateLimiter::limiter('login');
        $request = Request::create('/api/login', 'POST', ['email' => 'Admin@Example.com'], [], [], [
            'REMOTE_ADDR' => '192.0.2.80',
        ]);

        $limits = $limiter($request);

        $this->assertSame([5, 20, 30], array_map(fn (Limit $limit) => $limit->maxAttempts, $limits));
        $this->assertSame(15, $limits[1]->decayMinutes);
        $this->assertSame(
            'login-account:' . hash('sha256', 'admin@example.com'),
            $limits[1]->key
        );
    }

    public function test_security_headers_are_added_and_hsts_requires_https(): void
    {
        $middleware = new AddSecurityHeaders();
        $http = $middleware->handle(Request::create('/login', 'GET'), fn () => response('ok'));
        $https = $middleware->handle(Request::create('https://example.test/login', 'GET'), fn () => response('ok'));

        $this->assertSame('nosniff', $http->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $http->headers->get('X-Frame-Options'));
        $this->assertStringContainsString(
            "object-src 'none'",
            $http->headers->get('Content-Security-Policy')
        );
        $this->assertStringContainsString(
            "form-action 'self'",
            $http->headers->get('Content-Security-Policy')
        );
        $this->assertStringNotContainsString(
            "'unsafe-eval'",
            $http->headers->get('Content-Security-Policy')
        );
        $this->assertStringNotContainsString(
            'upgrade-insecure-requests',
            $http->headers->get('Content-Security-Policy')
        );
        $this->assertNull($http->headers->get('Strict-Transport-Security'));
        $this->assertStringContainsString(
            'upgrade-insecure-requests',
            $https->headers->get('Content-Security-Policy')
        );
        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $https->headers->get('Strict-Transport-Security')
        );
    }

    public function test_apache_replaces_success_headers_instead_of_duplicating_them(): void
    {
        $htaccess = file_get_contents(base_path('public/.htaccess'));

        $this->assertStringContainsString(
            'Header onsuccess unset X-Content-Type-Options',
            $htaccess
        );
        $this->assertStringContainsString(
            'Header onsuccess unset X-Frame-Options',
            $htaccess
        );
        $this->assertStringContainsString(
            'Header onsuccess unset Referrer-Policy',
            $htaccess
        );
    }

    public function test_known_scanner_paths_are_stopped_before_the_application(): void
    {
        RateLimiter::clear('security-probe:' . hash('sha256', '192.0.2.44'));
        $middleware = new BlockSuspiciousRequests();
        $request = Request::create('/.env', 'GET', [], [], [], ['REMOTE_ADDR' => '192.0.2.44']);
        $nextCalled = false;

        $response = $middleware->handle($request, function () use (&$nextCalled) {
            $nextCalled = true;
            return response('secret');
        });

        $this->assertFalse($nextCalled);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('no-store, private', $response->headers->get('Cache-Control'));
    }

    public function test_normal_paths_continue_through_the_stack(): void
    {
        $middleware = new BlockSuspiciousRequests();
        $request = Request::create('/login', 'GET');

        $response = $middleware->handle($request, fn () => response('login form'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('login form', $response->getContent());
    }

    public function test_probe_text_in_a_query_does_not_block_a_legitimate_path(): void
    {
        $middleware = new BlockSuspiciousRequests();
        $request = Request::create('/busqueda?q=wp-admin', 'GET');

        $response = $middleware->handle($request, fn () => response('results'));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_repeated_probes_from_one_ip_are_rate_limited(): void
    {
        config()->set('security_logging.probe_limit_per_hour', 3);
        $key = 'security-probe:' . hash('sha256', '192.0.2.55');
        RateLimiter::clear($key);
        $middleware = new BlockSuspiciousRequests();

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $request = Request::create('/wp-login.php', 'GET', [], [], [], ['REMOTE_ADDR' => '192.0.2.55']);
            $this->assertSame(404, $middleware->handle($request, fn () => response('unexpected'))->getStatusCode());
        }

        $request = Request::create('/wp-login.php', 'GET', [], [], [], ['REMOTE_ADDR' => '192.0.2.55']);
        $this->assertSame(429, $middleware->handle($request, fn () => response('unexpected'))->getStatusCode());

        RateLimiter::clear($key);
    }
}
