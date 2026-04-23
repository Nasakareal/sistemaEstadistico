<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerificarUnidad
{
    public function handle(Request $request, Closure $next, ...$slugs)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'No autenticado.');
        }

        if ($user->isSuperadmin()) {
            return $next($request);
        }

        if ((int) ($user->unidad_id ?? 0) === 3) {
            return $next($request);
        }

        if (!$user->tieneUnidad()) {
            abort(403, 'Tu usuario no tiene una unidad asignada.');
        }

        if (empty($slugs)) {
            return $next($request);
        }

        if (!$user->perteneceAAlgunaUnidad($slugs)) {
            abort(403, 'No tienes acceso a este módulo.');
        }

        return $next($request);
    }
}
