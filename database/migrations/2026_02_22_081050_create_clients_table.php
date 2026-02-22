<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 190)->index();
            $table->enum('tipo', ['ASEGURADORA','FLOTILLA','GOBIERNO','OTRO'])
                ->default('OTRO')
                ->index();

            $table->string('contacto_nombre', 190)->nullable();
            $table->string('contacto_email', 190)->nullable()->index();
            $table->string('contacto_telefono', 50)->nullable();

            $table->boolean('activo')->default(true)->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
