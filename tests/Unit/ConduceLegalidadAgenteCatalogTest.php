<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadAgenteCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');

        Schema::create('personals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('unidad_id');
            $table->string('nombre');
            $table->string('ap_paterno')->nullable();
            $table->string('ap_materno')->nullable();
            $table->string('numero_placa')->nullable();
            $table->string('estatus')->default('ACTIVO');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_catalogo_incluye_solo_personal_activo_de_vialidades_con_placa(): void
    {
        DB::table('personals')->insert([
            [
                'unidad_id' => 5,
                'nombre' => 'Julio Octavio',
                'ap_paterno' => 'Pérez',
                'ap_materno' => 'Hernández',
                'numero_placa' => '09130',
                'estatus' => 'ACTIVO',
            ],
            [
                'unidad_id' => 5,
                'nombre' => 'Agente',
                'ap_paterno' => 'Sin placa',
                'ap_materno' => null,
                'numero_placa' => null,
                'estatus' => 'ACTIVO',
            ],
            [
                'unidad_id' => 1,
                'nombre' => 'Agente',
                'ap_paterno' => 'Siniestros',
                'ap_materno' => null,
                'numero_placa' => '10001',
                'estatus' => 'ACTIVO',
            ],
        ]);

        $method = new ReflectionMethod(
            ConduceLegalidadController::class,
            'agentesVialidadesPayload'
        );
        $method->setAccessible(true);
        $catalogo = $method->invoke(new ConduceLegalidadController());

        $this->assertCount(1, $catalogo);
        $this->assertSame('Julio Octavio Pérez Hernández', $catalogo[0]['nombre_completo']);
        $this->assertSame('09130', $catalogo[0]['numero_placa']);
    }
}
