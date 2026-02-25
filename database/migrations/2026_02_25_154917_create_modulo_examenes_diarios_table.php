<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulo_examenes_diarios', function (Blueprint $table) {
            $table->id();

            $table->date('fecha');
            $table->string('modulo_nombre', 150);

            $table->unsignedInteger('servicio_publico')->default(0);
            $table->unsignedInteger('automovilista')->default(0);
            $table->unsignedInteger('chofer')->default(0);
            $table->unsignedInteger('motociclista')->default(0);
            $table->unsignedInteger('permiso')->default(0);

            $table->unsignedInteger('total');

            $table->unsignedInteger('hombres')->default(0);
            $table->unsignedInteger('mujeres')->default(0);

            $table->unsignedInteger('aprobados')->default(0);
            $table->unsignedInteger('reprobados')->default(0);

            $table->text('folios')->nullable();
            $table->string('informado_por', 150)->nullable();

            $table->timestamps();

            $table->unique(['fecha', 'modulo_nombre']);
            $table->index(['fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulo_examenes_diarios');
    }
};
