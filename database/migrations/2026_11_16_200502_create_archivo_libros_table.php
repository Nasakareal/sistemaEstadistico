<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivo_areas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->string('tipo', 30)->default('sala');
            $table->text('descripcion')->nullable();
            $table->decimal('plano_ancho', 8, 2)->nullable();
            $table->decimal('plano_alto', 8, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('archivo_anaqueles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')
                ->constrained('archivo_areas')
                ->restrictOnDelete();
            $table->string('codigo', 30);
            $table->string('nombre', 150)->nullable();
            $table->unsignedSmallInteger('total_filas')->default(1);
            $table->unsignedSmallInteger('total_columnas')->default(1);
            $table->decimal('plano_x', 8, 2)->nullable();
            $table->decimal('plano_y', 8, 2)->nullable();
            $table->decimal('plano_ancho', 8, 2)->nullable();
            $table->decimal('plano_alto', 8, 2)->nullable();
            $table->unsignedSmallInteger('rotacion')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['area_id', 'codigo']);
        });

        Schema::create('archivo_bloques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anaquel_id')
                ->constrained('archivo_anaqueles')
                ->restrictOnDelete();
            $table->string('codigo', 20);
            $table->unsignedSmallInteger('fila');
            $table->unsignedSmallInteger('columna');
            $table->unsignedSmallInteger('capacidad_libros')->default(8);
            $table->string('orden_apilado', 30)->default('arriba_abajo');
            $table->uuid('qr_uuid')->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['anaquel_id', 'codigo']);
            $table->unique(['anaquel_id', 'fila', 'columna']);
        });

        Schema::create('archivo_libros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->uuid('qr_uuid')->nullable()->unique();
            $table->string('titulo', 200)->nullable();
            $table->string('numero_libro', 40)->nullable();
            $table->unsignedSmallInteger('anio_inicio')->nullable();
            $table->unsignedSmallInteger('anio_fin')->nullable();
            $table->string('folio_inicial', 60)->nullable();
            $table->string('folio_final', 60)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->unsignedInteger('total_paginas')->nullable();
            $table->unsignedInteger('paginas_digitalizadas')->default(0);
            $table->string('estado_digitalizacion', 30)->default('pendiente');
            $table->string('estado_fisico', 30)->default('por_ubicar');

            $table->foreignId('bloque_destino_id')
                ->nullable()
                ->constrained('archivo_bloques')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('posicion_destino')->nullable();

            $table->foreignId('area_actual_id')
                ->nullable()
                ->constrained('archivo_areas')
                ->restrictOnDelete();
            $table->foreignId('bloque_actual_id')
                ->nullable()
                ->constrained('archivo_bloques')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('posicion_actual')->nullable();

            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['anio_inicio', 'anio_fin']);
            $table->index(['folio_inicial', 'folio_final']);
            $table->index('estado_digitalizacion');
            $table->index('estado_fisico');
            $table->unique(
                ['bloque_destino_id', 'posicion_destino'],
                'archivo_destino_posicion_unique'
            );
            $table->unique(
                ['bloque_actual_id', 'posicion_actual'],
                'archivo_actual_posicion_unique'
            );
        });

        Schema::create('archivo_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')
                ->constrained('archivo_libros')
                ->restrictOnDelete();
            $table->string('nombre', 200);
            $table->string('nombre_original', 255)->nullable();
            $table->string('disco', 60)->default('archivo_privado');
            $table->string('ruta_archivo', 500);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('tamano_bytes')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->unsignedInteger('pagina_inicio')->nullable();
            $table->unsignedInteger('pagina_fin')->nullable();
            $table->string('estado_revision', 30)->default('pendiente');
            $table->foreignId('usuario_carga_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['libro_id', 'pagina_inicio', 'pagina_fin']);
            $table->index('sha256');
        });

        Schema::create('archivo_folios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')
                ->constrained('archivo_libros')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('anio');
            $table->string('folio', 80);
            $table->date('fecha_siniestro')->nullable();
            $table->unsignedInteger('numero_pagina')->nullable();
            $table->foreignId('documento_id')
                ->nullable()
                ->constrained('archivo_documentos')
                ->restrictOnDelete();
            $table->unsignedInteger('pagina_documento')->nullable();
            $table->string('estado_verificacion', 30)->default('pendiente');
            $table->timestamps();

            $table->index(['anio', 'folio']);
            $table->index(['libro_id', 'numero_pagina']);
        });

        Schema::create('archivo_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')
                ->constrained('archivo_libros')
                ->restrictOnDelete();
            $table->string('tipo', 40);

            $table->foreignId('origen_area_id')
                ->nullable()
                ->constrained('archivo_areas')
                ->restrictOnDelete();
            $table->foreignId('origen_bloque_id')
                ->nullable()
                ->constrained('archivo_bloques')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('origen_posicion')->nullable();

            $table->foreignId('destino_area_id')
                ->nullable()
                ->constrained('archivo_areas')
                ->restrictOnDelete();
            $table->foreignId('destino_bloque_id')
                ->nullable()
                ->constrained('archivo_bloques')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('destino_posicion')->nullable();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('responsable_externo', 150)->nullable();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha_movimiento')->useCurrent();
            $table->timestamps();

            $table->index(['libro_id', 'fecha_movimiento']);
            $table->index(['tipo', 'fecha_movimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivo_movimientos');
        Schema::dropIfExists('archivo_folios');
        Schema::dropIfExists('archivo_documentos');
        Schema::dropIfExists('archivo_libros');
        Schema::dropIfExists('archivo_bloques');
        Schema::dropIfExists('archivo_anaqueles');
        Schema::dropIfExists('archivo_areas');
    }
};
