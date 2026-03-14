<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulo_constancia_examenes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('modulo_examen_diario_id')->nullable();
            $table->unsignedBigInteger('user_id');

            $table->date('fecha');
            $table->string('modulo_nombre', 150);

            $table->string('folios_desde', 50);
            $table->string('folios_hasta', 50);
            $table->string('rango_folios', 120)->nullable();

            $table->unsignedInteger('cantidad_constancias')->default(0);

            $table->unsignedInteger('servicio_publico')->default(0);
            $table->unsignedInteger('automovilista')->default(0);
            $table->unsignedInteger('chofer')->default(0);
            $table->unsignedInteger('motociclista')->default(0);
            $table->unsignedInteger('permiso')->default(0);

            $table->unsignedInteger('hombres')->default(0);
            $table->unsignedInteger('mujeres')->default(0);
            $table->unsignedInteger('aprobados')->default(0);
            $table->unsignedInteger('reprobados')->default(0);

            $table->string('informado_por', 150)->nullable();

            $table->enum('tipo_movimiento', ['GENERACION', 'REIMPRESION', 'CANCELACION'])->default('GENERACION');
            $table->text('observaciones')->nullable();

            $table->string('pdf_path')->nullable();
            $table->string('pdf_nombre')->nullable();

            $table->timestamp('fecha_generacion')->nullable();

            $table->foreign('modulo_examen_diario_id')
                ->references('id')
                ->on('modulo_examenes_diarios')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index(['fecha', 'modulo_nombre']);
            $table->index('user_id');
            $table->index('modulo_examen_diario_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulo_constancia_examenes');
    }
};
