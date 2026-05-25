<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fomento_cultura_vial_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('fomento_cultura_vial_detalles', 'nombre_institucion')) {
                $table->string('nombre_institucion', 255)->nullable()->after('programa_nombre');
            }

            if (!Schema::hasColumn('fomento_cultura_vial_detalles', 'domicilio')) {
                $table->string('domicilio', 255)->nullable()->after('nombre_institucion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fomento_cultura_vial_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('fomento_cultura_vial_detalles', 'domicilio')) {
                $table->dropColumn('domicilio');
            }

            if (Schema::hasColumn('fomento_cultura_vial_detalles', 'nombre_institucion')) {
                $table->dropColumn('nombre_institucion');
            }
        });
    }
};
