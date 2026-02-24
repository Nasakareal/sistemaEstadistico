<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documento_tipos', function (Blueprint $table) {
            $table->id();

            $table->string('clave', 40)->unique();
            $table->string('nombre', 160);

            $table->boolean('requiere_vigencia')->default(false)->index();
            $table->integer('dias_vigencia')->nullable();

            $table->boolean('sensible')->default(true)->index();
            $table->boolean('activo')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_tipos');
    }
};
