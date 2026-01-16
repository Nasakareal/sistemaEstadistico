<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class GruaSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('es_MX');

        DB::table('gruas')->insert([
            [
                'nombre'    => 'AUTOPISTA',
                'direccion' => 'Autopista Siglo XXI Km. 87, col. Ex Hacienda La Huerta, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'DANNYS',
                'direccion' => 'Higareda S/N, col. La Boruca, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'EXPRESS',
                'direccion' => 'Camino a la Joya S/N, loc. La Joya, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'GALVAN',
                'direccion' => 'Av. Madero Poniente No. ' . $faker->numberBetween(1000, 3000) . ', col. Tiníjaro, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'HERNANDEZ',
                'direccion' => 'Periférico Paseo de la República Km. ' . $faker->randomFloat(1, 10, 30) . ', Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'PINEDA',
                'direccion' => 'Av. Francisco I. Madero Oriente No. ' . $faker->numberBetween(2000, 5000) . ', col. Ciudad Industrial, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'PROFESIONALES',
                'direccion' => 'Carr. Morelia–Salamanca Km. ' . $faker->randomFloat(1, 5, 15) . ', col. San José Itzícuaro, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'MORELIA',
                'direccion' => 'Av. Periodismo No. ' . $faker->numberBetween(300, 1200) . ', col. Nueva Valladolid, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'MONARCAS',
                'direccion' => 'Blvd. García de León No. ' . $faker->numberBetween(500, 2500) . ', col. Chapultepec Sur, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
            [
                'nombre'    => 'MUÑOZ',
                'direccion' => 'Carr. Morelia - Pátzcuaro Km. 5.5, col. Campo, Morelia, Michoacán',
                'telefono'  => null,
                'email'     => null,
            ],
        ]);
    }
}
