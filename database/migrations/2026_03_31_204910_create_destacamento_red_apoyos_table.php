<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destacamento_red_apoyos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('destacamento_id');
            $table->string('tipo_apoyo', 100);
            $table->string('institucion');
            $table->string('contacto')->nullable();
            $table->string('cargo')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('telefono_secundario', 30)->nullable();
            $table->string('direccion')->nullable();
            $table->string('municipio')->nullable();
            $table->text('observaciones')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('destacamento_id')
                ->references('id')
                ->on('destacamentos')
                ->onDelete('cascade');

            $table->index('destacamento_id');
            $table->index('tipo_apoyo');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destacamento_red_apoyos');
    }
};
