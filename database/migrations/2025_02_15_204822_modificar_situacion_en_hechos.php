<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModificarSituacionEnHechos extends Migration
{
    public function up()
    {
        Schema::table('hechos', function (Blueprint $table) {
            // Crear una nueva columna temporal con el ENUM en mayúsculas
            $table->enum('nueva_situacion', ['RESUELTO', 'PENDIENTE', 'TURNADO', 'REPORTE'])
                  ->default('PENDIENTE');
        });

        // Copiar los valores actuales a la nueva columna, convirtiéndolos en mayúsculas
        DB::statement("UPDATE hechos SET nueva_situacion = UPPER(situacion)");

        Schema::table('hechos', function (Blueprint $table) {
            // Eliminar la columna antigua
            $table->dropColumn('situacion');
        });

        Schema::table('hechos', function (Blueprint $table) {
            // Renombrar la nueva columna como 'situacion'
            $table->renameColumn('nueva_situacion', 'situacion');
        });
    }

    public function down()
    {
        Schema::table('hechos', function (Blueprint $table) {
            $table->enum('situacion_antigua', ['RESUELTO', 'PENDIENTE', 'TURNADO', 'REPORTE'])
                  ->default('PENDIENTE');
        });

        DB::statement("UPDATE hechos SET situacion_antigua = situacion");

        Schema::table('hechos', function (Blueprint $table) {
            $table->dropColumn('situacion');
        });

        Schema::table('hechos', function (Blueprint $table) {
            $table->renameColumn('situacion_antigua', 'situacion');
        });
    }

}
