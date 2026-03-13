<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operativo_catalogos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete()->cascadeOnUpdate();
            $table->string('nombre');
            $table->string('slug')->nullable()->unique();
            $table->string('tipo', 50)->default('OPERATIVO');
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['unidad_id', 'activo']);
            $table->index(['unidad_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operativo_catalogos');
    }
};
