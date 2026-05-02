<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThumbnailPathToActividadFotos extends Migration
{
    public function up()
    {
        Schema::table('actividades', function (Blueprint $table) {
            if (!Schema::hasColumn('actividades', 'foto_thumbnail_path')) {
                $table->string('foto_thumbnail_path', 255)->nullable()->after('foto_hash');
            }
        });

        Schema::table('actividad_fotos', function (Blueprint $table) {
            if (!Schema::hasColumn('actividad_fotos', 'foto_thumbnail_path')) {
                $table->string('foto_thumbnail_path', 255)->nullable()->after('foto_hash');
            }
        });
    }

    public function down()
    {
        Schema::table('actividad_fotos', function (Blueprint $table) {
            if (Schema::hasColumn('actividad_fotos', 'foto_thumbnail_path')) {
                $table->dropColumn('foto_thumbnail_path');
            }
        });

        Schema::table('actividades', function (Blueprint $table) {
            if (Schema::hasColumn('actividades', 'foto_thumbnail_path')) {
                $table->dropColumn('foto_thumbnail_path');
            }
        });
    }
}
