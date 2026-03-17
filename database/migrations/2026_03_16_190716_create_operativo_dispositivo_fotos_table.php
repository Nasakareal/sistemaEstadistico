<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperativoDispositivoFotosTable extends Migration
{
    public function up()
    {
        Schema::create('operativo_dispositivo_fotos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('operativo_dispositivo_id')->index();

            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('peso')->nullable();

            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->foreign('operativo_dispositivo_id', 'operativo_dispositivo_fotos_fk')
                ->references('id')
                ->on('operativo_dispositivos')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('operativo_dispositivo_fotos');
    }
}
