<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('destacamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->constrained('unidades')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('clave')->nullable();
            $table->string('nombre');
            $table->string('municipio')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['unidad_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('destacamentos');
    }
};
