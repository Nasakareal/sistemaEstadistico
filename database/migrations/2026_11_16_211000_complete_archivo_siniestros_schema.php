<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('archivo_areas', function (Blueprint $table) {
            if (!Schema::hasColumn('archivo_areas', 'activo')) {
                $table->boolean('activo')->default(true)->after('plano_alto');
            }
        });

        Schema::table('archivo_documentos', function (Blueprint $table) {
            if (!Schema::hasColumn('archivo_documentos', 'ruta_archivo')) {
                $table->string('ruta_archivo', 500)->nullable()->after('disco');
            }
            if (!Schema::hasColumn('archivo_documentos', 'mime_type')) {
                $table->string('mime_type', 100)->nullable()->after('ruta_archivo');
            }
            if (!Schema::hasColumn('archivo_documentos', 'tamano_bytes')) {
                $table->unsignedBigInteger('tamano_bytes')->nullable()->after('mime_type');
            }
            if (!Schema::hasColumn('archivo_documentos', 'sha256')) {
                $table->char('sha256', 64)->nullable()->index()->after('tamano_bytes');
            }
            if (!Schema::hasColumn('archivo_documentos', 'pagina_inicio')) {
                $table->unsignedInteger('pagina_inicio')->nullable()->after('sha256');
            }
            if (!Schema::hasColumn('archivo_documentos', 'pagina_fin')) {
                $table->unsignedInteger('pagina_fin')->nullable()->after('pagina_inicio');
            }
            if (!Schema::hasColumn('archivo_documentos', 'estado_revision')) {
                $table->string('estado_revision', 30)->default('pendiente')->after('pagina_fin');
            }
            if (!Schema::hasColumn('archivo_documentos', 'usuario_carga_id')) {
                $table->foreignId('usuario_carga_id')->nullable()->after('estado_revision')
                    ->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // No retiramos columnas porque pueden contener documentos ya digitalizados.
    }
};
