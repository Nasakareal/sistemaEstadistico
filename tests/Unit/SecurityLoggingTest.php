<?php

namespace Tests\Unit;

use App\Http\Middleware\LogSecurityEvents;
use App\Models\SecurityEvent;
use App\Services\SecurityEventRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class SecurityLoggingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_recorder_groups_repeated_events_and_redacts_sensitive_metadata(): void
    {
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.0.2.25',
            'HTTP_USER_AGENT' => 'Security logging test',
        ]);
        $recorder = app(SecurityEventRecorder::class);

        $metadata = [
            'status_code' => 401,
            'password' => 'never-store-this',
            'nested' => ['access_token' => 'never-store-this-either'],
            'safe' => 'visible',
        ];

        $recorder->record('test_login_failed', 'Prueba', 'high', 'authentication', $request, $metadata);
        $recorder->record('test_login_failed', 'Prueba', 'high', 'authentication', $request, $metadata);

        $event = SecurityEvent::where('event_code', 'test_login_failed')->firstOrFail();

        $this->assertSame(2, $event->occurrences);
        $this->assertSame('192.0.2.25', $event->ip_address);
        $this->assertSame('[REDACTED]', $event->metadata['password']);
        $this->assertSame('[REDACTED]', $event->metadata['nested']['access_token']);
        $this->assertSame('visible', $event->metadata['safe']);
    }

    public function test_middleware_marks_common_scanner_paths_as_high_severity(): void
    {
        $recorder = Mockery::mock(SecurityEventRecorder::class);
        $recorder->shouldReceive('record')
            ->once()
            ->withArgs(function ($code, $description, $severity, $category, $request, $metadata) {
                return $code === 'suspicious_path_probe'
                    && $severity === 'high'
                    && $category === 'reconnaissance'
                    && $metadata['matched_pattern'] === '.env'
                    && $metadata['status_code'] === 404;
            });

        $middleware = new LogSecurityEvents($recorder);
        $request = Request::create('/.env', 'GET');
        $response = $middleware->handle($request, fn () => response('No encontrado', 404));

        $this->assertSame(404, $response->getStatusCode());
    }

    public function test_identity_is_masked_before_it_reaches_the_log(): void
    {
        $recorder = app(SecurityEventRecorder::class);

        $this->assertSame('us*****@example.com', $recorder->maskedIdentity('usuario@example.com'));
        $this->assertSame('******7890', $recorder->maskedIdentity('1234567890'));
    }

    public function test_middleware_classifies_authorization_exceptions_as_access_denied(): void
    {
        $recorder = Mockery::mock(SecurityEventRecorder::class);
        $recorder->shouldReceive('record')
            ->once()
            ->withArgs(function ($code, $description, $severity, $category, $request, $metadata) {
                return $code === 'access_denied'
                    && $severity === 'high'
                    && $category === 'authorization'
                    && $metadata['status_code'] === 403
                    && $metadata['exception_class'] === AuthorizationException::class;
            });

        $middleware = new LogSecurityEvents($recorder);
        $request = Request::create('/admin/settings/security-events', 'GET');

        try {
            $middleware->handle($request, function () {
                throw new AuthorizationException('No autorizado');
            });
            $this->fail('La excepción debía propagarse.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('No autorizado', $exception->getMessage());
        }
    }

    public function test_authenticated_rate_limit_is_operational_and_records_safe_context(): void
    {
        $recorder = Mockery::mock(SecurityEventRecorder::class);
        $recorder->shouldReceive('record')
            ->once()
            ->withArgs(function ($code, $description, $severity, $category, $request, $metadata) {
                return $code === 'authenticated_rate_limit_reached'
                    && $severity === 'warning'
                    && $category === 'operational'
                    && $metadata['authenticated'] === true
                    && $metadata['intent'] === 'Actualizar la sesión, el perfil y los permisos de la aplicación.'
                    && $metadata['rate_limit']['limit'] === '60'
                    && $metadata['rate_limit']['retry_after_seconds'] === '12';
            });

        $middleware = new LogSecurityEvents($recorder);
        $request = Request::create('/api/me', 'GET');
        $request->setUserResolver(fn () => new class {
            public function getAuthIdentifier()
            {
                return 11;
            }
        });

        $response = response('Límite excedido', 429, [
            'X-RateLimit-Limit' => '60',
            'Retry-After' => '12',
        ]);

        $middleware->handle($request, fn () => $response);
    }

    public function test_known_legacy_client_rejections_do_not_pollute_security_events(): void
    {
        $recorder = Mockery::mock(SecurityEventRecorder::class);
        $recorder->shouldNotReceive('record');
        $middleware = new LogSecurityEvents($recorder);

        $cases = [
            ['GET', '/api/app/version', 401],
            ['GET', '/api/agente-upec-home/filtros', 403],
            ['GET', '/api/estadisticas-actividades/catalogos/unidades', 403],
            ['GET', '/api/estadisticas-actividades/catalogos/delegaciones', 403],
            ['POST', '/api/whatsapp/webhook', 503],
        ];

        foreach ($cases as [$method, $path, $status]) {
            $request = Request::create($path, $method);
            $response = response('', $status);

            $handled = $middleware->handle($request, fn () => $response);

            $this->assertSame($status, $handled->getStatusCode());
        }
    }

    public function test_records_when_server_delivers_authorized_siniestros_menu(): void
    {
        $recorder = Mockery::mock(SecurityEventRecorder::class);
        $recorder->shouldReceive('record')
            ->once()
            ->withArgs(function ($code, $description, $severity, $category, $request, $metadata) {
                return $code === 'menu_siniestros_authorized_delivered'
                    && $severity === 'info'
                    && $category === 'interface_integrity'
                    && $metadata['server_authorized'] === true
                    && $metadata['delivered'] === true;
            });

        $middleware = new LogSecurityEvents($recorder);
        $request = Request::create('/home', 'GET');
        $request->setUserResolver(fn () => new class {
            public $unidad_id = 2;

            public function can($permission): bool
            {
                return $permission === 'ver hechos';
            }

            public function getAuthIdentifier(): int
            {
                return 99;
            }
        });
        $response = response('<li id="menuSiniestros">Siniestros</li>', 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);

        $handled = $middleware->handle($request, fn () => $response);

        $this->assertSame(200, $handled->getStatusCode());
    }

    public function test_historical_events_can_explain_probable_endpoint_intent(): void
    {
        $profileCheck = new SecurityEvent(['method' => 'GET', 'path' => '/api/me']);
        $conversation = new SecurityEvent(['method' => 'GET', 'path' => '/api/comunicaciones/conversacion/3']);
        $location = new SecurityEvent(['method' => 'POST', 'path' => '/api/location']);

        $this->assertStringContainsString('sesión', $profileCheck->intentLabel());
        $this->assertStringContainsString('#3', $conversation->intentLabel());
        $this->assertStringContainsString('ubicación actual', $location->intentLabel());
    }

    public function test_authentication_subscriber_is_registered(): void
    {
        $this->assertTrue(Event::hasListeners(Login::class));
    }
}
