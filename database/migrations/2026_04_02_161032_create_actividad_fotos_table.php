<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActividadFotosTable extends Migration
{
    public function up()
    {
        Schema::create('actividad_fotos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->string('foto_path');
            $table->string('foto_nombre_original')->nullable();
            $table->string('foto_hash', 191)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('actividad_id')->references('id')->on('actividades')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index('actividad_id');
            $table->index(['actividad_id', 'orden']);
        });
    }

    public function down()
    {
        Schema::table('actividad_fotos', function (Blueprint $table) {
            $table->dropForeign(['actividad_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });

        Schema::dropIfExists('actividad_fotos');
    }
}
