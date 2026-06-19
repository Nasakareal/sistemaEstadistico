<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        if (!Schema::hasColumn('licencia_punto_infracciones', 'fundamento_legal')) {
            Schema::table('licencia_punto_infracciones', function (Blueprint $table) {
                $table->text('fundamento_legal')->nullable()->after('descripcion');
            });
        }

        DB::table('licencia_punto_infracciones')
            ->whereNull('fundamento_legal')
            ->update([
                'fundamento_legal' => 'Fundamentado en el Reglamento de la Ley de Movilidad y Seguridad Vial vigente en el Estado.',
            ]);
    }

    public function down(): void
    {
        if (
            Schema::hasTable('licencia_punto_infracciones')
            && Schema::hasColumn('licencia_punto_infracciones', 'fundamento_legal')
        ) {
            Schema::table('licencia_punto_infracciones', function (Blueprint $table) {
                $table->dropColumn('fundamento_legal');
            });
        }
    }
};
