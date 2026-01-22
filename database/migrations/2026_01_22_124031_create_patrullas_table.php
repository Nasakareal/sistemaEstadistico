<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('patrullas', function (Blueprint $table) {
            $table->id();

            $table->string('numero_economico', 20)->unique();

            $table->foreignId('unidad_id')
                ->nullable()
                ->constrained('unidades')
                ->nullOnDelete();

            $table->foreignId('turno_id')
                ->nullable()
                ->constrained('turnos')
                ->nullOnDelete();

            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['unidad_id', 'turno_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('patrullas');
    }
};
