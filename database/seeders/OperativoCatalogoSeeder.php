<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\OperativoCatalogo;

class OperativoCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $carreterasId = 4;

        $globales = [
            'RELÁMPAGO',
            'CARRUSEL',
            'BLINDAJE',
            'CONCIENTIZACIÓN A MOTOCICLISTAS',
            'PUESTO DE REVISIÓN',
            'PUESTO DE CONTROL',
            'APOYO COCOTRA',
            'BLINDAJE CON ESTADOS COLINDANTES',
            'BASES DE OPERACIONES INTERINSTITUCIONAL',
        ];

        foreach ($globales as $index => $nombre) {

            OperativoCatalogo::updateOrCreate(
                [
                    'unidad_id' => null,
                    'slug' => Str::slug($nombre)
                ],
                [
                    'nombre' => $nombre,
                    'tipo' => 'GLOBAL',
                    'activo' => 1,
                    'orden' => $index + 1
                ]
            );
        }

        $carreteras = [
            'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)',
            'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)',
            'CASCO',
            'CINTURÓN',
            'CORDILLERA',
            'ASIENTO SEGURO PASAJEROS MENORES',
            'CABALLEROS DEL CAMINO',
        ];

        foreach ($carreteras as $index => $nombre) {

            OperativoCatalogo::updateOrCreate(
                [
                    'unidad_id' => $carreterasId,
                    'slug' => Str::slug($nombre)
                ],
                [
                    'nombre' => $nombre,
                    'tipo' => 'CARRETERAS',
                    'activo' => 1,
                    'orden' => 100 + $index + 1
                ]
            );
        }

        $this->command->info('Catálogo de operativos cargado correctamente.');
    }
}
