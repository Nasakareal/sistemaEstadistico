<?php

namespace Tests\Unit;

use App\Http\Controllers\EstadisticasCarreterasController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EstadisticasCarreterasScopeTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = (string) config('database.default');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->originalConnection);
        parent::tearDown();
    }

    public function test_superadmin_solo_recibe_datos_de_carreteras(): void
    {
        $carreterasId = DB::table('unidades')->insertGetId(['nombre' => 'CARRETERAS', 'slug' => 'carreteras']);
        $delegacionesId = DB::table('unidades')->insertGetId(['nombre' => 'DELEGACIONES', 'slug' => 'delegaciones']);

        DB::table('actividades')->insert([
            ['unidad_org_id' => $carreterasId, 'cantidad' => 7, 'fecha' => '2026-10-01'],
            ['unidad_org_id' => $delegacionesId, 'cantidad' => 900, 'fecha' => '2026-10-01'],
        ]);
        DB::table('operativo_dispositivos')->insert([
            ['unidad_org_id' => $carreterasId, 'estado_revision' => 'aprobado', 'fecha' => '2026-10-01'],
            ['unidad_org_id' => $delegacionesId, 'estado_revision' => 'aprobado', 'fecha' => '2026-10-01'],
        ]);
        DB::table('puestas_disposicion')->insert([
            ['unidad_id' => $carreterasId, 'fecha_puesta' => '2026-10-01', 'nombre_policia' => 'ELEMENTO CARRETERAS', 'tipo_puesta' => 'PERSONA', 'motivo' => 'DELITO'],
            ['unidad_id' => $delegacionesId, 'fecha_puesta' => '2026-10-01', 'nombre_policia' => 'ELEMENTO DELEGACIONES', 'tipo_puesta' => 'PERSONA', 'motivo' => 'DELITO'],
        ]);

        $user = new class {
            public int $id = 1;
            public int $unidad_id = 0;
            public ?int $delegacion_id = null;
            public ?int $destacamento_id = null;
            public function hasRole(string $role): bool { return $role === 'Superadmin'; }
        };
        $request = Request::create('/estadisticas-carreteras/kpis', 'GET', ['cache_ttl' => 0]);
        $request->setUserResolver(fn () => $user);

        $data = (new EstadisticasCarreterasController())->kpis($request)->getData(true);

        $this->assertSame(7, $data['totales']['actividades']);
        $this->assertSame(1, $data['totales']['operativos']);
        $this->assertSame(1, $data['totales']['puestas_disposicion']);
        $this->assertSame('ELEMENTO CARRETERAS', $data['top']['elemento'][0]['label']);
    }

    private function createSchema(): void
    {
        Schema::create('unidades', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->unique();
        });
        Schema::create('actividades', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('unidad_org_id');
            $table->unsignedInteger('cantidad')->default(0);
            $table->date('fecha')->nullable();
        });
        Schema::create('operativo_dispositivos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('unidad_org_id');
            $table->string('estado_revision');
            $table->date('fecha')->nullable();
        });
        Schema::create('puestas_disposicion', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('unidad_id');
            $table->date('fecha_puesta')->nullable();
            $table->string('nombre_policia')->nullable();
            $table->string('tipo_puesta')->nullable();
            $table->string('motivo')->nullable();
        });
        Schema::create('puestas_disposicion_personas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('puesta_disposicion_id');
            $table->string('calidad')->nullable();
        });
        Schema::create('puestas_disposicion_vehiculos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('puesta_disposicion_id');
        });
        Schema::create('puestas_disposicion_objetos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('puesta_disposicion_id');
            $table->string('tipo_objeto')->nullable();
        });
    }
}
