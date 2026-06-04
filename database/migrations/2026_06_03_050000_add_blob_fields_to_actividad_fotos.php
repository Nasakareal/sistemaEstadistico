<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            if (!Schema::hasColumn('actividades', 'foto_blob_path')) {
                $table->string('foto_blob_path', 500)->nullable()->after('foto_path');
            }

            if (!Schema::hasColumn('actividades', 'foto_thumbnail_blob_path')) {
                $table->string('foto_thumbnail_blob_path', 500)->nullable()->after('foto_thumbnail_path');
            }

            if (!Schema::hasColumn('actividades', 'foto_blob_copiada_at')) {
                $table->timestamp('foto_blob_copiada_at')->nullable()->after('foto_thumbnail_blob_path');
            }
        });

        Schema::table('actividad_fotos', function (Blueprint $table) {
            if (!Schema::hasColumn('actividad_fotos', 'foto_blob_path')) {
                $table->string('foto_blob_path', 500)->nullable()->after('foto_path');
            }

            if (!Schema::hasColumn('actividad_fotos', 'foto_thumbnail_blob_path')) {
                $table->string('foto_thumbnail_blob_path', 500)->nullable()->after('foto_thumbnail_path');
            }

            if (!Schema::hasColumn('actividad_fotos', 'foto_blob_copiada_at')) {
                $table->timestamp('foto_blob_copiada_at')->nullable()->after('foto_thumbnail_blob_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('actividad_fotos', function (Blueprint $table) {
            foreach (['foto_blob_copiada_at', 'foto_thumbnail_blob_path', 'foto_blob_path'] as $column) {
                if (Schema::hasColumn('actividad_fotos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('actividades', function (Blueprint $table) {
            foreach (['foto_blob_copiada_at', 'foto_thumbnail_blob_path', 'foto_blob_path'] as $column) {
                if (Schema::hasColumn('actividades', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
