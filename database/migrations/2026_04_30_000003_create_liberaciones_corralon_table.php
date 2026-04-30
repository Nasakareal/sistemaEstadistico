<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiberacionesCorralonTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('liberaciones_corralon')) {
            return;
        }

        Schema::create('liberaciones_corralon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->foreignId('grua_id')->nullable()->constrained('gruas')->nullOnDelete();
            $table->foreignId('grua_usuario_id')->nullable()->constrained('grua_usuarios')->nullOnDelete();
            $table->string('persona_recibe')->nullable();
            $table->string('identificacion_recibe')->nullable();
            $table->string('telefono_recibe', 30)->nullable();
            $table->string('foto_identificacion')->nullable();
            $table->string('foto_entrega')->nullable();
            $table->string('documento_liberacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['PENDIENTE', 'ENTREGADO'])->default('PENDIENTE');
            $table->dateTime('fecha_entrega')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('liberaciones_corralon');
    }
}
