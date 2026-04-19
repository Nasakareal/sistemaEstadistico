<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_fotos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('personal_id');
            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_fotos');
    }
};
