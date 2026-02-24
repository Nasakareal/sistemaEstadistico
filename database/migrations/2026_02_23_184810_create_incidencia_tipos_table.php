<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencia_tipos', function (Blueprint $table) {
            $table->id();

            $table->string('clave', 30)->unique();
            $table->string('nombre', 120);
            $table->string('categoria', 50)->nullable()->index();

            $table->boolean('descuenta')->default(false)->index();
            $table->boolean('requiere_documento')->default(false)->index();
            $table->boolean('activo')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencia_tipos');
    }
};
