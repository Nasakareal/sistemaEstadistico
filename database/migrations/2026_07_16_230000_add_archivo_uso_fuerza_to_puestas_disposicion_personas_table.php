<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('puestas_disposicion_personas', 'archivo_uso_fuerza')) {
            Schema::table('puestas_disposicion_personas', function (Blueprint $table) {
                $table->string('archivo_uso_fuerza')->nullable()->after('observaciones');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('puestas_disposicion_personas', 'archivo_uso_fuerza')) {
            Schema::table('puestas_disposicion_personas', function (Blueprint $table) {
                $table->dropColumn('archivo_uso_fuerza');
            });
        }
    }
};
