<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inegi_choques_envios', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 30)->default('pendiente')->index();
            $table->unsignedInteger('intentos')->default(0);
            $table->json('destinatarios')->nullable();
            $table->string('archivo_nombre')->nullable();
            $table->string('archivo_sha256', 64)->nullable();
            $table->unsignedInteger('total_registros')->default(0);
            $table->timestamp('enviado_at')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamps();

            $table->unique(['fecha_inicio', 'fecha_fin'], 'inegi_choques_envios_rango_unique');
        });

        Schema::create('inegi_choques_envio_hechos', function (Blueprint $table) {
            $table->foreignId('envio_id')
                ->constrained('inegi_choques_envios')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('hecho_id');

            $table->primary(['envio_id', 'hecho_id'], 'inegi_envio_hecho_primary');
            $table->index('hecho_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inegi_choques_envio_hechos');
        Schema::dropIfExists('inegi_choques_envios');
    }
};
