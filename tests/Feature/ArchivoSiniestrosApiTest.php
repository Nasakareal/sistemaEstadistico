<?php

namespace Tests\Feature;

use App\Models\ArchivoAnaquel;
use App\Models\ArchivoArea;
use App\Models\ArchivoBloque;
use App\Models\ArchivoDocumento;
use App\Models\ArchivoLibro;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArchivoSiniestrosApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->baseUrl = 'http://localhost';
        config()->set('app.url', 'http://localhost');
        URL::forceRootUrl('http://localhost');

        config()->set('database.default', 'archivo_siniestros_testing');
        config()->set('database.connections.archivo_siniestros_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('archivo_siniestros_testing');
        DB::setDefaultConnection('archivo_siniestros_testing');

        $this->crearEsquema();
        Sanctum::actingAs($this->usuario());
    }

    public function test_listados_y_detalle_de_anaquel_tienen_contrato_estable(): void
    {
        [, $anaquel] = $this->ubicacion();

        $this->getJson('/api/archivo-siniestros/anaqueles')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next'],
            ]);

        $this->getJson("/api/archivo-siniestros/anaqueles/{$anaquel->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $anaquel->id)
            ->assertJsonPath('data.codigo', 'ANQ-01');
    }

    public function test_patch_conserva_los_campos_no_enviados(): void
    {
        [$area] = $this->ubicacion();

        $this->patchJson("/api/archivo-siniestros/areas/{$area->id}", [
            'nombre' => 'Archivo renombrado',
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Archivo renombrado')
            ->assertJsonPath('data.codigo', 'AREA-01');

        $this->assertDatabaseHas('archivo_areas', [
            'id' => $area->id,
            'codigo' => 'AREA-01',
            'nombre' => 'Archivo renombrado',
            'activo' => true,
        ]);
    }

    public function test_patch_parcial_funciona_en_anaqueles_bloques_y_libros(): void
    {
        [, $anaquel, $bloque, $libro] = $this->ubicacion();

        $this->patchJson("/api/archivo-siniestros/anaqueles/{$anaquel->id}", [
            'nombre' => 'Anaquel norte',
        ])->assertOk()
            ->assertJsonPath('data.nombre', 'Anaquel norte')
            ->assertJsonPath('data.codigo', 'ANQ-01');

        $this->patchJson("/api/archivo-siniestros/bloques/{$bloque->id}", [
            'observaciones' => 'Cerca de la entrada',
        ])->assertOk()
            ->assertJsonPath('data.observaciones', 'Cerca de la entrada')
            ->assertJsonPath('data.codigo', 'B-01');

        $this->patchJson("/api/archivo-siniestros/libros/{$libro->id}", [
            'titulo' => 'Libro actualizado',
        ])->assertOk()
            ->assertJsonPath('data.titulo', 'Libro actualizado')
            ->assertJsonPath('data.codigo', 'LIB-01');
    }

    public function test_rechaza_una_pagina_digital_fuera_del_documento(): void
    {
        [, , , $libro] = $this->ubicacion();
        $documento = ArchivoDocumento::create([
            'libro_id' => $libro->id,
            'nombre' => 'Páginas 10 a 12',
            'disco' => 'local',
            'ruta_archivo' => 'archivo-siniestros/prueba.pdf',
            'pagina_inicio' => 10,
            'pagina_fin' => 12,
            'estado_revision' => 'pendiente',
        ]);

        $this->postJson("/api/archivo-siniestros/libros/{$libro->id}/folios", [
            'anio' => 2026,
            'folio' => 'F-001',
            'documento_id' => $documento->id,
            'pagina_documento' => 4,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('pagina_documento');
    }

    public function test_carga_un_documento_privado(): void
    {
        Storage::fake('local');
        [, , , $libro] = $this->ubicacion();

        $respuesta = $this->post("/api/archivo-siniestros/libros/{$libro->id}/documentos", [
            'nombre' => 'Escaneo de prueba',
            'pagina_inicio' => 1,
            'pagina_fin' => 1,
            'archivo' => UploadedFile::fake()->image('escaneo.jpg', 10, 10),
        ], ['Accept' => 'application/json']);

        $respuesta->assertCreated()
            ->assertJsonPath('data.libro_id', $libro->id);

        $documento = ArchivoDocumento::findOrFail($respuesta->json('data.id'));
        Storage::disk('local')->assertExists($documento->ruta_archivo);
    }

    public function test_usuario_sin_permiso_no_puede_acceder(): void
    {
        $usuario = User::create([
            'name' => 'Usuario sin acceso',
            'email' => 'sin-acceso@example.test',
            'password' => Hash::make('password'),
        ]);
        Sanctum::actingAs($usuario);

        $this->getJson('/api/archivo-siniestros/libros')->assertForbidden();
    }

    private function usuario(): User
    {
        $user = User::create([
            'name' => 'Administrador de prueba',
            'email' => 'archivo-api@example.test',
            'password' => Hash::make('password'),
        ]);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['ver archivo siniestros', 'gestionar archivo siniestros', 'digitalizar archivo siniestros'] as $nombre) {
            \Spatie\Permission\Models\Permission::create(['name' => $nombre, 'guard_name' => 'web']);
        }
        $user->givePermissionTo([
            'ver archivo siniestros',
            'gestionar archivo siniestros',
            'digitalizar archivo siniestros',
        ]);

        return $user;
    }

    private function ubicacion(): array
    {
        $area = ArchivoArea::firstOrCreate(['codigo' => 'AREA-01'], [
            'nombre' => 'Archivo principal',
            'tipo' => 'sala',
            'activo' => true,
        ]);
        $anaquel = ArchivoAnaquel::firstOrCreate([
            'area_id' => $area->id,
            'codigo' => 'ANQ-01',
        ], [
            'nombre' => 'Anaquel uno',
            'total_filas' => 2,
            'total_columnas' => 2,
            'rotacion' => 0,
            'activo' => true,
        ]);
        $bloque = ArchivoBloque::firstOrCreate([
            'anaquel_id' => $anaquel->id,
            'codigo' => 'B-01',
        ], [
            'fila' => 1,
            'columna' => 1,
            'capacidad_libros' => 10,
            'orden_apilado' => 'arriba_abajo',
            'qr_uuid' => (string) Str::uuid(),
            'activo' => true,
        ]);
        $libro = ArchivoLibro::firstOrCreate(['codigo' => 'LIB-01'], [
            'qr_uuid' => (string) Str::uuid(),
            'titulo' => 'Libro de prueba',
            'anio_inicio' => 2026,
            'total_paginas' => 100,
            'paginas_digitalizadas' => 0,
            'estado_digitalizacion' => 'pendiente',
            'estado_fisico' => 'por_ubicar',
        ]);

        return [$area, $anaquel, $bloque, $libro];
    }

    private function crearEsquema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nombres')->nullable();
            $table->string('apellido_paterno')->nullable();
            $table->string('apellido_materno')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
        Schema::create('archivo_areas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('tipo');
            $table->text('descripcion')->nullable();
            $table->decimal('plano_ancho', 8, 2)->nullable();
            $table->decimal('plano_alto', 8, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('archivo_anaqueles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('archivo_areas');
            $table->string('codigo');
            $table->string('nombre')->nullable();
            $table->unsignedInteger('total_filas');
            $table->unsignedInteger('total_columnas');
            $table->decimal('plano_x', 8, 2)->nullable();
            $table->decimal('plano_y', 8, 2)->nullable();
            $table->decimal('plano_ancho', 8, 2)->nullable();
            $table->decimal('plano_alto', 8, 2)->nullable();
            $table->unsignedInteger('rotacion')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->unique(['area_id', 'codigo']);
        });
        Schema::create('archivo_bloques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anaquel_id')->constrained('archivo_anaqueles');
            $table->string('codigo');
            $table->unsignedInteger('fila');
            $table->unsignedInteger('columna');
            $table->unsignedInteger('capacidad_libros');
            $table->string('orden_apilado');
            $table->uuid('qr_uuid')->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->unique(['anaquel_id', 'codigo']);
            $table->unique(['anaquel_id', 'fila', 'columna']);
        });
        Schema::create('archivo_libros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->uuid('qr_uuid')->nullable()->unique();
            $table->string('titulo')->nullable();
            $table->string('numero_libro')->nullable();
            $table->unsignedInteger('anio_inicio')->nullable();
            $table->unsignedInteger('anio_fin')->nullable();
            $table->string('folio_inicial')->nullable();
            $table->string('folio_final')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->unsignedInteger('total_paginas')->nullable();
            $table->unsignedInteger('paginas_digitalizadas')->default(0);
            $table->string('estado_digitalizacion');
            $table->string('estado_fisico');
            $table->foreignId('bloque_destino_id')->nullable();
            $table->unsignedInteger('posicion_destino')->nullable();
            $table->foreignId('area_actual_id')->nullable();
            $table->foreignId('bloque_actual_id')->nullable();
            $table->unsignedInteger('posicion_actual')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('archivo_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('archivo_libros');
            $table->string('nombre');
            $table->string('nombre_original')->nullable();
            $table->string('disco')->default('local');
            $table->string('ruta_archivo')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('sha256')->nullable();
            $table->unsignedInteger('pagina_inicio')->nullable();
            $table->unsignedInteger('pagina_fin')->nullable();
            $table->string('estado_revision')->default('pendiente');
            $table->foreignId('usuario_carga_id')->nullable();
            $table->timestamps();
        });
        Schema::create('archivo_folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('archivo_libros');
            $table->unsignedInteger('anio');
            $table->string('folio');
            $table->date('fecha_siniestro')->nullable();
            $table->unsignedInteger('numero_pagina')->nullable();
            $table->foreignId('documento_id')->nullable();
            $table->unsignedInteger('pagina_documento')->nullable();
            $table->string('estado_verificacion');
            $table->timestamps();
        });
        Schema::create('archivo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('archivo_libros');
            $table->string('tipo');
            $table->foreignId('origen_area_id')->nullable();
            $table->foreignId('origen_bloque_id')->nullable();
            $table->unsignedInteger('origen_posicion')->nullable();
            $table->foreignId('destino_area_id')->nullable();
            $table->foreignId('destino_bloque_id')->nullable();
            $table->unsignedInteger('destino_posicion')->nullable();
            $table->foreignId('usuario_id')->nullable();
            $table->string('responsable_externo')->nullable();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_movimiento')->nullable();
            $table->timestamps();
        });
    }
}
