<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puestas_disposicion', function (Blueprint $table) {
            $table->unique(
                ['anio', 'unidad_id', 'numero_puesta'],
                'uniq_puestas_disposicion_anio_unidad_numero'
            );
        });
    }

    public function down(): void
    {
        Schema::table('puestas_disposicion', function (Blueprint $table) {
            $table->dropUnique('uniq_puestas_disposicion_anio_unidad_numero');
        });
    }
};
