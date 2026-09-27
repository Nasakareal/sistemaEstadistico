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
            $event = $status !== null ? $this->eventForStatus($status, $request) : null;
            $context = $this->requestContext($request, $exception);

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
                    ] + $context
                );
            } elseif ($event !== null) {
                $this->recorder->record(
                    $event['code'],
                    $event['description'],
                    $event['severity'],
                    $event['category'],
                    $request,
                    ['status_code' => $status, 'exception_class' => get_class($exception)] + $context
                );
            } else {
                $this->recorder->record(
                    'unhandled_exception',
                    'La solicitud provocó una excepción no controlada.',
                    'critical',
                    'application',
                    $request,
                    ['exception_class' => get_class($exception)] + $context
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

        $event = $this->eventForStatus($status, $request);
        $context = $this->requestContext($request, $response);

        if ($event !== null) {
            $this->recorder->record(
                $event['code'],
                $event['description'],
                $event['severity'],
                $event['category'],
                $request,
                ['status_code' => $status] + $context
            );
        }

        return $response;
    }

    private function eventForStatus(int $status, Request $request): ?array
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
        ];

        if ($status === 429) {
            if ($request->user()) {
                return [
                    'code' => 'authenticated_rate_limit_reached',
                    'description' => 'Un usuario autenticado alcanzó el límite compartido de solicitudes API.',
                    'severity' => 'warning',
                    'category' => 'operational',
                ];
            }

            return [
                'code' => 'rate_limit_exceeded',
                'description' => 'Una solicitud no autenticada excedió el límite permitido.',
                'severity' => 'high',
                'category' => 'rate_limit',
            ];
        }

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

    private function requestContext(Request $request, $source = null): array
    {
        $route = $request->route();
        $headers = $source instanceof HttpExceptionInterface
            ? $source->getHeaders()
            : (is_object($source) && isset($source->headers) ? $source->headers->all() : []);

        $header = function (string $name) use ($headers) {
            foreach ($headers as $key => $value) {
                if (strtolower((string) $key) === strtolower($name)) {
                    return is_array($value) ? ($value[0] ?? null) : $value;
                }
            }

            return null;
        };

        $referer = trim((string) $request->headers->get('referer', ''));
        $refererPath = $referer !== '' ? parse_url($referer, PHP_URL_PATH) : null;
        $parameters = [];

        if (is_object($route)) {
            foreach ($route->parameters() as $key => $value) {
                if (is_numeric($value)) {
                    $parameters[$key] = (string) $value;
                } elseif ($value !== null) {
                    $parameters[$key] = '[presente]';
                }
            }
        }

        return [
            'authenticated' => (bool) $request->user(),
            'intent' => $this->intentFor($request),
            'controller_action' => is_object($route) ? $route->getActionName() : null,
            'route_parameters' => $parameters,
            'input_fields' => array_values(array_slice(array_keys($request->all()), 0, 40)),
            'query_fields' => array_values(array_slice(array_keys($request->query()), 0, 40)),
            'referer_path' => is_string($refererPath) ? $refererPath : null,
            'ajax' => $request->ajax(),
            'content_type' => $request->getContentType(),
            'rate_limit' => [
                'limit' => $header('X-RateLimit-Limit'),
                'remaining' => $header('X-RateLimit-Remaining'),
                'retry_after_seconds' => $header('Retry-After'),
            ],
        ];
    }

    private function intentFor(Request $request): string
    {
        $path = '/' . ltrim((string) $request->path(), '/');
        $method = strtoupper((string) $request->method());

        if ($method === 'GET' && $path === '/api/me') {
            return 'Actualizar la sesión, el perfil y los permisos de la aplicación.';
        }

        if ($method === 'GET' && preg_match('#^/api/comunicaciones/conversacion/(\\d+)$#', $path, $matches)) {
            return 'Consultar o actualizar la conversación con el usuario #' . $matches[1] . '.';
        }

        if ($method === 'POST' && $path === '/api/location') {
            return 'Enviar la ubicación actual del dispositivo.';
        }

        if ($method === 'POST' && $path === '/api/location/response-route') {
            return 'Sincronizar puntos de ruta o ubicación acumulados por el dispositivo.';
        }

        $verbs = [
            'GET' => 'Consultar un recurso de la aplicación.',
            'POST' => 'Enviar o crear información en la aplicación.',
            'PUT' => 'Actualizar información existente.',
            'PATCH' => 'Actualizar parcialmente información existente.',
            'DELETE' => 'Solicitar la eliminación de un recurso.',
        ];

        return $verbs[$method] ?? 'Realizar una operación en la aplicación.';
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
