<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LimitExternalSync
{
    public function handle(Request $request, Closure $next)
    {
        if (
            !$request->user() ||
            (int) $request->user()->id !== 71 ||
            $request->ip() !== '2.24.67.135' ||
            strtolower($request->userAgent() ?? '') !== 'node' ||
            !$request->isMethod('GET') ||
            !in_array($request->path(), ['api/hechos', 'api/actividades'], true)
        ) {
            return $next($request);
        }

        $key = 'external-sync:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 20)) {
            $retry = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Límite de solicitudes alcanzado.',
                'retry_after' => $retry
            ], 429)->header('Retry-After', $retry);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
