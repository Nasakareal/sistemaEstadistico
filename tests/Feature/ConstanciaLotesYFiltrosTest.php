<?php

namespace Tests\Feature;

use App\Http\Controllers\ConstanciaManejoController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ConstanciaLotesYFiltrosTest extends TestCase
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('constancia_modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo');
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('constancias_manejo', function (Blueprint $table) {
            $table->id();
            $table->string('folio');
            $table->string('folio_qr');
            $table->unsignedBigInteger('modulo_id');
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('perito_activador_id')->nullable();
            $table->string('nombre_solicitante')->nullable();
            $table->string('tipo_licencia')->nullable();
            $table->string('tipo_examen')->nullable();
            $table->string('estatus');
            $table->dateTime('fecha_impresion')->nullable();
            $table->dateTime('fecha_expiracion')->nullable();
            $table->string('lote_uuid', 36)->nullable();
            $table->string('qr_token');
            $table->string('acceso_examen_token')->nullable();
            $table->timestamps();
        });

        Schema::create('constancia_examenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('constancia_id');
            $table->string('resultado')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Superadmin de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('constancia_modulos')->insert([
            [
                'id' => 1,
                'nombre' => 'Módulo Siniestros',
                'tipo' => 'SINIESTROS',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'nombre' => 'Módulo Delegaciones',
                'tipo' => 'DELEGACION',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('constancias_manejo')->insert([
            $this->constancia(1, 'S-0001', 1, '11111111-1111-4111-8111-111111111111'),
            $this->constancia(2, 'D-0001', 2, '22222222-2222-4222-8222-222222222222'),
        ]);

        $user = Mockery::mock(User::class)->makePartial();
        $user->id = 1;
        $user->shouldReceive('hasRole')->with('Superadmin')->andReturn(true);
        Auth::setUser($user);
    }

    public function test_superadmin_puede_filtrar_constancias_y_lotes_por_origen(): void
    {
        $view = $this->app->make(ConstanciaManejoController::class)->index(
            Request::create('/constancias-manejo', 'GET', ['tipo_modulo' => 'SINIESTROS'])
        );

        $data = $view->getData();

        $this->assertTrue($data['isSuperadmin']);
        $this->assertSame(1, $data['constancias']->total());
        $this->assertSame('S-0001', $data['constancias']->first()->folio);
        $this->assertSame(1, $data['lotes']->total());
        $this->assertSame('11111111-1111-4111-8111-111111111111', $data['lotes']->first()->lote_uuid);
    }

    public function test_descarga_el_lote_completo_como_pdf(): void
    {
        $response = $this->app->make(ConstanciaManejoController::class)
            ->descargarLote('11111111-1111-4111-8111-111111111111');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString(
            'attachment; filename="lote_constancias_modulo_siniestros_s_0001_a_s_0001.pdf"',
            $response->headers->get('content-disposition')
        );
    }

    private function constancia(int $id, string $folio, int $moduloId, string $loteUuid): array
    {
        return [
            'id' => $id,
            'folio' => $folio,
            'folio_qr' => $folio,
            'modulo_id' => $moduloId,
            'user_id' => 1,
            'nombre_solicitante' => null,
            'tipo_licencia' => null,
            'tipo_examen' => null,
            'estatus' => 'IMPRESA_INACTIVA',
            'fecha_impresion' => now(),
            'lote_uuid' => $loteUuid,
            'qr_token' => $loteUuid,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
