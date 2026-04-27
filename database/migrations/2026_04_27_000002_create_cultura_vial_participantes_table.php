<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cultura_vial_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sala_id')->constrained('cultura_vial_salas')->cascadeOnDelete();
            $table->string('nombre', 80);
            $table->string('join_token', 80)->unique();
            $table->unsignedInteger('mejor_puntaje')->default(0);
            $table->unsignedInteger('intentos')->default(0);
            $table->timestamp('ultimo_intento_at')->nullable();
            $table->timestamps();

            $table->index(['sala_id', 'mejor_puntaje']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cultura_vial_participantes');
    }
};
