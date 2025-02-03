<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GruaSeeder extends Seeder
{
    public function run()
    {
        DB::table('gruas')->insert([
            ['nombre' => 'Gruas Pineda', 'direccion' => 'Calle Independencia 123, Morelia', 'telefono' => '4431234567', 'email' => 'contacto@gruas-pineda.com'],
            ['nombre' => 'Gruas Morelia', 'direccion' => 'Av. Morelos Sur 456, Morelia', 'telefono' => '4439876543', 'email' => 'info@gruas-morelos.com'],
            ['nombre' => 'Gruas y Pensiones Dannys', 'direccion' => 'Blvd. García de León 789, Morelia', 'telefono' => '4437654321', 'email' => 'soporte@gruas-michoacan.com'],
        ]);
    }
}
