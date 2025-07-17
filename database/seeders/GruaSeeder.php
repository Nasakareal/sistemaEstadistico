<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GruaSeeder extends Seeder
{
    public function run()
    {
        DB::table('gruas')->insert([
            ['nombre' => 'AUTOPISTA',      'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'DANNYS',         'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'EXPRESS',        'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'GALVAN',         'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'HERNANDEZ',      'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'PINEDA',         'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'PROFESIONALES',  'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'MORELIA',        'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'MONARCAS',       'direccion' => null, 'telefono' => null, 'email' => null],
            ['nombre' => 'MUÑOZ',          'direccion' => null, 'telefono' => null, 'email' => null],
        ]);
    }
}
