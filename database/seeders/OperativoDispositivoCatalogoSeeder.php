<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OperativoDispositivoCatalogo;

class OperativoDispositivoCatalogoSeeder extends Seeder
{
    public function run()
    {
        $items = [
            ['unidad_id' => 4, 'nombre' => 'PSV (PUESTO DE SEGURIDAD Y VIGILANCIA)', 'slug' => 'psv', 'orden' => 1],
            ['unidad_id' => 4, 'nombre' => 'RSV (RECORRIDOS DE SEGURIDAD Y VIGILANCIA - PATRULLAJE)', 'slug' => 'rsv', 'orden' => 2],
            ['unidad_id' => 4, 'nombre' => 'CASCO', 'slug' => 'casco', 'orden' => 3],
            ['unidad_id' => 4, 'nombre' => 'CINTURÓN', 'slug' => 'cinturon', 'orden' => 4],
            ['unidad_id' => 4, 'nombre' => 'CARRUSEL', 'slug' => 'carrusel', 'orden' => 5],
            ['unidad_id' => 4, 'nombre' => 'CORDILLERA', 'slug' => 'cordillera', 'orden' => 6],
            ['unidad_id' => 4, 'nombre' => 'ASIENTO SEGURO PASAJEROS MENORES', 'slug' => 'asiento-seguro-pasajeros-menores', 'orden' => 7],
            ['unidad_id' => 4, 'nombre' => 'CABALLEROS DEL CAMINO', 'slug' => 'caballeros-del-camino', 'orden' => 8],
            ['unidad_id' => 4, 'nombre' => 'PROXIMIDAD SOCIAL', 'slug' => 'proximidad-social', 'orden' => 9],
        ];

        foreach ($items as $item) {
            OperativoDispositivoCatalogo::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'unidad_id' => $item['unidad_id'],
                    'nombre' => $item['nombre'],
                    'activo' => 1,
                    'orden' => $item['orden'],
                ]
            );
        }
    }
}
