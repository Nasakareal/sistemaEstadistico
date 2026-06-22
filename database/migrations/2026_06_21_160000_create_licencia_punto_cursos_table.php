<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_cursos')) {
            Schema::create('licencia_punto_cursos', function (Blueprint $table) {
                $table->id();
                $table->string('folio', 80)->unique();
                $table->string('nombre', 180);
                $table->text('descripcion')->nullable();
                $table->string('lugar', 180)->nullable();
                $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('unidad_id')->nullable()->constrained('unidades')->nullOnDelete();
                $table->dateTime('fecha_inicio')->nullable()->index();
                $table->dateTime('fecha_fin')->nullable();
                $table->unsignedTinyInteger('horas_totales')->default(15);
                $table->unsignedTinyInteger('puntos_recuperacion')->default(12);
                $table->unsignedSmallInteger('cupo')->nullable();
                $table->string('estado', 30)->default('programado')->index();
                $table->dateTime('closed_at')->nullable();
                $table->text('observaciones')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['instructor_id', 'estado']);
            });
        }

        if (!Schema::hasTable('licencia_punto_curso_participantes')) {
            Schema::create('licencia_punto_curso_participantes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curso_id')->constrained('licencia_punto_cursos')->cascadeOnDelete();
                $table->foreignId('cuenta_id')->nullable()->constrained('licencia_punto_cuentas')->nullOnDelete();
                $table->foreignId('conductor_id')->nullable()->constrained('conductores')->nullOnDelete();
                $table->foreignId('movimiento_id')->nullable()->constrained('licencia_punto_movimientos')->nullOnDelete();
                $table->string('numero_licencia', 80);
                $table->string('titular_nombre', 255);
                $table->string('curp', 18)->nullable()->index();
                $table->string('telefono', 20)->nullable();
                $table->decimal('asistencia_horas', 5, 2)->default(0);
                $table->string('estado', 30)->default('inscrito')->index();
                $table->unsignedTinyInteger('puntos_acreditados')->default(0);
                $table->dateTime('acreditado_at')->nullable();
                $table->text('observaciones')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['curso_id', 'numero_licencia'], 'lp_curso_participantes_unique');
                $table->index(['cuenta_id', 'estado']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('licencia_punto_curso_participantes');
        Schema::dropIfExists('licencia_punto_cursos');
    }
};
