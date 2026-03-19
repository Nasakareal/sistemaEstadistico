<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operativo_consolidado_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('operativo_consolidado_id');
            $table->unsignedBigInteger('operativo_dispositivo_id');

            $table->unsignedBigInteger('operativo_dispositivo_catalogo_id')->nullable();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('lugar', 255)->nullable();

            $table->unsignedInteger('cantidad')->default(0);
            $table->unsignedInteger('vehiculos_inspeccionados')->default(0);
            $table->unsignedInteger('personas_inspeccionadas')->default(0);
            $table->unsignedInteger('vehiculos_impactados')->default(0);
            $table->unsignedInteger('personas_impactadas')->default(0);
            $table->unsignedInteger('estado_fuerza_participante')->default(0);
            $table->decimal('kilometros_recorridos', 10, 2)->default(0);

            $table->text('crps_participantes')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            $table->unique(
                ['operativo_consolidado_id', 'operativo_dispositivo_id'],
                'opc_detalle_unique'
            );

            $table->index(['operativo_consolidado_id'], 'opc_detalle_consolidado_idx');
            $table->index(['operativo_dispositivo_id'], 'opc_detalle_dispositivo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operativo_consolidado_detalles');
    }
};
