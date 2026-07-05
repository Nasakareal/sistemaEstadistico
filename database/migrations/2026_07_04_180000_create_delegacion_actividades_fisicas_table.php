<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegacion_actividades_fisicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegacion_id')->nullable()->constrained('delegaciones')->nullOnDelete();
            $table->date('fecha')->nullable();
            $table->time('hora')->nullable();
            $table->string('tipo_ejercicio', 180);
            $table->unsignedSmallInteger('elementos_participantes')->default(0);
            $table->string('foto_path');
            $table->string('foto_nombre_original')->nullable();
            $table->string('foto_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['fecha', 'delegacion_id']);
            $table->index('tipo_ejercicio');
            $table->index('foto_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegacion_actividades_fisicas');
    }
};
