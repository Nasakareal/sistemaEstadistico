<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rol_servicios', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 120);
            $table->string('tipo', 30)->default('24x24')->index();

            $table->date('fecha_inicio_regla');
            $table->unsignedBigInteger('turno_inicial_id');

            $table->boolean('activo')->default(true)->index();

            $table->timestamps();

            $table->foreign('turno_inicial_id')->references('id')->on('turnos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_servicios');
    }
};
