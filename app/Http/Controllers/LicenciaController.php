<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LicenciaController extends Controller
{
    public function requisitos()
    {
        $tramites = [
            'Primera Vez' => [
                '1. Acta de Nacimiento o CURP (no mayor a 5 años).',
                '2. Examen de manejo.',
                '3. Examen médico.',
                '4. Comprobante de domicilio vigente(No mayor a 90 días.',
                '5. INE.',
            ],
            'Renovación' => [
                '1. Licencia Anterior.',
                '2. Examen médico.',
                '3. Comprobante de domicilio vigente(No mayor a 90 días.',
                '4. INE',
            ],
            'Servicio Público por primera vez' => [
                '1. Acta de Nacimiento o CURP (no mayor a 5 años).',
                '2. Examen de manejo.',
                '3. Examen médico.',
                '4. Comprobante de domicilio vigente(No mayor a 90 días.',
                '5. INE.',
                '6. Constancia del curso COCOTRA.',
            ],
            'Reposición' => [
                '1. Acta de extravío o denuncia.',
                '2. Examen médico.',
                '3. Comprobante de domicilio vigente(No mayor a 90 días.',
                '4. INE',
            ],
            'Permiso para menores' => [
                '1. Acta de Nacimiento o CURP (no mayor a 5 años).',
                '2. Examen de manejo.',
                '3. Examen médico.',
                '4. Comprobante de domicilio vigente(No mayor a 90 días.',
                '5. Identificación con fotografía.',
                '6. Responsiva.',
                '7. INE del responsable.',
            ],
        ];

        return view('licencias.requisitos', compact('tramites'));
    }

    public function costos()
    {
        $costos = [
            'Licencia Automovilista' => [
                '2 años'     => '$1,175.00 MXN',
                '4 años'     => '$1,720.00 MXN',
                '5 años'     => '$1,989.00 MXN',
                'Permanente' => '$2,789.00 MXN',
            ],
            'Licencia Chofer' => [
                '2 años'     => '$1,573.00 MXN',
                '4 años'     => '$2,402.00 MXN',
                '5 años'     => '$2,737.00 MXN',
            ],
            'Licencia Servicio Público' => [
                '3 años'     => '$2,000.00 MXN',
                '5 años'     => '$2,454.00 MXN',
            ],
            'Licencia Motociclista' => [
                '2 años'     => '$710.00 MXN',
                '4 años'     => '$934.00 MXN',
                '5 años'     => '$1,246.00 MXN',
                'Permanente' => '$2,066.00 MXN',
            ],
            'Permiso para Automovilista' => [
                '1 año'     => '$585.00 MXN',
            ],
            'Permiso para Motociclista' => [
                '1 año'     => '$454.00 MXN',
            ],
            'Reposición de licencia' => [
                '1 año'      => '$467.00 MXN'
            ],
            'Exámenes' => [
                '1 año'      => 'Manejo: $129.00 MXN, Médico: $143.00 MXN'
            ],
            'Multas' => [
                '6 Días'      => '$518.70 MXN',
                '7 Días'      => '$726.18 MXN',
                '8 Días'      => '$1,085.00 MXN',
            ]
        ];

        return view('licencias.costos', compact('costos'));
    }



    public function ubicaciones()
    {
        $ubicaciones = [
            ['nombre' => 'Módulo Policía y Tránsito', 'direccion' => 'Periférico Independencia #5000, col. Sentimientos de la Nación, Morelia, Michoacán', 'horario' => 'Lunes a Viernes, 9:00 a 15:00'],
            ['nombre' => 'Módulo Casa Cuna', 'direccion' => 'Av. Lázaro Cárdenas y Miguel de Cervantes Saavedra #225, col. Ventura Puente, Morelia, Michoacán', 'horario' => 'Lunes a Sábado, 9:00 a 15:00'],
            ['nombre' => 'Macro Módulo', 'direccion' => 'Av. Lázaro Cárdenas #2100, col. Chapultepec Norte, Morelia, Michoacán', 'horario' => 'Lunes a Viernes, 9:00 a 15:00'],
            ['nombre' => 'Módulo Capuchinas', 'direccion' => 'Ortega y Montañes, col. Centro, Morelia, Michoacán', 'horario' => 'Lunes a Viernes, 9:00 a 15:00'],
            ['nombre' => 'Módulo Central Camioneta', 'direccion' => 'Periférico República, Sala C, col. Eduardo Ruiz, Morelia, Michoacán', 'horario' => 'Lunes a Viernes, 9:00 a 15:00'],
        ];

        return view('licencias.ubicaciones', compact('ubicaciones'));
    }
}
