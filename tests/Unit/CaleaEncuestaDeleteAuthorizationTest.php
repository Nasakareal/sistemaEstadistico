<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\CaleaEncuestaController;
use App\Models\User;
use Illuminate\Http\Request;
use ReflectionMethod;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CaleaEncuestaDeleteAuthorizationTest extends TestCase
{
    public function test_only_superadmin_can_delete_calea_surveys(): void
    {
        $method = new ReflectionMethod(CaleaEncuestaController::class, 'superadmin');
        $method->setAccessible(true);
        $controller = new CaleaEncuestaController();

        $superadmin = new class extends User {
            public function isSuperadmin(): bool { return true; }
        };
        $allowed = Request::create('/api/calea-encuestas/1', 'DELETE');
        $allowed->setUserResolver(fn () => $superadmin);
        $this->assertSame($superadmin, $method->invoke($controller, $allowed));

        $administrador = new class extends User {
            public function isSuperadmin(): bool { return false; }
        };
        $denied = Request::create('/api/calea-encuestas/1', 'DELETE');
        $denied->setUserResolver(fn () => $administrador);

        try {
            $method->invoke($controller, $denied);
            $this->fail('Administrador no debe poder eliminar encuestas CALEA.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame(
                'Sólo Superadmin puede eliminar encuestas CALEA.',
                $exception->getMessage()
            );
        }
    }
}
