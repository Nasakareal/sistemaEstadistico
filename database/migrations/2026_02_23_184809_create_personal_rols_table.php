<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_rols', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('rol_servicio_id');

            $table->unsignedBigInteger('turno_base_id');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();

            $table->boolean('activo')->default(true)->index();

            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->cascadeOnDelete();
            $table->foreign('rol_servicio_id')->references('id')->on('rol_servicios')->restrictOnDelete();
            $table->foreign('turno_base_id')->references('id')->on('turnos')->restrictOnDelete();

            $table->index(['personal_id', 'activo']);
            $table->index(['fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_rols');
    }
};
