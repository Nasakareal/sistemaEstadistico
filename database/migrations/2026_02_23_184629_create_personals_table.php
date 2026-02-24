<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personals', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('unidad_id')->index();
            $table->unsignedBigInteger('turno_id')->nullable()->index();

            $table->string('nombre', 100);
            $table->string('ap_paterno', 100)->nullable();
            $table->string('ap_materno', 100)->nullable();

            $table->string('curp', 18)->nullable()->unique();
            $table->string('rfc', 13)->nullable()->index();

            $table->string('cuip', 30)->nullable()->unique();

            $table->string('grado', 120)->nullable();
            $table->string('puesto', 120)->nullable();

            $table->string('adscripcion', 200)->nullable();
            $table->string('area', 200)->nullable();

            $table->string('estatus', 30)->default('ACTIVO')->index();

            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_baja')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('unidad_id')->references('id')->on('unidades')->restrictOnDelete();
            $table->foreign('turno_id')->references('id')->on('turnos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personals');
    }
};
