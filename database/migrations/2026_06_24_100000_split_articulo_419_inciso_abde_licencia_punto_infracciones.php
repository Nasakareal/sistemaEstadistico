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

        $this->ensureColumns();

        $now = now();

        DB::table('licencia_punto_infracciones')
            ->where('codigo', 'ART419_I_ABDE_SEGURIDAD')
            ->update([
                'activa' => false,
                'updated_at' => $now,
            ]);

        foreach ($this->infracciones() as $infraccion) {
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
                'ART419_I_A_CONTROL_DIRECCION',
                'ART419_I_B_CINTURON_SEGURIDAD',
                'ART419_I_D_LUCES_VISIBILIDAD',
                'ART419_I_E_SENALES_ADVERTENCIA',
            ])
            ->update([
                'activa' => false,
                'updated_at' => now(),
            ]);

        DB::table('licencia_punto_infracciones')
            ->where('codigo', 'ART419_I_ABDE_SEGURIDAD')
            ->update([
                'activa' => true,
                'updated_at' => now(),
            ]);
    }

    private function infracciones(): array
    {
        return [
            [
                'codigo' => 'ART419_I_A_CONTROL_DIRECCION',
                'nombre' => 'No sujetar el volante o control de direccion con ambas manos',
                'puntos' => 1,
                'descripcion' => 'La persona conductora no lleva firmemente con ambas manos el control de direccion, o permite que otra persona pasajera lo tome parcial o totalmente.',
                'fundamento_legal' => 'Articulo 419, fraccion I, inciso a): multa de 20 a 30 UMAS y 1 punto de penalizacion en la licencia para conducir.',
            ],
            [
                'codigo' => 'ART419_I_B_CINTURON_SEGURIDAD',
                'nombre' => 'No usar cinturon o permitir pasajeros sin cinturon',
                'puntos' => 1,
                'descripcion' => 'La persona conductora no usa su cinturon de seguridad o no se asegura de que todas las personas pasajeras lo utilicen correctamente.',
                'fundamento_legal' => 'Articulo 419, fraccion I, inciso b): multa de 20 a 30 UMAS y 1 punto de penalizacion en la licencia para conducir.',
            ],
            [
                'codigo' => 'ART419_I_C_PORTEZUELAS',
                'nombre' => 'Abrir puertas sin precaucion o circular con puertas abiertas',
                'puntos' => 3,
                'descripcion' => 'El vehiculo circula con portezuelas abiertas, se abre una portezuela sin verificar que no interfiera con peatones u otros vehiculos, o se mantiene abierta mas tiempo del necesario para ascenso o descenso.',
                'fundamento_legal' => 'Articulo 419, fraccion I, inciso c): multa de 30 a 40 UMAS y 3 puntos de penalizacion en la licencia para conducir.',
            ],
            [
                'codigo' => 'ART419_I_D_LUCES_VISIBILIDAD',
                'nombre' => 'No encender luces cuando hay poca visibilidad',
                'puntos' => 1,
                'descripcion' => 'La persona conductora no enciende las luces cuando disminuye sensiblemente la visibilidad por clima, ambiente o condiciones de la via.',
                'fundamento_legal' => 'Articulo 419, fraccion I, inciso d): multa de 20 a 30 UMAS y 1 punto de penalizacion en la licencia para conducir.',
            ],
            [
                'codigo' => 'ART419_I_E_SENALES_ADVERTENCIA',
                'nombre' => 'No colocar senales de advertencia al detenerse en via primaria',
                'puntos' => 1,
                'descripcion' => 'El vehiculo se detiene por caso fortuito o fuerza mayor en via primaria y no se colocan instrumentos o dispositivos de advertencia conforme al sentido de circulacion.',
                'fundamento_legal' => 'Articulo 419, fraccion I, inciso e): multa de 20 a 30 UMAS y 1 punto de penalizacion en la licencia para conducir.',
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
