<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alcoholimetria_reportes_mensuales', function (Blueprint $table) {
            $table->id();
            $table->date('mes')->unique();
            $table->string('estado', 30)->default('pendiente')->index();
            $table->unsignedInteger('intentos')->default(0);
            $table->json('destinatarios')->nullable();
            $table->string('archivo_nombre')->nullable();
            $table->string('archivo_sha256', 64)->nullable();
            $table->json('resumen')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alcoholimetria_reportes_mensuales');
    }
};
