<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_vehiculo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('vehiculo_id');
            $table->timestamps();

            $table->foreign('actividad_id')->references('id')->on('actividades')->onDelete('cascade');
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->onDelete('cascade');

            $table->unique(['actividad_id', 'vehiculo_id']);
            $table->index('actividad_id');
            $table->index('vehiculo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_vehiculo');
    }
};
