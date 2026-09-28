<?php

namespace Tests\Feature;

use App\Http\Controllers\PersonalController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class PersonalPatrullaCompartidaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'personal_patrulla_compartida_testing');
        config()->set('database.connections.personal_patrulla_compartida_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('personal_patrulla_compartida_testing');
        DB::setDefaultConnection('personal_patrulla_compartida_testing');

        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('patrullas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidad_id');
            $table->string('numero_economico');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        Schema::create('personals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidad_id');
            $table->unsignedBigInteger('destacamento_id')->nullable();
            $table->unsignedBigInteger('patrulla_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('nombre');
            $table->string('curp')->nullable();
            $table->string('numero_seguro_social')->nullable();
            $table->string('cuip')->nullable();
            $table->string('cup')->nullable();
            $table->string('categoria');
            $table->string('estatus');
            $table->text('alergias')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('unidades')->insert(['id' => 5]);
        DB::table('patrullas')->insert([
            'id' => 17,
            'unidad_id' => 5,
            'numero_economico' => '05-017',
            'activa' => true,
        ]);
        DB::table('personals')->insert([
            'unidad_id' => 5,
            'patrulla_id' => 17,
            'nombre' => 'Elemento Uno',
            'categoria' => 'OPERATIVO',
            'estatus' => 'ACTIVO',
        ]);

        $actor = Mockery::mock(User::class)->makePartial();
        $actor->forceFill(['id' => 99, 'unidad_id' => 5]);
        $actor->shouldReceive('hasRole')->andReturnFalse();
        Auth::shouldReceive('user')->andReturn($actor);
    }

    public function test_patrulla_asignada_sigue_disponible_y_puede_asignarse_a_otro_elemento(): void
    {
        $controller = new PersonalController();
        $metodo = new ReflectionMethod($controller, 'patrullasDisponiblesParaActor');
        $metodo->setAccessible(true);

        $patrullas = $metodo->invoke($controller, 5);

        $this->assertSame([17], $patrullas->pluck('id')->all());

        $response = $controller->store(Request::create('/personal', 'POST', [
            'unidad_id' => 5,
            'patrulla_id' => 17,
            'nombre' => 'Elemento Dos',
            'categoria' => 'OPERATIVO',
            'estatus' => 'ACTIVO',
        ]));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertDatabaseCount('personals', 2);
        $this->assertDatabaseHas('personals', [
            'nombre' => 'Elemento Dos',
            'patrulla_id' => 17,
        ]);
    }
}
