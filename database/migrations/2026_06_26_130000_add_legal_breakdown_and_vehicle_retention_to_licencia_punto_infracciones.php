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

        Schema::table('licencia_punto_infracciones', function (Blueprint $table) {
            if (!Schema::hasColumn('licencia_punto_infracciones', 'articulo')) {
                $table->string('articulo', 30)->nullable()->after('nombre')->index();
            }

            if (!Schema::hasColumn('licencia_punto_infracciones', 'fraccion')) {
                $table->string('fraccion', 30)->nullable()->after('articulo')->index();
            }

            if (!Schema::hasColumn('licencia_punto_infracciones', 'inciso')) {
                $table->string('inciso', 30)->nullable()->after('fraccion')->index();
            }

            if (!Schema::hasColumn('licencia_punto_infracciones', 'multa_uma_min')) {
                $table->unsignedSmallInteger('multa_uma_min')->nullable()->after('puntos');
            }

            if (!Schema::hasColumn('licencia_punto_infracciones', 'multa_uma_max')) {
                $table->unsignedSmallInteger('multa_uma_max')->nullable()->after('multa_uma_min');
            }

            if (!Schema::hasColumn('licencia_punto_infracciones', 'retencion_vehiculo')) {
                $table->boolean('retencion_vehiculo')->default(false)->after('multa_uma_max')->index();
            }
        });

        if (Schema::hasColumn('licencia_punto_infracciones', 'puntos')) {
            DB::statement('ALTER TABLE licencia_punto_infracciones MODIFY puntos TINYINT UNSIGNED NOT NULL DEFAULT 0');
        }

        $this->actualizarArticulo419();
    }

    public function down(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        Schema::table('licencia_punto_infracciones', function (Blueprint $table) {
            foreach ([
                'retencion_vehiculo',
                'multa_uma_max',
                'multa_uma_min',
                'inciso',
                'fraccion',
                'articulo',
            ] as $column) {
                if (Schema::hasColumn('licencia_punto_infracciones', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('licencia_punto_infracciones', 'puntos')) {
            DB::statement('ALTER TABLE licencia_punto_infracciones MODIFY puntos TINYINT UNSIGNED NOT NULL');
        }
    }

    private function actualizarArticulo419(): void
    {
        $now = now();
        $rows = [
            'ART419_I_A_CONTROL_DIRECCION' => ['419', 'I', 'a', 20, 30],
            'ART419_I_B_CINTURON_SEGURIDAD' => ['419', 'I', 'b', 20, 30],
            'ART419_I_C_PORTEZUELAS' => ['419', 'I', 'c', 30, 40],
            'ART419_I_D_LUCES_VISIBILIDAD' => ['419', 'I', 'd', 20, 30],
            'ART419_I_E_SENALES_ADVERTENCIA' => ['419', 'I', 'e', 20, 30],
            'ART419_I_ABDE_SEGURIDAD' => ['419', 'I', 'a,b,d,e', 20, 30],
        ];

        foreach ($rows as $codigo => $data) {
            DB::table('licencia_punto_infracciones')
                ->where('codigo', $codigo)
                ->update([
                    'articulo' => $data[0],
                    'fraccion' => $data[1],
                    'inciso' => $data[2],
                    'multa_uma_min' => $data[3],
                    'multa_uma_max' => $data[4],
                    'retencion_vehiculo' => false,
                    'updated_at' => $now,
                ]);
        }
    }
};
