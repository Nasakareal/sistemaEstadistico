<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_capturas')
            || Schema::hasColumn('conduce_legalidad_capturas', 'actividad_id')) {
            return;
        }

        Schema::table('conduce_legalidad_capturas', function (Blueprint $table) {
            $table->foreignId('actividad_id')
                ->nullable()
                ->after('operativo_id')
                ->unique()
                ->constrained('actividades')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_capturas')
            || !Schema::hasColumn('conduce_legalidad_capturas', 'actividad_id')) {
            return;
        }

        Schema::table('conduce_legalidad_capturas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('actividad_id');
        });
    }
};
