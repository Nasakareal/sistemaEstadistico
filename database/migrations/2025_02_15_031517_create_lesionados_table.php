<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLesionadosTable extends Migration
{
    public function up()
    {
        Schema::create('lesionados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hecho_id');
            $table->string('nombre', 255);
            $table->integer('edad')->nullable();
            $table->enum('sexo', ['Masculino', 'Femenino', 'Otro'])->nullable();
            $table->enum('tipo_lesion', [
                'Leve', 
                'Moderada', 
                'Grave', 
                'Fallecido'
            ]);
            $table->boolean('hospitalizado')->default(false);
            $table->string('hospital')->nullable();
            $table->boolean('atencion_en_sitio')->default(false);
            $table->string('ambulancia')->nullable();
            $table->string('paramedico')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('hecho_id')->references('id')->on('hechos')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesionados');
    }
}
