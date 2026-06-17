<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            Schema::create('licencia_punto_infracciones', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 50)->unique();
                $table->string('nombre', 150);
                $table->unsignedTinyInteger('puntos');
                $table->text('descripcion')->nullable();
                $table->boolean('activa')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('licencia_punto_cuentas')) {
            Schema::create('licencia_punto_cuentas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conductor_id')->nullable()->constrained('conductores')->nullOnDelete();
                $table->string('numero_licencia', 80)->unique();
                $table->string('tipo_licencia', 60)->nullable();
                $table->string('titular_nombre', 255);
                $table->string('curp', 18)->nullable()->index();
                $table->string('telefono', 20)->nullable();
                $table->date('fecha_emision')->nullable();
                $table->date('fecha_vencimiento')->nullable();
                $table->unsignedTinyInteger('saldo_actual')->default(8)->index();
                $table->string('estado', 50)->default('vigente')->index();
                $table->dateTime('fecha_ultima_infraccion')->nullable()->index();
                $table->dateTime('fecha_agotamiento')->nullable();
                $table->unsignedSmallInteger('reincidencias_cero')->default(0);
                $table->string('expediente_folio', 80)->nullable();
                $table->string('oficio_folio', 80)->nullable();
                $table->dateTime('finanzas_notificado_at')->nullable();
                $table->dateTime('titular_notificado_at')->nullable();
                $table->string('token_consulta', 64)->unique();
                $table->text('observaciones')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('licencia_punto_movimientos')) {
            Schema::create('licencia_punto_movimientos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cuenta_id')->constrained('licencia_punto_cuentas')->cascadeOnDelete();
                $table->foreignId('infraccion_id')->nullable()->constrained('licencia_punto_infracciones')->nullOnDelete();
                $table->foreignId('hecho_id')->nullable()->constrained('hechos')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('tipo', 50)->index();
                $table->smallInteger('puntos');
                $table->unsignedTinyInteger('saldo_anterior');
                $table->unsignedTinyInteger('saldo_nuevo');
                $table->dateTime('fecha_movimiento')->index();
                $table->string('referencia', 120)->nullable();
                $table->text('descripcion')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['cuenta_id', 'fecha_movimiento']);
            });
        }

        if (!Schema::hasTable('licencia_punto_alertas')) {
            Schema::create('licencia_punto_alertas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cuenta_id')->constrained('licencia_punto_cuentas')->cascadeOnDelete();
                $table->foreignId('movimiento_id')->nullable()->constrained('licencia_punto_movimientos')->nullOnDelete();
                $table->string('tipo', 50)->index();
                $table->string('nivel', 50)->index();
                $table->unsignedTinyInteger('saldo_disparador');
                $table->text('mensaje');
                $table->dateTime('atendida_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['cuenta_id', 'tipo']);
            });
        }

        $this->seedCatalogoInfracciones();
    }

    public function down(): void
    {
        Schema::dropIfExists('licencia_punto_alertas');
        Schema::dropIfExists('licencia_punto_movimientos');
        Schema::dropIfExists('licencia_punto_cuentas');
        Schema::dropIfExists('licencia_punto_infracciones');
    }

    private function seedCatalogoInfracciones(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        $now = now();
        $infracciones = [
            [
                'codigo' => 'EXCESO_VELOCIDAD',
                'nombre' => 'Exceso de velocidad',
                'puntos' => 2,
                'descripcion' => 'Conducir por encima del limite permitido.',
            ],
            [
                'codigo' => 'CELULAR_CONDUCIR',
                'nombre' => 'Celular al conducir',
                'puntos' => 1,
                'descripcion' => 'Usar telefono o dispositivo movil mientras conduce.',
            ],
            [
                'codigo' => 'SEMAFORO_ROJO',
                'nombre' => 'Semaforo rojo',
                'puntos' => 3,
                'descripcion' => 'No respetar luz roja o indicacion de alto.',
            ],
        ];

        foreach ($infracciones as $infraccion) {
            $exists = DB::table('licencia_punto_infracciones')
                ->where('codigo', $infraccion['codigo'])
                ->exists();

            DB::table('licencia_punto_infracciones')->updateOrInsert(
                ['codigo' => $infraccion['codigo']],
                array_merge($infraccion, [
                    'activa' => true,
                    'updated_at' => $now,
                ], $exists ? [] : ['created_at' => $now])
            );
        }
    }
};
