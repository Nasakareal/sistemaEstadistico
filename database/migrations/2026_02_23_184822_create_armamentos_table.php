<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armamentos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('unidad_id')->index();

            $table->string('tipo', 80)->nullable();
            $table->string('marca', 80)->nullable();
            $table->string('modelo', 80)->nullable();

            $table->string('matricula', 80)->nullable()->unique();
            $table->string('serie', 80)->nullable()->index();

            $table->string('calibre', 40)->nullable();

            $table->string('estatus', 30)->default('ACTIVO')->index();
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('unidad_id')->references('id')->on('unidades')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armamentos');
    }
};
