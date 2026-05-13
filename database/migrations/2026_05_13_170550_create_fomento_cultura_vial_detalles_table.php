<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFomentoCulturaVialDetallesTable extends Migration
{
    public function up()
    {
        Schema::create('fomento_cultura_vial_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('actividad_id');

            $table->string('nivel_educativo', 120)->nullable();
            $table->string('sector', 120)->nullable();

            $table->unsignedInteger('ninas')->default(0);
            $table->unsignedInteger('ninos')->default(0);
            $table->unsignedInteger('adolescentes_mujeres')->default(0);
            $table->unsignedInteger('adolescentes_hombres')->default(0);
            $table->unsignedInteger('docentes_hombres')->default(0);
            $table->unsignedInteger('docentes_mujeres')->default(0);
            $table->unsignedInteger('hombres')->default(0);
            $table->unsignedInteger('mujeres')->default(0);
            $table->unsignedInteger('total_poblacion_atendida')->default(0);

            $table->timestamps();

            $table->foreign('actividad_id')
                ->references('id')
                ->on('actividades')
                ->onDelete('cascade');

            $table->index('actividad_id');
            $table->index('nivel_educativo');
            $table->index('sector');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fomento_cultura_vial_detalles');
    }
}
