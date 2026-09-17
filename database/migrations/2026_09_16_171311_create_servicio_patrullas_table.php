<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_patrullas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bitacora_servicio_patrulla_id')
                ->constrained('bitacora_servicio_patrullas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('patrulla_id')
                ->constrained('patrullas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('fecha');

            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();

            $table->string('tipo_servicio', 150);

            $table->string('folio', 100)->nullable();

            $table->string('lugar', 255)->nullable();
            $table->string('colonia', 150)->nullable();
            $table->string('municipio', 150)->nullable();

            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();

            $table->text('descripcion');

            $table->text('resultado')->nullable();
            $table->text('observaciones')->nullable();

            $table->unsignedBigInteger('registrado_por_user_id')->nullable();
            $table->string('registrado_por_nombre', 150);

            $table->timestamps();

            $table->foreign('registrado_por_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index('bitacora_servicio_patrulla_id');
            $table->index(['patrulla_id', 'fecha']);
            $table->index('tipo_servicio');
            $table->index('folio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_patrullas');
    }
};
