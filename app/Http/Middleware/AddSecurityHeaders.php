<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        $contentSecurityPolicy = trim((string) config('security_headers.content_security_policy', ''));
        if ($contentSecurityPolicy !== '') {
            if ($request->isSecure()) {
                $contentSecurityPolicy = rtrim($contentSecurityPolicy, "; \t\n\r\0\x0B")
                    . '; upgrade-insecure-requests';
            }

            $response->headers->set(
                config('security_headers.csp_report_only', false)
                    ? 'Content-Security-Policy-Report-Only'
                    : 'Content-Security-Policy',
                $contentSecurityPolicy
            );
        }

        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
