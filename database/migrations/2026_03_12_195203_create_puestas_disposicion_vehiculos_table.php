<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePuestasDisposicionVehiculosTable extends Migration
{
    public function up()
    {
        Schema::create('puestas_disposicion_vehiculos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('puesta_disposicion_id');
            $table->unsignedBigInteger('vehiculo_id')->nullable();

            $table->string('tipo', 100)->nullable();
            $table->string('marca', 100)->nullable();
            $table->string('submarca', 100)->nullable();
            $table->string('modelo', 20)->nullable();
            $table->string('color', 100)->nullable();
            $table->string('placas', 50)->nullable();
            $table->string('serie', 100)->nullable();

            $table->string('calidad', 100);
            $table->string('motivo_relacion', 255)->nullable();
            $table->boolean('con_reporte_robo')->default(false);
            $table->string('numero_reporte_robo', 255)->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('puesta_disposicion_id');
            $table->index('vehiculo_id');
            $table->index('placas');
            $table->index('serie');
            $table->index('calidad');

            $table->foreign('puesta_disposicion_id')
                ->references('id')
                ->on('puestas_disposicion')
                ->cascadeOnDelete();

            $table->foreign('vehiculo_id')
                ->references('id')
                ->on('vehiculos')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('puestas_disposicion_vehiculos');
    }
}
