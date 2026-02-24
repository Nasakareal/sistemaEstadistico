<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_asignacions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('personal_id');

            $table->unsignedBigInteger('vehiculo_id')->nullable();
            $table->unsignedBigInteger('armamento_id')->nullable();

            $table->date('fecha_asignacion');
            $table->date('fecha_fin')->nullable();

            $table->string('folio', 80)->nullable()->index();
            $table->unsignedBigInteger('documento_id')->nullable();
            $table->text('observaciones')->nullable();

            $table->boolean('activo')->default(true)->index();

            $table->timestamps();

            $table->foreign('personal_id')->references('id')->on('personals')->cascadeOnDelete();
            $table->foreign('vehiculo_id')->references('id')->on('vehiculos')->nullOnDelete();
            $table->foreign('armamento_id')->references('id')->on('armamentos')->nullOnDelete();
            $table->foreign('documento_id')->references('id')->on('personal_documentos')->nullOnDelete();

            $table->index(['personal_id', 'activo']);
            $table->index(['vehiculo_id', 'activo']);
            $table->index(['armamento_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_asignacions');
    }
};
