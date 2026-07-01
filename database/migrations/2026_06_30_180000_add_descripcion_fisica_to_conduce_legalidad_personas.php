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
            if (!Schema::hasColumn('conduce_legalidad_personas', 'edad_aproximada')) {
                $table->string('edad_aproximada', 40)->nullable()->after('edad');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'complexion')) {
                $table->string('complexion', 80)->nullable()->after('edad_aproximada');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'estatura')) {
                $table->string('estatura', 80)->nullable()->after('complexion');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'tez')) {
                $table->string('tez', 80)->nullable()->after('estatura');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'cabello')) {
                $table->string('cabello', 80)->nullable()->after('tez');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'prenda_superior')) {
                $table->string('prenda_superior', 80)->nullable()->after('cabello');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'color_superior')) {
                $table->string('color_superior', 80)->nullable()->after('prenda_superior');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'prenda_inferior')) {
                $table->string('prenda_inferior', 80)->nullable()->after('color_superior');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'color_inferior')) {
                $table->string('color_inferior', 80)->nullable()->after('prenda_inferior');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'calzado')) {
                $table->string('calzado', 80)->nullable()->after('color_inferior');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'color_calzado')) {
                $table->string('color_calzado', 80)->nullable()->after('calzado');
            }
            if (!Schema::hasColumn('conduce_legalidad_personas', 'rasgos_visibles')) {
                $table->json('rasgos_visibles')->nullable()->after('color_calzado');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('conduce_legalidad_personas')) {
            return;
        }

        Schema::table('conduce_legalidad_personas', function (Blueprint $table) {
            foreach ([
                'rasgos_visibles',
                'color_calzado',
                'calzado',
                'color_inferior',
                'prenda_inferior',
                'color_superior',
                'prenda_superior',
                'cabello',
                'tez',
                'estatura',
                'complexion',
                'edad_aproximada',
            ] as $column) {
                if (Schema::hasColumn('conduce_legalidad_personas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};