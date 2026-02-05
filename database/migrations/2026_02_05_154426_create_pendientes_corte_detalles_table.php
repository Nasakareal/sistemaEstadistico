<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendientes_corte_detalles', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('pendientes_corte_id');
            $table->unsignedBigInteger('hecho_id');
            $table->string('situacion_en_corte', 30)->default('PENDIENTE');
            $table->timestamps();
            $table->unique(['pendientes_corte_id', 'hecho_id']);
            $table->foreign('pendientes_corte_id')->references('id')->on('pendientes_cortes')->cascadeOnDelete();
            $table->foreign('hecho_id')->references('id')->on('hechos')->cascadeOnDelete();
            $table->index('hecho_id');
            $table->index('pendientes_corte_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendientes_corte_detalles');
    }
};
