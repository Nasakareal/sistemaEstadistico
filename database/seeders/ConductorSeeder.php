<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConductorSeeder extends Seeder
{
    public function run()
    {
        DB::table('conductores')->insert([
            [
                'nombre' => 'Pablo Germán Florian Esperza',
                'edad' => 25,
                'domicilio' => 'Calle Rio Colorado #15, Col. Juan Escutia, Morelia',
                'cinturon' => true,
                'antecedentes' => false,
                'certificado_lesiones' => false,
                'certificado_alcoholemia' => true,
                'aliento_etilico' => true,
                'estado_licencia' => 'Michoacán',
                'vigencia_licencia' => '2030-01-23',
                'numero_licencia' => '4435747213',
                'tipo_licencia' => 'Automovilista',
            ],
            [
                'nombre' => 'Ana María Pérez Juárez',
                'edad' => 30,
                'domicilio' => 'Av. Camelinas #234, Col. Las Américas, Morelia',
                'cinturon' => true,
                'antecedentes' => false,
                'certificado_lesiones' => false,
                'certificado_alcoholemia' => false,
                'aliento_etilico' => false,
                'estado_licencia' => 'Michoacán',
                'vigencia_licencia' => '2029-05-15',
                'numero_licencia' => '4439987123',
                'tipo_licencia' => 'Motociclista',
            ],
        ]);
    }
}
