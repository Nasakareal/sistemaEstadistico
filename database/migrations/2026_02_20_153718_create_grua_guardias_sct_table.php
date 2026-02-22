<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grua_guardias_sct', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('grua_id');

            $table->unsignedBigInteger('tramo_id')->nullable();

            $table->unsignedTinyInteger('dia_inicio');
            $table->unsignedTinyInteger('dia_fin');


            $table->unsignedTinyInteger('prioridad')->default(1);
            $table->boolean('activo')->default(true);
            $table->string('notas')->nullable();

            $table->timestamps();

            $table->foreign('grua_id')->references('id')->on('gruas')->cascadeOnDelete();
            $table->foreign('tramo_id')->references('id')->on('tramos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grua_guardias_sct');
    }
};
