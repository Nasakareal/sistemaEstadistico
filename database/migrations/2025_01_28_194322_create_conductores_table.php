<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConductoresTable extends Migration
{
    public function up()
    {
        Schema::create('conductores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->integer('edad');
            $table->string('domicilio', 255);
            $table->string('telefono', 20)->nullable();
            $table->string('sexo', 20)->nullable();
            $table->string('ocupacion', 255)->nullable();
            $table->boolean('cinturon')->default(false);
            $table->boolean('antecedentes')->default(false);
            $table->boolean('certificado_lesiones')->default(false);
            $table->boolean('certificado_alcoholemia')->default(false);
            $table->boolean('aliento_etilico')->default(false);
            $table->string('estado_licencia', 100);
            $table->date('vigencia_licencia');
            $table->string('numero_licencia', 50);
            $table->string('tipo_licencia', 50);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('conductores');
        Schema::enableForeignKeyConstraints();
    }
}
