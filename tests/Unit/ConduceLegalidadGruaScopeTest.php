<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\ConduceLegalidadController;
use App\Models\ConduceLegalidadOperativo;
use App\Models\Grua;
use App\Models\Unidad;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class ConduceLegalidadGruaScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('gruas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
        });
        Schema::create('delegaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
        });
        Schema::create('unidad_grua', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_id');
            $table->unsignedBigInteger('grua_id');
        });
        Schema::create('delegacion_grua', function (Blueprint $table) {
            $table->unsignedBigInteger('delegacion_id');
            $table->unsignedBigInteger('grua_id');
        });

        DB::table('gruas')->insert([
            ['id' => 1, 'nombre' => 'GRÚAS URUAPAN'],
            ['id' => 2, 'nombre' => 'GRÚAS MORELIA'],
            ['id' => 3, 'nombre' => 'GRÚAS SINIESTROS'],
        ]);
        DB::table('unidades')->insert([
            ['id' => 1, 'nombre' => 'Siniestros'],
            ['id' => 2, 'nombre' => 'Delegaciones'],
        ]);
        DB::table('delegaciones')->insert([
            ['id' => 15, 'nombre' => 'Morelia'],
            ['id' => 30, 'nombre' => 'Uruapan'],
        ]);
        DB::table('delegacion_grua')->insert([
            ['delegacion_id' => 30, 'grua_id' => 1],
            ['delegacion_id' => 15, 'grua_id' => 2],
        ]);
        DB::table('unidad_grua')->insert([
            ['unidad_id' => 1, 'grua_id' => 3],
        ]);
    }

    public function test_operativo_de_uruapan_solo_lista_sus_gruas(): void
    {
        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill(['unidad_id' => 2, 'delegacion_id' => 30]);

        $queryMethod = new ReflectionMethod(ConduceLegalidadController::class, 'gruasOperativoQuery');
        $queryMethod->setAccessible(true);
        $query = $queryMethod->invoke(new ConduceLegalidadController(), $operativo);

        $this->assertSame([1], $query->pluck('gruas.id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_operativo_de_vialidades_urbanas_usa_las_gruas_de_siniestros(): void
    {
        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill(['unidad_id' => 5, 'delegacion_id' => null]);

        $queryMethod = new ReflectionMethod(ConduceLegalidadController::class, 'gruasOperativoQuery');
        $queryMethod->setAccessible(true);
        $query = $queryMethod->invoke(new ConduceLegalidadController(), $operativo);

        $this->assertSame([3], $query->pluck('gruas.id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_catalogo_de_vialidades_no_es_descartado_por_la_app_instalada(): void
    {
        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill(['unidad_id' => 5]);

        $grua = new Grua();
        $grua->forceFill(['id' => 3, 'nombre' => 'GRÚAS SINIESTROS']);
        $unidadSiniestros = new Unidad();
        $unidadSiniestros->forceFill(['id' => 1, 'nombre' => 'Siniestros']);
        $grua->setRelation('unidades', collect([$unidadSiniestros]));

        $payloadMethod = new ReflectionMethod(ConduceLegalidadController::class, 'marcarGruasComoVialidadesUrbanas');
        $payloadMethod->setAccessible(true);
        $payloadMethod->invoke(new ConduceLegalidadController(), collect([$grua]), $operativo);

        $this->assertSame([1, 5], $grua->unidades->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_rechaza_grua_de_otra_delegacion_en_la_alimentacion(): void
    {
        $operativo = new ConduceLegalidadOperativo();
        $operativo->forceFill(['unidad_id' => 2, 'delegacion_id' => 30]);

        $assertMethod = new ReflectionMethod(ConduceLegalidadController::class, 'assertGruasCorrespondenOperativo');
        $assertMethod->setAccessible(true);

        try {
            $assertMethod->invoke(new ConduceLegalidadController(), [
                'vehiculos' => [['grua_id' => 2, 'corralon_id' => 1]],
            ], $operativo);
            $this->fail('Se esperaba que rechazara la grúa de otra delegación.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('vehiculos.0.grua_id', $e->errors());
        }
    }
}
