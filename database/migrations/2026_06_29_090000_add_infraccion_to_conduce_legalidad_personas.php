<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_personas')) {
            return;
        }

        Schema::table('conduce_legalidad_personas', function (Blueprint $table) {
            if (!Schema::hasColumn('conduce_legalidad_personas', 'licencia_punto_infraccion_id')) {
                $table->foreignId('licencia_punto_infraccion_id')
                    ->nullable()
                    ->after('raw_licencia_qr')
                    ->constrained('licencia_punto_infracciones')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conduce_legalidad_personas', 'infraccion_codigo')) {
                $table->string('infraccion_codigo', 80)->nullable()->after('licencia_punto_infraccion_id');
            }

            if (!Schema::hasColumn('conduce_legalidad_personas', 'fundamento_legal')) {
                $table->longText('fundamento_legal')->nullable()->after('infraccion_codigo');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_personas')) {
            return;
        }

        Schema::table('conduce_legalidad_personas', function (Blueprint $table) {
            if (Schema::hasColumn('conduce_legalidad_personas', 'licencia_punto_infraccion_id')) {
                $table->dropConstrainedForeignId('licencia_punto_infraccion_id');
            }

            foreach (['fundamento_legal', 'infraccion_codigo'] as $column) {
                if (Schema::hasColumn('conduce_legalidad_personas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
