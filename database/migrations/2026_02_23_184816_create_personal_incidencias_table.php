<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_incidencias', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('incidencia_tipo_id');

            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            $table->string('folio', 60)->nullable()->index();
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('documento_id')->nullable();

            $table->boolean('activo')->default(true)->index();

            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->cascadeOnDelete();
            $table->foreign('incidencia_tipo_id')->references('id')->on('incidencia_tipos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_incidencias');
    }
};
