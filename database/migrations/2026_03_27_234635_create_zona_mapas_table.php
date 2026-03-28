<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('zonas_mapa', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo', 30);
            $table->json('geojson');
            $table->string('color', 20)->nullable();
            $table->boolean('activa')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index('nombre');
            $table->index('tipo');
            $table->index('activa');
        });
    }

    public function down()
    {
        Schema::dropIfExists('zonas_mapa');
    }
};
