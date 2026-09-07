<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCaleaDirectivasTable extends Migration
{
    public function up()
    {
        Schema::create('calea_directivas', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 30)->unique();

            $table->unsignedSmallInteger('nivel_1')->nullable();
            $table->unsignedSmallInteger('nivel_2')->nullable();
            $table->unsignedSmallInteger('nivel_3')->nullable();

            $table->string('titulo', 500);
            $table->string('categoria', 255)->nullable();
            $table->text('descripcion')->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index(['nivel_1', 'nivel_2', 'nivel_3']);
            $table->index('categoria');
            $table->index('activo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calea_directivas');
    }
}
