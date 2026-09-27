<?php

namespace App\Http\Middleware;

use App\Services\SecurityEventRecorder;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class LogSecurityEvents
{
    private SecurityEventRecorder $recorder;

    public function __construct(SecurityEventRecorder $recorder)
    {
        $this->recorder = $recorder;
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $status = $this->statusForException($exception);
            $suspiciousPattern = $this->suspiciousPattern($request);
            $event = $status !== null ? $this->eventForStatus($status) : null;

            if ($suspiciousPattern !== null) {
                $this->recorder->record(
                    'suspicious_path_probe',
                    'Solicitud a una ruta asociada con escaneo automatizado.',
                    'high',
                    'reconnaissance',
                    $request,
                    [
                        'status_code' => $status,
                        'matched_pattern' => $suspiciousPattern,
                        'exception_class' => get_class($exception),
                    ]
                );
            } elseif ($event !== null) {
                $this->recorder->record(
                    $event['code'],
                    $event['description'],
                    $event['severity'],
                    $event['category'],
                    $request,
                    ['status_code' => $status, 'exception_class' => get_class($exception)]
                );
            } else {
                $this->recorder->record(
                    'unhandled_exception',
                    'La solicitud provocó una excepción no controlada.',
                    'critical',
                    'application',
                    $request,
                    ['exception_class' => get_class($exception)]
                );
            }

            throw $exception;
        }

        $status = (int) $response->getStatusCode();
        $suspiciousPattern = $this->suspiciousPattern($request);

        if ($suspiciousPattern !== null) {
            $this->recorder->record(
                'suspicious_path_probe',
                'Solicitud a una ruta asociada con escaneo automatizado.',
                'high',
                'reconnaissance',
                $request,
                ['status_code' => $status, 'matched_pattern' => $suspiciousPattern]
            );

            return $response;
        }

        $event = $this->eventForStatus($status);

        if ($event !== null) {
            $this->recorder->record(
                $event['code'],
                $event['description'],
                $event['severity'],
                $event['category'],
                $request,
                ['status_code' => $status]
            );
        }

        return $response;
    }

    private function eventForStatus(int $status): ?array
    {
        $events = [
            401 => [
                'code' => 'authentication_required',
                'description' => 'Intento de acceso sin autenticación válida.',
                'severity' => 'warning',
                'category' => 'authorization',
            ],
            403 => [
                'code' => 'access_denied',
                'description' => 'Intento de acceso a un recurso no autorizado.',
                'severity' => 'high',
                'category' => 'authorization',
            ],
            419 => [
                'code' => 'csrf_mismatch',
                'description' => 'La solicitud falló la validación CSRF o la sesión expiró.',
                'severity' => 'high',
                'category' => 'request_integrity',
            ],
            429 => [
                'code' => 'rate_limit_exceeded',
                'description' => 'La IP excedió el límite de solicitudes permitido.',
                'severity' => 'high',
                'category' => 'rate_limit',
            ],
        ];

        if (isset($events[$status])) {
            return $events[$status];
        }

        if ($status >= 500) {
            return [
                'code' => 'server_error_response',
                'description' => 'La aplicación respondió con un error interno.',
                'severity' => 'critical',
                'category' => 'application',
            ];
        }

        return null;
    }

    private function statusForException(Throwable $exception): ?int
    {
        if ($exception instanceof AuthenticationException) {
            return 401;
        }

        if ($exception instanceof AuthorizationException) {
            return 403;
        }

        if ($exception instanceof TokenMismatchException) {
            return 419;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return null;
    }

    private function suspiciousPattern(Request $request): ?string
    {
        $path = strtolower('/' . ltrim((string) $request->getRequestUri(), '/'));

        for ($i = 0; $i < 2; $i++) {
            $decoded = rawurldecode($path);
            if ($decoded === $path) {
                break;
            }
            $path = $decoded;
        }

        $patterns = [
            '/.env' => '.env',
            '/.git' => '.git',
            'wp-admin' => 'wp-admin',
            'wp-login' => 'wp-login',
            'phpmyadmin' => 'phpmyadmin',
            'pma/' => 'pma',
            'vendor/phpunit' => 'vendor/phpunit',
            '../' => 'path-traversal',
            '..\\' => 'path-traversal',
            '/server-status' => 'server-status',
            '/actuator' => 'actuator',
            '/config.php' => 'config.php',
            '/shell.php' => 'shell.php',
        ];

        foreach ($patterns as $needle => $label) {
            if (strpos($path, $needle) !== false) {
                return $label;
            }
        }

        return null;
    }
}
