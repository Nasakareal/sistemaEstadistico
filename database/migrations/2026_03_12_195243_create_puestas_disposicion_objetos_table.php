<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePuestasDisposicionObjetosTable extends Migration
{
    public function up()
    {
        Schema::create('puestas_disposicion_objetos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('puesta_disposicion_id');

            $table->string('tipo_objeto', 100);
            $table->text('descripcion');
            $table->decimal('cantidad', 10, 2)->nullable();
            $table->string('unidad_medida', 50)->nullable();
            $table->string('cadena_custodia', 255)->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->index('puesta_disposicion_id');
            $table->index('tipo_objeto');

            $table->foreign('puesta_disposicion_id')
                ->references('id')
                ->on('puestas_disposicion')
                ->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('puestas_disposicion_objetos');
    }
}
