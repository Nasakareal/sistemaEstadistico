<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hechos_eliminados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hecho_id_original')->index();
            $table->unsignedBigInteger('unidad_org_id')->nullable()->index();
            $table->json('datos_hecho');
            $table->json('lesionados')->nullable();
            $table->json('croquis')->nullable();
            $table->unsignedBigInteger('dictamen_id')->nullable();
            $table->foreignId('eliminado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('eliminado_at');
            $table->unsignedBigInteger('hecho_id_restaurado')->nullable()->index();
            $table->foreignId('restaurado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('restaurado_at')->nullable();
            $table->timestamps();
        });

        Schema::create('hechos_eliminados_vehiculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hecho_eliminado_id')
                ->constrained('hechos_eliminados')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('vehiculo_id_original')->nullable()->index();
            $table->json('datos_vehiculo');
            $table->json('datos_pivote')->nullable();
            $table->json('conductores')->nullable();
            $table->json('servicios')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hechos_eliminados_vehiculos');
        Schema::dropIfExists('hechos_eliminados');
    }
};
