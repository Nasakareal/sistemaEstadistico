<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrega_recepcion_patrullas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patrulla_id')
                ->constrained('patrullas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('fecha');

            $table->time('hora_programada')->default('07:00:00');
            $table->time('hora_real')->nullable();

            $table->unsignedBigInteger('entrega_user_id')->nullable();
            $table->string('entrega_nombre', 150);

            $table->unsignedBigInteger('recibe_user_id')->nullable();
            $table->string('recibe_nombre', 150);

            $table->unsignedInteger('kilometraje')->nullable();

            $table->decimal('nivel_combustible', 5, 2)->nullable();

            $table->enum('estado_carroceria', [
                'bueno',
                'regular',
                'malo'
            ])->nullable();

            $table->enum('estado_interiores', [
                'bueno',
                'regular',
                'malo'
            ])->nullable();

            $table->enum('estado_llantas', [
                'bueno',
                'regular',
                'malo'
            ])->nullable();

            $table->enum('estado_luces', [
                'bueno',
                'regular',
                'malo'
            ])->nullable();

            $table->enum('estado_torreta', [
                'bueno',
                'regular',
                'malo',
                'no_aplica'
            ])->nullable();

            $table->enum('estado_sirena', [
                'bueno',
                'regular',
                'malo',
                'no_aplica'
            ])->nullable();

            $table->enum('estado_radio', [
                'bueno',
                'regular',
                'malo',
                'no_aplica'
            ])->nullable();

            $table->enum('estado_mecanico', [
                'bueno',
                'regular',
                'malo'
            ])->nullable();

            $table->boolean('trae_refaccion')->nullable();
            $table->boolean('trae_gato')->nullable();
            $table->boolean('trae_llave_cruz')->nullable();
            $table->boolean('trae_extintor')->nullable();
            $table->boolean('trae_botiquin')->nullable();

            $table->text('equipo_adicional')->nullable();
            $table->text('danos_existentes')->nullable();
            $table->text('novedades')->nullable();
            $table->text('observaciones')->nullable();

            $table->string('foto_frontal')->nullable();
            $table->string('foto_trasera')->nullable();
            $table->string('foto_lateral_izquierdo')->nullable();
            $table->string('foto_lateral_derecho')->nullable();
            $table->string('foto_tablero')->nullable();

            $table->boolean('aceptada_entrega')->default(false);
            $table->boolean('aceptada_recepcion')->default(false);

            $table->timestamp('entrega_confirmada_at')->nullable();
            $table->timestamp('recepcion_confirmada_at')->nullable();

            $table->timestamps();

            $table->foreign('entrega_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('recibe_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index(['patrulla_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrega_recepcion_patrullas');
    }
};
