<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operativo_dispositivo_persona', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operativo_dispositivo_id')->constrained('operativo_dispositivos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo_participacion')->nullable();
            $table->string('curp')->nullable();
            $table->string('telefono')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('nombre');
            $table->index('tipo_participacion');
            $table->index('curp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operativo_dispositivo_persona');
    }
};
