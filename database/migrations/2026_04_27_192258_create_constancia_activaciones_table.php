<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConstanciaActivacionesTable extends Migration
{
    public function up()
    {
        Schema::create('constancia_activaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('constancia_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('accion', ['ACTIVADA', 'CANCELADA', 'REIMPRESA', 'VALIDADA_QR']);
            $table->dateTime('fecha');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('constancia_id');
            $table->index('user_id');
            $table->index('accion');
            $table->index('fecha');
        });
    }

    public function down()
    {
        Schema::dropIfExists('constancia_activaciones');
    }
}
