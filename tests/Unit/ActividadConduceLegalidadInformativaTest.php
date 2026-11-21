<?php

namespace Tests\Unit;

use App\Models\Actividad;
use App\Models\ActividadSubcategoria;
use App\Services\ActividadConduceLegalidadSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActividadConduceLegalidadInformativaTest extends TestCase
{
    private string $conexionOriginal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->conexionOriginal = (string) config('database.default');
        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('actividad_subcategorias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actividad_categoria_id')->nullable();
            $table->string('nombre');
            $table->string('slug')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('actividades', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actividad_subcategoria_id')->nullable();
            $table->timestamps();
        });
        Schema::create('conduce_legalidad_capturas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actividad_id')->nullable();
            $table->unsignedBigInteger('operativo_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->conexionOriginal);
        parent::tearDown();
    }

    public function test_actividad_informativa_no_crea_captura_del_operativo(): void
    {
        $subcategoria = ActividadSubcategoria::query()->create([
            'nombre' => 'CONDUCE CON LEGALIDAD',
            'slug' => 'conduce-con-legalidad',
            'activo' => true,
        ]);
        $actividad = Actividad::query()->create([
            'actividad_subcategoria_id' => $subcategoria->id,
        ]);

        $captura = app(ActividadConduceLegalidadSyncService::class)->sync($actividad);

        $this->assertNull($captura);
        $this->assertDatabaseCount('conduce_legalidad_capturas', 0);
    }
}
