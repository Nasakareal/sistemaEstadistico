<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_curso_materiales')) {
            Schema::create('licencia_punto_curso_materiales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curso_id')->constrained('licencia_punto_cursos')->cascadeOnDelete();
                $table->string('titulo', 180);
                $table->string('tipo', 30)->default('pdf')->index();
                $table->string('archivo_path', 255)->nullable();
                $table->string('url', 500)->nullable();
                $table->text('contenido')->nullable();
                $table->unsignedSmallInteger('orden')->default(0);
                $table->boolean('activo')->default(true)->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['curso_id', 'orden']);
            });
        }

        if (Schema::hasTable('licencia_punto_curso_participantes')) {
            Schema::table('licencia_punto_curso_participantes', function (Blueprint $table) {
                if (!Schema::hasColumn('licencia_punto_curso_participantes', 'calificacion')) {
                    $table->unsignedTinyInteger('calificacion')->nullable()->after('asistencia_horas');
                }

                if (!Schema::hasColumn('licencia_punto_curso_participantes', 'calificado_at')) {
                    $table->dateTime('calificado_at')->nullable()->after('calificacion');
                }

                if (!Schema::hasColumn('licencia_punto_curso_participantes', 'calificado_by')) {
                    $table->foreignId('calificado_by')->nullable()->after('calificado_at')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('licencia_punto_curso_participantes')) {
            Schema::table('licencia_punto_curso_participantes', function (Blueprint $table) {
                if (Schema::hasColumn('licencia_punto_curso_participantes', 'calificado_by')) {
                    $table->dropForeign(['calificado_by']);
                    $table->dropColumn('calificado_by');
                }

                foreach (['calificado_at', 'calificacion'] as $column) {
                    if (Schema::hasColumn('licencia_punto_curso_participantes', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('licencia_punto_curso_materiales');
    }
};
