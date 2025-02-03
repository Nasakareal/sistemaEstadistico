<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHechosTable extends Migration
{
    public function up()
    {
        Schema::create('hechos', function (Blueprint $table) {
            $table->id();
            $table->string('folio_c5i', 20)->unique();
            $table->string('perito', 255);
            $table->string('autorizacion_practico', 255);
            $table->string('unidad', 50);
            $table->time('hora');
            $table->date('fecha');
            $table->string('sector', 100);
            $table->string('calle', 255);
            $table->string('colonia', 255);
            $table->string('entre_calles', 255);
            $table->string('municipio', 100);
            $table->enum('tipo_hecho', [
                'VOLCADURA', 
                'SALIDA DE SUPERFICIE DE RODAMIENTO', 
                'SUBIDA AL CAMELLÓN', 
                'CAIDA DE MOTOCICLETA',
                'COLISIÓN CON PEATÓN',
                'COLISIÓN POR ALCANCE',
                'COLISIÓN POR NO RESPETAR SEMÁFORO',
                'COLISIÓN POR INVASIÓN DE CARRIL',
                'COLISIÓN POR CAMBIO DE CARRIL',
                'COLISIÓN POR CORTE DE CIRCULACIÓN',
                'COLISIÓN POR MANIOBRA DE REVERSA',
                'CAIDA ACUATICA DE VEHÍCULO',
                'DESBARRANCAMIENTO',
                'INCENDIO',
                'EXPLOSIÓN',
                'Otro'
            ]);
            $table->string('superficie_via', 50);
            $table->enum('tiempo', ['Día', 'Noche', 'Amanecer', 'Atardecer']);
            $table->enum('clima', ['Bueno', 'Malo', 'Nublado', 'Lluvioso']);
            $table->enum('condiciones', ['Bueno', 'Regular', 'Malo']);
            $table->string('control_transito', 50);
            $table->boolean('checaron_antecedentes')->default(false);
            $table->string('causas', 255);
            $table->string('colision_camino', 255);
            $table->enum('situacion', ['Resuelto', 'Pendiente', 'Turnado']);
            $table->string('oficio_mp', 255)->nullable();
            $table->integer('vehiculos_mp')->default(0);
            $table->integer('personas_mp')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hechos');
    }
}
