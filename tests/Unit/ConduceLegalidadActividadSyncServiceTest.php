<?php

namespace Tests\Unit;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadSubcategoria;
use App\Models\ConduceLegalidadCaptura;
use App\Models\ConduceLegalidadOperativo;
use App\Models\LicenciaPuntoInfraccion;
use App\Services\ConduceLegalidadActividadSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConduceLegalidadActividadSyncServiceTest extends TestCase
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

        $this->crearEsquema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        config()->set('database.default', $this->conexionOriginal);
        parent::tearDown();
    }

    public function test_crea_actualiza_y_elimina_una_sola_actividad_por_captura(): void
    {
        $categoria = ActividadCategoria::query()->create([
            'nombre' => 'OPERATIVOS',
            'slug' => 'operativos',
            'activo' => true,
        ]);
        ActividadSubcategoria::query()->create([
            'actividad_categoria_id' => $categoria->id,
            'nombre' => 'CONDUCE CON LEGALIDAD',
            'slug' => 'conduce-con-legalidad',
            'activo' => true,
        ]);
        $operativo = ConduceLegalidadOperativo::query()->create([
            'nombre' => 'Operativo conduce con legalidad',
            'tipo_operativo' => 'conduce_legalidad',
            'fecha' => '2026-10-05',
            'hora_inicio' => '09:00:00',
            'municipio' => 'Morelia',
            'lugar' => 'Centro',
            'estado' => 'activo',
            'unidad_id' => 3,
        ]);
        $infraccion = LicenciaPuntoInfraccion::query()->create([
            'codigo' => 'ART-123',
            'nombre' => 'Falta de placa',
            'articulo' => '123',
            'fundamento_legal' => 'Articulo 123',
            'retencion_vehiculo' => true,
            'activa' => true,
        ]);
        $captura = ConduceLegalidadCaptura::query()->create([
            'operativo_id' => $operativo->id,
            'client_uuid' => 'captura-1',
            'agente_nombre' => 'Agente Uno',
            'unidad_id' => 3,
            'fecha' => '2026-10-05',
            'hora' => '09:30:00',
            'municipio' => 'Morelia',
            'lugar' => 'Avenida Madero',
            'lat' => 19.7000000,
            'lng' => -101.1900000,
            'narrativa' => 'Primera captura',
        ]);
        $captura->fundamentos()->create([
            'licencia_punto_infraccion_id' => $infraccion->id,
            'orden' => 0,
            'infraccion_codigo' => 'ART-123',
            'fundamento_legal' => 'Articulo 123 capturado',
        ]);
        $vehiculoOrigen = $captura->vehiculos()->create([
            'marca' => 'Honda',
            'modelo' => '2024',
            'tipo_general' => 'motocicleta',
            'tipo' => 'Motocicleta',
            'linea' => 'Cargo',
            'color' => 'Rojo',
            'placas' => 'ABC-123',
            'estado_placas' => 'Michoacan',
            'serie' => '12345678901234567',
            'tipo_servicio' => 'Particular',
        ]);
        $personaOrigen = $captura->personas()->create([
            'nombre' => 'Persona Conductora',
            'sexo' => 'Masculino',
            'edad' => 28,
        ]);

        $servicio = app(ConduceLegalidadActividadSyncService::class);
        $actividad = DB::transaction(fn () => $servicio->sync($captura));

        $captura->refresh();
        $this->assertNotNull($captura->actividad_id);
        $this->assertSame($actividad->id, $captura->actividad_id);
        $this->assertSame(1, Actividad::query()->count());
        $this->assertSame('CONDUCE CON LEGALIDAD', $actividad->subcategoria->nombre);
        $this->assertSame('MORELIA', $actividad->municipio);
        $this->assertSame('AGENTE UNO', $actividad->nombre);
        $this->assertSame('ROJO', $actividad->vehiculos->first()->color);
        $this->assertSame(28, $actividad->personas->first()->edad);
        $this->assertSame('CONDUCTOR', $actividad->personas->first()->tipo_participacion);
        $this->assertSame('123', $actividad->infracciones_actividad[0]['articulo']);

        $captura->update([
            'municipio' => 'Uruapan',
            'narrativa' => 'Captura corregida',
        ]);
        $segundaInfraccion = LicenciaPuntoInfraccion::query()->create([
            'codigo' => 'ART-456',
            'nombre' => 'Otra conducta',
            'articulo' => '456',
            'fundamento_legal' => 'Articulo 456',
            'retencion_vehiculo' => true,
            'activa' => true,
        ]);
        $captura->fundamentos()->delete();
        $captura->fundamentos()->create([
            'licencia_punto_infraccion_id' => $segundaInfraccion->id,
            'orden' => 0,
            'infraccion_codigo' => 'ART-456',
            'fundamento_legal' => 'Articulo 456 capturado',
        ]);
        $vehiculoOrigen->update(['color' => 'Negro']);
        $personaOrigen->update(['edad' => 29]);

        DB::transaction(fn () => $servicio->sync($captura->fresh()));

        $actividad->refresh()->load(['vehiculos', 'personas']);
        $this->assertSame(1, Actividad::query()->count());
        $this->assertSame('URUAPAN', $actividad->municipio);
        $this->assertSame('Captura corregida', $actividad->narrativa);
        $this->assertSame('NEGRO', $actividad->vehiculos->first()->color);
        $this->assertSame(29, $actividad->personas->first()->edad);
        $this->assertSame('456', $actividad->infracciones_actividad[0]['articulo']);

        DB::transaction(function () use ($captura, $actividad, $servicio): void {
            $captura->delete();
            $servicio->deleteLinkedActivity($actividad);
        });

        $this->assertSame(0, Actividad::query()->count());
    }

    public function test_ignora_las_capturas_de_alcoholimetria(): void
    {
        $operativo = ConduceLegalidadOperativo::query()->create([
            'nombre' => 'Operativo de Alcoholimetria',
            'tipo_operativo' => 'alcoholimetria',
            'fecha' => '2026-10-05',
            'estado' => 'activo',
        ]);
        $captura = ConduceLegalidadCaptura::query()->create([
            'operativo_id' => $operativo->id,
            'client_uuid' => 'alcohol-1',
        ]);

        $this->assertNull(app(ConduceLegalidadActividadSyncService::class)->sync($captura));
        $this->assertSame(0, Actividad::query()->count());
    }

    private function crearEsquema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('nombres')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->unsignedBigInteger('destacamento_id')->nullable();
            $table->timestamps();
        });
        Schema::create('actividad_categorias', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('slug');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('actividad_subcategorias', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actividad_categoria_id');
            $table->string('nombre');
            $table->string('slug');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('conduce_legalidad_operativos', function (Blueprint $table): void {
            $table->id();
            $table->string('client_uuid')->nullable();
            $table->string('nombre');
            $table->string('tipo_operativo');
            $table->date('fecha');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->string('municipio')->nullable();
            $table->string('lugar')->nullable();
            $table->string('numero')->nullable();
            $table->string('colonia')->nullable();
            $table->string('codigo_postal')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('coordenadas_texto')->nullable();
            $table->text('objetivo')->nullable();
            $table->text('narrativa')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado');
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamps();
        });
        Schema::create('actividades', function (Blueprint $table): void {
            $table->id();
            $table->string('client_uuid')->nullable();
            $table->string('sync_status')->default('local');
            $table->text('sync_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->unsignedBigInteger('actividad_categoria_id');
            $table->unsignedBigInteger('actividad_subcategoria_id')->nullable();
            $table->string('nombre');
            $table->unsignedInteger('cantidad')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('unidad_org_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('destacamento_id')->nullable();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('lugar')->nullable();
            $table->string('municipio')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('coordenadas_texto')->nullable();
            $table->string('fuente_ubicacion')->nullable();
            $table->text('motivo')->nullable();
            $table->text('narrativa')->nullable();
            $table->text('observaciones')->nullable();
            $table->json('infracciones_actividad')->nullable();
            $table->unsignedInteger('personas_alcanzadas')->default(0);
            $table->unsignedInteger('personas_participantes')->default(0);
            $table->unsignedInteger('personas_detenidas')->default(0);
            $table->string('estado_revision')->default('pendiente');
            $table->timestamps();
        });
        Schema::create('licencia_punto_infracciones', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->nullable();
            $table->string('nombre')->nullable();
            $table->string('articulo')->nullable();
            $table->string('fraccion')->nullable();
            $table->string('inciso')->nullable();
            $table->string('ambito_vehiculo')->nullable();
            $table->integer('puntos')->default(0);
            $table->integer('multa_uma_min')->nullable();
            $table->integer('multa_uma_max')->nullable();
            $table->boolean('amonestacion')->default(false);
            $table->boolean('arresto_persona')->default(false);
            $table->boolean('suspension_licencia')->default(false);
            $table->boolean('cancelacion_licencia')->default(false);
            $table->boolean('deposito_si_sin_persona_habilitada')->default(false);
            $table->boolean('retencion_vehiculo')->default(false);
            $table->text('descripcion')->nullable();
            $table->text('fundamento_legal')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
        Schema::create('conduce_legalidad_capturas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('operativo_id');
            $table->unsignedBigInteger('actividad_id')->nullable();
            $table->unsignedBigInteger('licencia_punto_infraccion_id')->nullable();
            $table->string('infraccion_codigo')->nullable();
            $table->text('fundamento_legal')->nullable();
            $table->string('client_uuid')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('agente_nombre')->nullable();
            $table->string('agente_nombres')->nullable();
            $table->string('agente_apellido_paterno')->nullable();
            $table->string('agente_apellido_materno')->nullable();
            $table->string('agente_numero_placa')->nullable();
            $table->string('agente_adscripcion')->nullable();
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('municipio')->nullable();
            $table->string('lugar')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('coordenadas_texto')->nullable();
            $table->text('narrativa')->nullable();
            $table->text('observaciones')->nullable();
            $table->json('rnd_data')->nullable();
            $table->timestamps();
        });
        Schema::create('conduce_legalidad_captura_fundamentos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('captura_id');
            $table->unsignedBigInteger('licencia_punto_infraccion_id');
            $table->unsignedInteger('orden')->default(0);
            $table->string('infraccion_codigo')->nullable();
            $table->text('fundamento_legal')->nullable();
            $table->timestamps();
        });
        Schema::create('conduce_legalidad_vehiculos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('captura_id');
            foreach (['marca', 'modelo', 'tipo_general', 'tipo', 'linea', 'color', 'placas', 'estado_placas', 'serie', 'numero_inventario', 'tipo_servicio', 'tarjeta_circulacion_nombre', 'grua', 'corralon', 'aseguradora', 'partes_danadas'] as $campo) {
                $table->string($campo)->nullable();
            }
            $table->unsignedInteger('capacidad_personas')->default(0);
            $table->unsignedBigInteger('grua_id')->nullable();
            $table->decimal('monto_danos', 12, 2)->nullable();
            $table->boolean('antecedente_vehiculo')->default(false);
            $table->timestamps();
        });
        Schema::create('conduce_legalidad_personas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('captura_id');
            foreach (['nombre', 'nombres', 'apellido_paterno', 'apellido_materno', 'telefono', 'domicilio', 'sexo', 'nacionalidad', 'ocupacion', 'observaciones'] as $campo) {
                $table->string($campo)->nullable();
            }
            $table->unsignedTinyInteger('edad')->nullable();
            $table->timestamps();
        });
        Schema::create('vehiculos', function (Blueprint $table): void {
            $table->id();
            $table->string('client_uuid')->nullable();
            foreach (['marca', 'modelo', 'tipo', 'linea', 'color', 'placas', 'estado_placas', 'serie', 'tipo_servicio', 'tarjeta_circulacion_nombre', 'grua', 'numero_inventario_grua', 'corralon', 'aseguradora', 'partes_danadas'] as $campo) {
                $table->string($campo)->nullable();
            }
            $table->unsignedInteger('capacidad_personas')->default(0);
            $table->unsignedBigInteger('grua_id')->nullable();
            $table->decimal('monto_danos', 12, 2)->nullable();
            $table->boolean('antecedente_vehiculo')->default(false);
            $table->timestamps();
        });
        Schema::create('actividad_vehiculo', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('vehiculo_id');
            $table->timestamps();
        });
        Schema::create('actividad_personas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('vehiculo_id')->nullable();
            $table->string('tipo_participacion');
            $table->string('nombre');
            $table->string('telefono')->nullable();
            $table->string('domicilio')->nullable();
            $table->string('sexo')->nullable();
            $table->string('nacionalidad')->nullable();
            $table->string('ocupacion')->nullable();
            $table->unsignedTinyInteger('edad')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
        Schema::create('servicios', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vehiculo_id');
            $table->unsignedBigInteger('grua_id')->nullable();
            $table->unsignedBigInteger('unidad_id')->nullable();
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->string('tipo_vehiculo')->nullable();
            $table->string('aseguradora')->default('');
            $table->timestamps();
        });
    }
}
