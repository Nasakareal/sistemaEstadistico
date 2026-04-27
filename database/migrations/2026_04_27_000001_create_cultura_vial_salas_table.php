<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cultura_vial_salas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 8)->unique();
            $table->string('nombre', 120);
            $table->string('juego_slug', 80)->default('ciudad_segura');
            $table->string('estado', 20)->default('abierta');
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at']);
            $table->index(['instructor_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cultura_vial_salas');
    }
};
