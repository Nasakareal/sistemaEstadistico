<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRetentionFieldsToActividadFotos extends Migration
{
    public function up()
    {
        Schema::table('actividades', function (Blueprint $table) {
            if (!Schema::hasColumn('actividades', 'foto_archivo_zip_path')) {
                $table->string('foto_archivo_zip_path', 255)->nullable()->after('foto_hash');
            }

            if (!Schema::hasColumn('actividades', 'foto_archivada_at')) {
                $table->timestamp('foto_archivada_at')->nullable()->after('foto_archivo_zip_path');
            }

            if (!Schema::hasColumn('actividades', 'foto_eliminada_at')) {
                $table->timestamp('foto_eliminada_at')->nullable()->after('foto_archivada_at');
            }
        });

        Schema::table('actividad_fotos', function (Blueprint $table) {
            if (!Schema::hasColumn('actividad_fotos', 'foto_archivo_zip_path')) {
                $table->string('foto_archivo_zip_path', 255)->nullable()->after('foto_hash');
            }

            if (!Schema::hasColumn('actividad_fotos', 'foto_archivada_at')) {
                $table->timestamp('foto_archivada_at')->nullable()->after('foto_archivo_zip_path');
            }

            if (!Schema::hasColumn('actividad_fotos', 'foto_eliminada_at')) {
                $table->timestamp('foto_eliminada_at')->nullable()->after('foto_archivada_at');
            }
        });
    }

    public function down()
    {
        Schema::table('actividad_fotos', function (Blueprint $table) {
            $columns = [];

            foreach (['foto_archivo_zip_path', 'foto_archivada_at', 'foto_eliminada_at'] as $column) {
                if (Schema::hasColumn('actividad_fotos', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('actividades', function (Blueprint $table) {
            $columns = [];

            foreach (['foto_archivo_zip_path', 'foto_archivada_at', 'foto_eliminada_at'] as $column) {
                if (Schema::hasColumn('actividades', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
