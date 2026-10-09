<?php

namespace Tests\Unit;

use App\Services\AseguramientosResumenService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AseguramientosResumenConduceLegalidadTest extends TestCase
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
        DB::connection()->getPdo()->sqliteCreateFunction(
            'TIMESTAMP',
            fn ($fecha, $hora) => trim((string) $fecha . ' ' . (string) $hora),
            2
        );

        $this->crearEsquema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->conexionOriginal);
        parent::tearDown();
    }

    public function test_cuenta_motocicleta_resguardada_desde_conduce_legalidad(): void
    {
        DB::table('unidades')->insert([
            'id' => 5,
            'nombre' => 'PROTECCION EN VIALIDADES URBANAS',
            'slug' => 'vialidades-urbanas',
        ]);
        DB::table('conduce_legalidad_operativos')->insert([
            [
                'id' => 10,
                'nombre' => 'Operativo conduce con legalidad',
                'tipo_operativo' => 'conduce_legalidad',
                'fecha' => '2026-10-08',
                'hora_inicio' => '09:00:00',
                'unidad_id' => 5,
            ],
            [
                'id' => 11,
                'nombre' => 'Alcoholimetria',
                'tipo_operativo' => 'alcoholimetria',
                'fecha' => '2026-10-08',
                'hora_inicio' => '09:00:00',
                'unidad_id' => 5,
            ],
        ]);
        DB::table('conduce_legalidad_capturas')->insert([
            [
                'id' => 100,
                'operativo_id' => 10,
                'unidad_id' => 5,
                'fecha' => '2026-10-08',
                'hora' => '10:00:00',
                'municipio' => 'Morelia',
            ],
            [
                'id' => 101,
                'operativo_id' => 10,
                'unidad_id' => 5,
                'fecha' => '2026-10-08',
                'hora' => '11:00:00',
                'municipio' => 'Morelia',
            ],
            [
                'id' => 102,
                'operativo_id' => 11,
                'unidad_id' => 5,
                'fecha' => '2026-10-08',
                'hora' => '12:00:00',
                'municipio' => 'Morelia',
            ],
        ]);
        DB::table('conduce_legalidad_vehiculos')->insert([
            [
                'captura_id' => 100,
                'tipo' => 'Motocicleta',
                'marca' => 'Honda',
                'retencion_vehiculo' => 1,
                'motivo_retencion' => 'Resguardo por falta de documentos',
            ],
            [
                'captura_id' => 101,
                'tipo' => 'Motocicleta',
                'marca' => 'Italika',
                'retencion_vehiculo' => 0,
                'motivo_retencion' => null,
            ],
            [
                'captura_id' => 102,
                'tipo' => 'Automovil',
                'marca' => null,
                'retencion_vehiculo' => 1,
                'motivo_retencion' => null,
            ],
        ]);

        $usuario = new class {
            public int $unidad_id = 5;
            public $delegacion_id = null;
            public $destacamento_id = null;

            public function hasRole(string $role): bool
            {
                return false;
            }
        };

        $resumen = app(AseguramientosResumenService::class)->generar([
            'desde' => '2026-10-08',
            'hora_desde' => '00:00',
            'hasta' => '2026-10-08',
            'hora_hasta' => '23:59',
            'unidad_id' => 5,
        ], $usuario);

        $this->assertSame(1, $resumen['fuentes']['conduce_legalidad']);
        $this->assertSame(1, $resumen['kpis']['puestas']['value']);
        $this->assertSame(1, $resumen['kpis']['vehiculos']['value']);
        $this->assertSame(1, $resumen['vehiculos']['resguardo_conduce']);
        $this->assertSame(1, $resumen['vehiculos']['tipos']['MOTOCICLETA']);
        $this->assertSame(100, $resumen['detalles']['vehiculos.resguardo_conduce'][0]['captura_id']);
    }

    private function crearEsquema(): void
    {
        Schema::create('unidades', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('slug')->nullable();
        });
        Schema::create('delegaciones', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre')->nullable();
        });
        Schema::create('destacamentos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre')->nullable();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedBigInteger('destacamento_id')->nullable();
        });
        Schema::create('conduce_legalidad_operativos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('tipo_operativo');
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
        });
        Schema::create('conduce_legalidad_capturas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('operativo_id');
            $table->unsignedBigInteger('actividad_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('municipio')->nullable();
            $table->string('lugar')->nullable();
            $table->text('narrativa')->nullable();
            $table->text('observaciones')->nullable();
        });
        Schema::create('conduce_legalidad_vehiculos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('captura_id');
            $table->string('tipo')->nullable();
            $table->string('tipo_general')->nullable();
            $table->string('marca')->nullable();
            $table->string('linea')->nullable();
            $table->string('placas')->nullable();
            $table->string('serie')->nullable();
            $table->boolean('retencion_vehiculo')->default(false);
            $table->text('motivo_retencion')->nullable();
            $table->unsignedBigInteger('grua_id')->nullable();
            $table->unsignedBigInteger('corralon_id')->nullable();
            $table->string('grua')->nullable();
            $table->string('corralon')->nullable();
        });
    }
}
