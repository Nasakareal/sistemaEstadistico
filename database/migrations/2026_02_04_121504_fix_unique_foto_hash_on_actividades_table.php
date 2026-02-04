<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {

            $table->dropUnique('uq_act_foto_hash');
            $table->dropUnique('uq_act_cat_sub_nombre');
            $table->unique(['nombre', 'foto_hash'], 'uq_act_nombre_foto_hash');
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {

            $table->dropUnique('uq_act_nombre_foto_hash');

            $table->unique('foto_hash', 'uq_act_foto_hash');
            $table->unique(
                ['actividad_categoria_id', 'actividad_subcategoria_id', 'nombre'],
                'uq_act_cat_sub_nombre'
            );
        });
    }
};
