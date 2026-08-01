<?php

namespace Tests\Feature;

use App\Http\Controllers\DelegacionController;
use App\Http\Controllers\MapaDelegacionesController;
use App\Models\Delegacion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DelegacionDireccionCompletaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('delegaciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->nullable();
            $table->string('nombre');
            $table->string('municipio')->nullable();
            $table->string('direccion_completa', 500)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->boolean('activa')->default(true);
            $table->foreignId('delegacion_padre_id')->nullable()->constrained('delegaciones');
            $table->timestamps();
        });
    }

    public function test_guarda_y_publica_la_direccion_completa_de_la_delegacion_y_sus_hijas(): void
    {
        $request = Request::create('/delegaciones', 'POST', [
            'clave' => 'D01',
            'nombre' => 'Delegación Centro',
            'municipio' => 'Morelia',
            'direccion_completa' => 'Av. Acueducto 123, Col. Centro, C.P. 58000',
            'lat' => '19.7023000',
            'lng' => '-101.1921000',
            'activa' => '1',
            'hijas' => [[
                'clave' => 'H01',
                'nombre' => 'Subdelegación Norte',
                'municipio' => 'Morelia',
                'direccion_completa' => 'Calle Norte 45, Col. Industrial, C.P. 58130',
                'lat' => '19.7300000',
                'lng' => '-101.1900000',
                'activa' => '1',
            ]],
        ]);

        $response = $this->app->make(DelegacionController::class)->store($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('delegaciones.index'), $response->getTargetUrl());

        $padre = Delegacion::query()->where('clave', 'D01')->firstOrFail();
        $hija = Delegacion::query()->where('clave', 'H01')->firstOrFail();

        $this->assertSame('Av. Acueducto 123, Col. Centro, C.P. 58000', $padre->direccion_completa);
        $this->assertSame('Calle Norte 45, Col. Industrial, C.P. 58130', $hija->direccion_completa);
        $this->assertEquals($padre->id, $hija->delegacion_padre_id);

        $mapa = $this->app->make(MapaDelegacionesController::class)->data()->getData(true);

        $this->assertContains([
            'id' => $padre->id,
            'delegacion_padre_id' => null,
            'clave' => 'D01',
            'nombre' => 'Delegación Centro',
            'municipio' => 'Morelia',
            'direccion_completa' => 'Av. Acueducto 123, Col. Centro, C.P. 58000',
            'lat' => '19.7023000',
            'lng' => '-101.1921000',
            'color' => '#2563eb',
        ], $mapa);

        $this->assertSame(
            'Calle Norte 45, Col. Industrial, C.P. 58130',
            collect($mapa)->firstWhere('id', $hija->id)['direccion_completa']
        );
    }
}
