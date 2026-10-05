<?php

namespace Tests\Unit;

use App\Models\ConstanciaModulo;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ConstanciaModuloAssignmentTest extends TestCase
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
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::table('constancia_modulos')->insert([
            ['id' => 1, 'nombre' => 'Macro Modulo', 'tipo' => 'SINIESTROS', 'delegacion_id' => null, 'activo' => true],
            ['id' => 2, 'nombre' => 'Capuchinas', 'tipo' => 'SINIESTROS', 'delegacion_id' => null, 'activo' => true],
            ['id' => 3, 'nombre' => 'Delegacion Uruapan', 'tipo' => 'DELEGACION', 'delegacion_id' => 9, 'activo' => true],
        ]);
    }

    public function test_usuario_de_siniestros_solo_recibe_su_modulo_asignado(): void
    {
        $usuario = $this->usuario(1, 2);

        $this->assertSame(
            [2],
            ConstanciaModulo::permitidosPara($usuario)->pluck('id')->all()
        );
    }

    public function test_usuario_de_siniestros_sin_asignacion_no_recibe_modulos(): void
    {
        $usuario = $this->usuario(1, null);

        $this->assertSame([], ConstanciaModulo::permitidosPara($usuario)->pluck('id')->all());
    }

    public function test_usuario_de_delegaciones_conserva_el_modulo_de_su_delegacion(): void
    {
        $usuario = $this->usuario(2, null, 9);

        $this->assertSame(
            [3],
            ConstanciaModulo::permitidosPara($usuario)->pluck('id')->all()
        );
    }

    private function usuario(int $unidadId, ?int $moduloId, ?int $delegacionId = null): User
    {
        $usuario = Mockery::mock(User::class)->makePartial();
        $usuario->id = 50;
        $usuario->unidad_id = $unidadId;
        $usuario->delegacion_id = $delegacionId;
        $usuario->constancia_modulo_id = $moduloId;
        $usuario->shouldReceive('isSuperadmin')->andReturn(false);

        return $usuario;
    }
}
