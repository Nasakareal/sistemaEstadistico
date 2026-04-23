<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Redirect;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'No autenticado.',
                ], 401);
            }

            if ($exception instanceof AuthorizationException) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'No autorizado.',
                ], 403);
            }

            if ($this->isHttpException($exception)) {
                return response()->json([
                    'message' => $exception->getMessage() ?: 'Error HTTP ' . $exception->getStatusCode(),
                ], $exception->getStatusCode());
            }
        }

        // Manejo de error 403 - Redirigir a la página de bienvenida
        if ($exception instanceof AuthorizationException) {
            return Redirect::route('welcome');
        }

        // Manejo de error 404 - Página no encontrada
        if ($this->isHttpException($exception) && $exception->getStatusCode() === 404) {
            return response()->view('errors.404', [], 404);
        }

        // Manejo de otros errores HTTP
        if ($this->isHttpException($exception)) {
            $status = $exception->getStatusCode();
            if (view()->exists("errors.{$status}")) {
                return response()->view("errors.{$status}", [], $status);
            }
        }

        return parent::render($request, $exception);
    }
}
