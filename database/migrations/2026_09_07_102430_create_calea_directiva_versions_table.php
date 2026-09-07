<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaleaDirectivaVersionsTable extends Migration
{
    public function up()
    {
        Schema::create('calea_directiva_versiones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calea_directiva_id');

            $table->unsignedSmallInteger('numero_version')->default(1);
            $table->string('nombre_version', 100)->nullable();

            $table->date('fecha_emision')->nullable();
            $table->unsignedTinyInteger('mes_emision')->nullable();
            $table->unsignedSmallInteger('anio_emision')->nullable();

            $table->date('fecha_revision')->nullable();

            $table->text('area_responsable')->nullable();
            $table->text('autoriza')->nullable();
            $table->text('realizado_por')->nullable();
            $table->text('leyenda_documento')->nullable();

            $table->string('archivo_disk', 100)->nullable();
            $table->string('archivo_path', 1024)->nullable();
            $table->string('archivo_nombre_original', 500)->nullable();
            $table->string('archivo_mime', 100)->nullable();
            $table->unsignedBigInteger('archivo_size')->nullable();
            $table->char('archivo_sha256', 64)->nullable();
            $table->unsignedSmallInteger('numero_paginas')->nullable();

            $table->longText('texto_busqueda')->nullable();

            $table->boolean('vigente')->default(true);
            $table->boolean('publicada')->default(true);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            $table->foreign('calea_directiva_id')
                ->references('id')
                ->on('calea_directivas')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->unique(
                ['calea_directiva_id', 'numero_version'],
                'calea_directiva_version_unique'
            );

            $table->index(
                ['calea_directiva_id', 'vigente'],
                'calea_directiva_vigente_idx'
            );

            $table->index('fecha_revision');
            $table->index('anio_emision');
            $table->index('archivo_sha256');
            $table->index('publicada');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calea_directiva_versiones');
    }
}
