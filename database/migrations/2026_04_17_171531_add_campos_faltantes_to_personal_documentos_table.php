<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_documentos', function (Blueprint $table) {
            if (!Schema::hasColumn('personal_documentos', 'personal_id')) {
                $table->unsignedBigInteger('personal_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('personal_documentos', 'oficio_comision_secretario')) {
                $table->string('oficio_comision_secretario')->nullable()->after('personal_id');
            }

            if (!Schema::hasColumn('personal_documentos', 'fecha_oficio')) {
                $table->date('fecha_oficio')->nullable()->after('oficio_comision_secretario');
            }

            if (!Schema::hasColumn('personal_documentos', 'titular_firma_oficio')) {
                $table->string('titular_firma_oficio')->nullable()->after('fecha_oficio');
            }

            if (!Schema::hasColumn('personal_documentos', 'oficio_asignacion')) {
                $table->string('oficio_asignacion')->nullable()->after('titular_firma_oficio');
            }

            if (!Schema::hasColumn('personal_documentos', 'fecha_asignacion')) {
                $table->date('fecha_asignacion')->nullable()->after('oficio_asignacion');
            }

            if (!Schema::hasColumn('personal_documentos', 'titular_firma_asignacion')) {
                $table->string('titular_firma_asignacion')->nullable()->after('fecha_asignacion');
            }

            if (!Schema::hasColumn('personal_documentos', 'archivo_oficio_comision')) {
                $table->string('archivo_oficio_comision')->nullable()->after('titular_firma_asignacion');
            }

            if (!Schema::hasColumn('personal_documentos', 'archivo_oficio_asignacion')) {
                $table->string('archivo_oficio_asignacion')->nullable()->after('archivo_oficio_comision');
            }
        });

        $foreignExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'personal_documentos'
              AND COLUMN_NAME = 'personal_id'
              AND REFERENCED_TABLE_NAME = 'personals'
            LIMIT 1
        ");

        if (empty($foreignExists) && Schema::hasColumn('personal_documentos', 'personal_id')) {
            Schema::table('personal_documentos', function (Blueprint $table) {
                $table->foreign('personal_id')->references('id')->on('personals')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $foreignExists = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'personal_documentos'
              AND COLUMN_NAME = 'personal_id'
              AND REFERENCED_TABLE_NAME = 'personals'
            LIMIT 1
        ");

        Schema::table('personal_documentos', function (Blueprint $table) use ($foreignExists) {
            if (!empty($foreignExists)) {
                $table->dropForeign(['personal_id']);
            }

            $columns = [];

            if (Schema::hasColumn('personal_documentos', 'archivo_oficio_asignacion')) {
                $columns[] = 'archivo_oficio_asignacion';
            }

            if (Schema::hasColumn('personal_documentos', 'archivo_oficio_comision')) {
                $columns[] = 'archivo_oficio_comision';
            }

            if (Schema::hasColumn('personal_documentos', 'titular_firma_asignacion')) {
                $columns[] = 'titular_firma_asignacion';
            }

            if (Schema::hasColumn('personal_documentos', 'fecha_asignacion')) {
                $columns[] = 'fecha_asignacion';
            }

            if (Schema::hasColumn('personal_documentos', 'oficio_asignacion')) {
                $columns[] = 'oficio_asignacion';
            }

            if (Schema::hasColumn('personal_documentos', 'titular_firma_oficio')) {
                $columns[] = 'titular_firma_oficio';
            }

            if (Schema::hasColumn('personal_documentos', 'fecha_oficio')) {
                $columns[] = 'fecha_oficio';
            }

            if (Schema::hasColumn('personal_documentos', 'oficio_comision_secretario')) {
                $columns[] = 'oficio_comision_secretario';
            }

            if (Schema::hasColumn('personal_documentos', 'personal_id')) {
                $columns[] = 'personal_id';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
