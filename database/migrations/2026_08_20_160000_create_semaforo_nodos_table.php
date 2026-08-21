<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('semaforo_nodos', function (Blueprint $table) {
            $table->id();
            $table->string('node_id', 32)->unique();
            $table->string('ruta', 80)->unique();
            $table->string('nombre', 160);
            $table->string('ubicacion', 200)->nullable();
            $table->string('vialidad_principal', 160)->nullable();
            $table->string('vialidad_transversal', 160)->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->json('configuracion')->nullable();
            $table->string('plan_activo', 40)->nullable();
            $table->time('horario_inicio')->nullable();
            $table->time('horario_fin')->nullable();
            $table->string('horario_estado', 30)->nullable();
            $table->string('estado_operativo', 30)->default('sin_confirmar');
            $table->timestamp('ultimo_contacto_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'nombre']);
            $table->index('ultimo_contacto_at');
        });

        DB::table('semaforo_nodos')->insert([
            'node_id' => 'FBF61B44',
            'ruta' => 'QUIROGA_SALIDA',
            'nombre' => 'SALIDA QUIROGA',
            'ubicacion' => 'Morelia, Michoacán',
            'vialidad_principal' => 'SALIDA A QUIROGA',
            'vialidad_transversal' => 'CRUCE TRANSVERSAL',
            'plan_activo' => 'LOCAL CC1',
            'horario_inicio' => '18:30:00',
            'horario_fin' => '19:30:00',
            'horario_estado' => 'SAFE',
            'estado_operativo' => 'configurado',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('semaforo_nodos');
    }
};
