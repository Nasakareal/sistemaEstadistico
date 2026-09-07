<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaleaDirectivaSeccionsTable extends Migration
{
    public function up()
    {
        Schema::create('calea_directiva_secciones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calea_directiva_version_id');

            $table->unsignedInteger('orden')->default(0);

            $table->string('numero', 50)->nullable();
            $table->string('tipo', 100)->nullable();
            $table->string('titulo', 500)->nullable();

            $table->longText('contenido')->nullable();

            $table->unsignedSmallInteger('pagina_inicio')->nullable();
            $table->unsignedSmallInteger('pagina_fin')->nullable();

            $table->timestamps();

            $table->foreign('calea_directiva_version_id')
                ->references('id')
                ->on('calea_directiva_versiones')
                ->onDelete('cascade');

            $table->index(
                ['calea_directiva_version_id', 'orden'],
                'calea_seccion_version_orden_idx'
            );

            $table->index('tipo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calea_directiva_secciones');
    }
}
