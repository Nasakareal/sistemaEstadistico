<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parque_vehicular', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('unidad_id')->index();

            $table->string('tipo', 60)->nullable();
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 60)->nullable();
            $table->string('anio', 10)->nullable();

            $table->string('numero_economico', 40)->nullable()->index();
            $table->string('placas', 20)->nullable()->index();
            $table->string('serie', 40)->nullable()->index();
            $table->string('color', 40)->nullable();

            $table->string('estatus', 30)->default('ACTIVO')->index();
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('unidad_id')->references('id')->on('unidades')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parque_vehicular');
    }
};
