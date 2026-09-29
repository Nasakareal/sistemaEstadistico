<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class BlockSuspiciousRequests
{
    private const PROBE_PATTERNS = [
        '/.env',
        '/.git',
        '/.aws',
        '/.ssh',
        'wp-admin',
        'wp-login',
        'wp-content',
        'xmlrpc.php',
        'phpmyadmin',
        'pma/',
        'vendor/phpunit',
        '../',
        '..\\',
        '/server-status',
        '/actuator',
        '/cgi-bin',
        '/etc/passwd',
        '/config.php',
        '/shell.php',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!$this->isProbe($request)) {
            return $next($request);
        }

        $key = 'security-probe:' . hash('sha256', (string) $request->ip());
        $maxAttempts = (int) config('security_logging.probe_limit_per_hour', 10);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response('Demasiadas solicitudes.', 429, [
                'Retry-After' => (string) RateLimiter::availableIn($key),
                'Cache-Control' => 'no-store, private',
            ]);
        }

        RateLimiter::hit($key, 3600);

        // Do not reveal whether a scanned resource exists or which stack is in use.
        return response('No encontrado.', 404, [
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    private function isProbe(Request $request): bool
    {
        $path = strtolower('/' . ltrim((string) $request->getPathInfo(), '/'));

        for ($i = 0; $i < 2; $i++) {
            $decoded = rawurldecode($path);
            if ($decoded === $path) {
                break;
            }
            $path = $decoded;
        }

        foreach (self::PROBE_PATTERNS as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
