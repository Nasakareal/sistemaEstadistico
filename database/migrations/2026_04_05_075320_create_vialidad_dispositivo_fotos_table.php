<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVialidadDispositivoFotosTable extends Migration
{
    public function up()
    {
        Schema::create('vialidad_dispositivo_fotos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vialidad_dispositivo_id');
            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->unsignedInteger('orden')->default(1);
            $table->boolean('portada')->default(false);
            $table->boolean('included_in_share')->default(true);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();

            $table->foreign('vialidad_dispositivo_id', 'fk_vdf_dispositivo')
                ->references('id')
                ->on('vialidad_dispositivos')
                ->onDelete('cascade');

            $table->index(['vialidad_dispositivo_id', 'orden'], 'idx_vdf_dispositivo_orden');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vialidad_dispositivo_fotos');
    }
}
