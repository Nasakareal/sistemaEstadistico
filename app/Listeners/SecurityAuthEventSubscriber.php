<?php

namespace App\Listeners;

use App\Services\SecurityEventRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;

class SecurityAuthEventSubscriber
{
    private SecurityEventRecorder $recorder;

    public function __construct(SecurityEventRecorder $recorder)
    {
        $this->recorder = $recorder;
    }

    public function handleLogin(Login $event): void
    {
        $this->recorder->record(
            'login_succeeded',
            'Inicio de sesión correcto.',
            'info',
            'authentication',
            $this->request(),
            [
                'guard' => $event->guard,
                'remember' => (bool) $event->remember,
                'user_id' => $event->user ? (int) $event->user->getAuthIdentifier() : null,
            ]
        );
    }

    public function handleFailed(Failed $event): void
    {
        $identity = $event->credentials['email']
            ?? $event->credentials['username']
            ?? null;

        $this->recorder->record(
            'login_failed',
            'Intento de inicio de sesión con credenciales inválidas.',
            'high',
            'authentication',
            $this->request(),
            [
                'guard' => $event->guard,
                'identity_masked' => $this->recorder->maskedIdentity($identity),
                'identity_hash' => $identity ? hash('sha256', mb_strtolower(trim((string) $identity))) : null,
            ]
        );
    }

    public function handleLogout(Logout $event): void
    {
        $this->recorder->record(
            'logout',
            'Cierre de sesión.',
            'info',
            'authentication',
            $this->request(),
            [
                'guard' => $event->guard,
                'user_id' => $event->user ? (int) $event->user->getAuthIdentifier() : null,
            ]
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $this->recorder->record(
            'login_lockout',
            'Inicio de sesión bloqueado temporalmente por demasiados intentos.',
            'critical',
            'authentication',
            $event->request,
            ['identity_masked' => $this->recorder->maskedIdentity($event->request->input('email'))]
        );
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            Lockout::class => 'handleLockout',
        ];
    }

    private function request(): ?Request
    {
        return app()->bound('request') ? request() : null;
    }
}
