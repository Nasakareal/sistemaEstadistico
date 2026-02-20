<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tramos', function (Blueprint $table) {
            $table->id();
            $table->string('carretera', 50)->nullable();
            $table->string('nombre')->nullable();
            $table->decimal('km_inicio', 8, 2)->nullable();
            $table->decimal('km_fin', 8, 2)->nullable();
            $table->lineString('geom')->nullable();
            $table->polygon('bbox')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['carretera', 'km_inicio', 'km_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tramos');
    }
};
