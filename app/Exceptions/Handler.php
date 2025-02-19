<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
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
