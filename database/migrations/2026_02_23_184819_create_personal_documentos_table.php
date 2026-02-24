<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_documentos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('personal_id');
            $table->unsignedBigInteger('documento_tipo_id');

            $table->string('numero', 80)->nullable()->index();

            $table->date('fecha_emision')->nullable();
            $table->date('fecha_vencimiento')->nullable();

            $table->string('archivo_path', 500)->nullable();
            $table->string('archivo_nombre', 255)->nullable();
            $table->string('archivo_mime', 120)->nullable();
            $table->unsignedBigInteger('archivo_size')->nullable();
            $table->string('hash_sha256', 64)->nullable()->index();

            $table->boolean('activo')->default(true)->index();
            $table->text('observaciones')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('personal_id')->references('id')->on('personals')->cascadeOnDelete();
            $table->foreign('documento_tipo_id')->references('id')->on('documento_tipos')->restrictOnDelete();

            $table->index(['personal_id', 'documento_tipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_documentos');
    }
};
