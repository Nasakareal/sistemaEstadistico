<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\LicenciaEmisionController;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LicenciaEmisionAuthorizationTest extends TestCase
{
    public function test_usuario_no_superadmin_no_puede_listar_licencias(): void
    {
        $request = Request::create('/api/licencias-emision', 'GET');
        $request->setUserResolver(function () {
            return new class {
                public function isSuperadmin(): bool
                {
                    return false;
                }
            };
        });

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Solo Superadmin puede emitir y consultar licencias.');

        $this->app->make(LicenciaEmisionController::class)->index($request);
    }
}
