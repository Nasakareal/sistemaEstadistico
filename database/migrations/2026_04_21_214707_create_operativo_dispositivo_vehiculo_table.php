<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operativo_dispositivo_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operativo_dispositivo_id')->constrained('operativo_dispositivos')->cascadeOnDelete();
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->cascadeOnDelete();
            $table->string('rol')->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['operativo_dispositivo_id', 'vehiculo_id'], 'uniq_operativo_dispositivo_vehiculo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operativo_dispositivo_vehiculo');
    }
};
