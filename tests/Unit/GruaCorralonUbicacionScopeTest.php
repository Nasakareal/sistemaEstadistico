<?php

namespace Tests\Unit;

use App\Models\Grua;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GruaCorralonUbicacionScopeTest extends TestCase
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
            $table->text('direccion')->nullable();
            $table->text('ubicacion_corralon')->nullable();
            $table->timestamps();
        });
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });
        Schema::create('delegaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });
        Schema::create('unidad_grua', function (Blueprint $table) {
            $table->unsignedBigInteger('unidad_id');
            $table->unsignedBigInteger('grua_id');
            $table->text('ubicacion_corralon')->nullable();
            $table->timestamps();
        });
        Schema::create('delegacion_grua', function (Blueprint $table) {
            $table->unsignedBigInteger('delegacion_id');
            $table->unsignedBigInteger('grua_id');
            $table->text('ubicacion_corralon')->nullable();
            $table->timestamps();
        });

        DB::table('gruas')->insert([
            'id' => 10,
            'nombre' => 'GRÚAS MICHOACÁN',
            'direccion' => 'OFICINAS CENTRALES',
            'ubicacion_corralon' => 'UBICACIÓN ANTERIOR',
        ]);
        DB::table('unidades')->insert(['id' => 1, 'nombre' => 'Siniestros']);
        DB::table('delegaciones')->insert([
            ['id' => 30, 'nombre' => 'Uruapan'],
            ['id' => 31, 'nombre' => 'Pátzcuaro'],
        ]);
        DB::table('unidad_grua')->insert([
            'unidad_id' => 1,
            'grua_id' => 10,
            'ubicacion_corralon' => 'CORRALÓN MORELIA',
        ]);
        DB::table('delegacion_grua')->insert([
            [
                'delegacion_id' => 30,
                'grua_id' => 10,
                'ubicacion_corralon' => 'CORRALÓN URUAPAN',
            ],
            [
                'delegacion_id' => 31,
                'grua_id' => 10,
                'ubicacion_corralon' => 'CORRALÓN PÁTZCUARO',
            ],
        ]);
    }

    public function test_resuelve_una_ubicacion_distinta_para_cada_ambito(): void
    {
        $grua = Grua::with(['unidades', 'delegaciones'])->findOrFail(10);

        $this->assertSame('CORRALÓN MORELIA', $grua->ubicacionCorralonPara(1));
        $this->assertSame('CORRALÓN MORELIA', $grua->ubicacionCorralonPara(5));
        $this->assertSame('CORRALÓN URUAPAN', $grua->ubicacionCorralonPara(2, 30));
        $this->assertSame('CORRALÓN PÁTZCUARO', $grua->ubicacionCorralonPara(2, 31));
    }

    public function test_conserva_la_ubicacion_general_como_respaldo(): void
    {
        $grua = Grua::findOrFail(10);

        $this->assertSame('UBICACIÓN ANTERIOR', $grua->ubicacionCorralonPara(3));
    }
}
