<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\AuthController;
use App\Models\Personal;
use App\Models\PersonalFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use ReflectionMethod;
use Tests\TestCase;

class AuthControllerPersonalPhotoTest extends TestCase
{
    public function test_it_builds_a_valid_temporary_url_for_the_latest_personal_photo(): void
    {
        $personal = new Personal();
        $personal->setAttribute('id', 41);

        $foto = new PersonalFoto([
            'ruta' => 'personal/fotos/usuario.jpg',
        ]);
        $foto->setAttribute('id', 73);
        $personal->setRelation('fotoPrincipal', $foto);

        $method = new ReflectionMethod(AuthController::class, 'fotoPersonalUrl');
        $method->setAccessible(true);

        $url = $method->invoke(new AuthController(), $personal);

        $this->assertNotNull($url);
        $this->assertStringContainsString(
            '/personal-fotos/73/archivo-temporal',
            $url
        );
        $this->assertTrue(
            URL::hasValidSignature(Request::create($url, 'GET'))
        );
    }

    public function test_it_returns_null_when_personal_has_no_photo(): void
    {
        $personal = new Personal();
        $personal->setAttribute('id', 41);
        $personal->setRelation('fotoPrincipal', null);

        $method = new ReflectionMethod(AuthController::class, 'fotoPersonalUrl');
        $method->setAccessible(true);

        $url = $method->invoke(new AuthController(), $personal);

        $this->assertNull($url);
    }
}
