<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_servicio_patrullas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patrulla_id')
                ->constrained('patrullas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('turno_id')
                ->nullable()
                ->constrained('turnos')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->date('fecha');

            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            $table->unsignedBigInteger('capturado_por_user_id')->nullable();
            $table->string('capturado_por_nombre', 150);

            $table->unsignedInteger('kilometraje_inicio')->nullable();
            $table->unsignedInteger('kilometraje_fin')->nullable();

            $table->decimal('combustible_inicio', 5, 2)->nullable();
            $table->decimal('combustible_fin', 5, 2)->nullable();

            $table->text('observaciones')->nullable();

            $table->enum('estatus', [
                'abierta',
                'cerrada'
            ])->default('abierta');

            $table->timestamp('cerrada_at')->nullable();

            $table->timestamps();

            $table->foreign('capturado_por_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['patrulla_id', 'fecha']);
            $table->index(['fecha', 'turno_id']);
            $table->index('estatus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_servicio_patrullas');
    }
};
