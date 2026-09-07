<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaleaDirectivaBloquesTable extends Migration
{
    public function up()
    {
        Schema::create('calea_directiva_bloques', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('calea_directiva_seccion_id');
            $table->unsignedBigInteger('parent_id')->nullable();

            $table->unsignedInteger('orden')->default(0);

            $table->string('tipo', 100)->nullable();
            $table->string('numero', 100)->nullable();
            $table->string('titulo', 500)->nullable();

            $table->longText('contenido')->nullable();

            $table->unsignedSmallInteger('pagina_inicio')->nullable();
            $table->unsignedSmallInteger('pagina_fin')->nullable();

            $table->boolean('buscable')->default(true);
            $table->boolean('citable')->default(true);

            $table->timestamps();

            $table->foreign('calea_directiva_seccion_id')
                ->references('id')
                ->on('calea_directiva_secciones')
                ->onDelete('cascade');

            $table->foreign('parent_id')
                ->references('id')
                ->on('calea_directiva_bloques')
                ->onDelete('cascade');

            $table->index(
                ['calea_directiva_seccion_id', 'orden'],
                'calea_bloque_seccion_orden_idx'
            );

            $table->index('parent_id');
            $table->index('tipo');
            $table->index('buscable');
            $table->index('citable');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calea_directiva_bloques');
    }
}
