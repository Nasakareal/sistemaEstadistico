<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePuestasDisposicionTable extends Migration
{
    public function up()
    {
        Schema::create('puestas_disposicion', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('hecho_id')->nullable();

            $table->integer('numero_puesta');
            $table->year('anio');

            $table->string('tipo_puesta', 100);
            $table->string('motivo', 150);
            $table->string('estatus', 100)->default('ACTIVA');

            $table->string('nombre_policia', 255);
            $table->string('nombre_mp', 255)->nullable();
            $table->string('autoridad_receptora', 255)->nullable();
            $table->string('area', 255)->nullable();
            $table->string('carpeta_investigacion', 255)->nullable();
            $table->string('oficio', 255)->nullable();

            $table->date('fecha_puesta');
            $table->time('hora_puesta')->nullable();
            $table->string('lugar_puesta', 255)->nullable();
            $table->text('narrativa')->nullable();
            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('unidad_id');
            $table->unsignedBigInteger('delegacion_id')->nullable();
            $table->unsignedBigInteger('destacamento_id')->nullable();

            $table->string('archivo_puesta', 255)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->unique(['numero_puesta', 'anio']);

            $table->index('hecho_id');
            $table->index('unidad_id');
            $table->index('delegacion_id');
            $table->index('destacamento_id');
            $table->index('fecha_puesta');

            $table->foreign('hecho_id')
                ->references('id')
                ->on('hechos')
                ->nullOnDelete();

            $table->foreign('unidad_id')
                ->references('id')
                ->on('unidades')
                ->restrictOnDelete();

            $table->foreign('delegacion_id')
                ->references('id')
                ->on('delegaciones')
                ->nullOnDelete();

            $table->foreign('destacamento_id')
                ->references('id')
                ->on('destacamentos')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('puestas_disposicion');
    }
}
