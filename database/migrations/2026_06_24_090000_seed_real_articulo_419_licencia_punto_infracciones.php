<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $sampleCodes = [
        'EXCESO_VELOCIDAD',
        'CELULAR_CONDUCIR',
        'SEMAFORO_ROJO',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        $this->ensureColumns();

        $now = now();

        DB::table('licencia_punto_infracciones')
            ->whereIn('codigo', $this->sampleCodes)
            ->update([
                'activa' => false,
                'updated_at' => $now,
            ]);

        foreach ($this->articulo419() as $infraccion) {
            $exists = DB::table('licencia_punto_infracciones')
                ->where('codigo', $infraccion['codigo'])
                ->exists();

            DB::table('licencia_punto_infracciones')->updateOrInsert(
                ['codigo' => $infraccion['codigo']],
                array_merge($infraccion, [
                    'activa' => true,
                    'updated_at' => $now,
                ], $exists ? [] : ['created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('licencia_punto_infracciones')) {
            return;
        }

        DB::table('licencia_punto_infracciones')
            ->whereIn('codigo', [
                'ART419_I_ABDE_SEGURIDAD',
                'ART419_I_C_PORTEZUELAS',
            ])
            ->update([
                'activa' => false,
                'updated_at' => now(),
            ]);
    }

    private function articulo419(): array
    {
        return [
            [
                'codigo' => 'ART419_I_ABDE_SEGURIDAD',
                'nombre' => 'Art. 419 fracc. I incisos a), b), d) y e) - seguridad en vehiculo motorizado',
                'puntos' => 1,
                'descripcion' => 'No sujetar firmemente con ambas manos el control de direccion; no asegurarse de que los pasajeros utilicen correctamente el cinturon de seguridad; no encender luces cuando disminuya sensiblemente la visibilidad; o no colocar instrumentos/dispositivos de advertencia cuando el vehiculo se detenga por caso fortuito o fuerza mayor en vias primarias.',
                'fundamento_legal' => 'Articulo 419, fraccion I, incisos a), b), d) y e): multa de 20 a 30 UMAS y 1 punto de penalizacion en la licencia para conducir.',
            ],
            [
                'codigo' => 'ART419_I_C_PORTEZUELAS',
                'nombre' => 'Art. 419 fracc. I inciso c) - portezuelas',
                'puntos' => 3,
                'descripcion' => 'Circular con las portezuelas abiertas, abrirlas sin verificar que no se interfiera el flujo de personas peatonas u otros vehiculos, o mantenerlas abiertas mas del tiempo estrictamente necesario para ascenso o descenso.',
                'fundamento_legal' => 'Articulo 419, fraccion I, inciso c): multa de 30 a 40 UMAS y 3 puntos de penalizacion en la licencia para conducir.',
            ],
        ];
    }

    private function ensureColumns(): void
    {
        if (!Schema::hasColumn('licencia_punto_infracciones', 'descripcion')) {
            Schema::table('licencia_punto_infracciones', function (Blueprint $table) {
                $table->text('descripcion')->nullable()->after('puntos');
            });
        }

        if (!Schema::hasColumn('licencia_punto_infracciones', 'fundamento_legal')) {
            Schema::table('licencia_punto_infracciones', function (Blueprint $table) {
                $table->text('fundamento_legal')->nullable()->after('descripcion');
            });
        }
    }
};
