<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('conduce_legalidad_capturas')) {
            return;
        }

        Schema::table('conduce_legalidad_capturas', function (Blueprint $table) {
            if (!Schema::hasColumn('conduce_legalidad_capturas', 'licencia_punto_infraccion_id')) {
                $table->foreignId('licencia_punto_infraccion_id')
                    ->nullable()
                    ->after('operativo_id')
                    ->constrained('licencia_punto_infracciones')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('conduce_legalidad_capturas', 'infraccion_codigo')) {
                $table->string('infraccion_codigo', 80)->nullable()->after('licencia_punto_infraccion_id');
            }

            if (!Schema::hasColumn('conduce_legalidad_capturas', 'fundamento_legal')) {
                $table->longText('fundamento_legal')->nullable()->after('infraccion_codigo');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_capturas')) {
            return;
        }

        Schema::table('conduce_legalidad_capturas', function (Blueprint $table) {
            if (Schema::hasColumn('conduce_legalidad_capturas', 'licencia_punto_infraccion_id')) {
                $table->dropConstrainedForeignId('licencia_punto_infraccion_id');
            }

            foreach (['fundamento_legal', 'infraccion_codigo'] as $column) {
                if (Schema::hasColumn('conduce_legalidad_capturas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
