<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegaciones', function (Blueprint $table) {
            $table->id();

            $table->string('clave', 3)->nullable();
            $table->string('nombre', 120);
            $table->string('municipio', 120)->nullable();

            $table->boolean('activa')->default(true);

            $table->timestamps();

            $table->unique('clave');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegaciones');
    }
};
