<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConstanciaManejoController;
use App\Models\User;
use App\Services\ConstanciaExamenCuestionarioService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ExamenesImprimiblesAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('constancia_modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function test_lista_examenes_imprimibles_sin_exigir_modulo_asignado(): void
    {
        $usuario = Mockery::mock(User::class)->makePartial();
        $usuario->id = 50;
        $usuario->unidad_id = 1;
        $usuario->constancia_modulo_id = null;
        $usuario->shouldReceive('isSuperadmin')->andReturn(false);
        Auth::setUser($usuario);

        $cuestionarios = Mockery::mock(ConstanciaExamenCuestionarioService::class);
        $cuestionarios->shouldReceive('generar')->times(5)->andReturn(collect());

        $response = (new ConstanciaManejoController($cuestionarios))->examenesImprimibles();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['ok']);
        $this->assertCount(5, $response->getData(true)['data']);
    }
}
