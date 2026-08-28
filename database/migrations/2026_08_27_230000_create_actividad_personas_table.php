<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->nullable()->constrained('vehiculos')->nullOnDelete();
            $table->string('tipo_participacion', 30);
            $table->string('nombre', 255);
            $table->string('telefono', 30)->nullable();
            $table->string('domicilio', 255)->nullable();
            $table->string('sexo', 30)->nullable();
            $table->string('nacionalidad', 80)->nullable();
            $table->string('ocupacion', 255)->nullable();
            $table->unsignedTinyInteger('edad')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['actividad_id', 'tipo_participacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_personas');
    }
};
