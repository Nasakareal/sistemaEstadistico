<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LicenciaController extends Controller
{
    public function requisitos()
    {
        $requisitos = [
            'Tener 18 años o más',
            'Presentar identificación oficial vigente',
            'Aprobar el examen de manejo (teórico y práctico)',
            'Comprobante de domicilio reciente',
            'Cubrir el costo correspondiente',
        ];

        return view('licencias.requisitos', compact('requisitos'));
    }

    public function costos()
    {
        $costos = [
            'Licencia tipo A (Automovilista particular)' => '$1,200.00 MXN',
            'Licencia tipo B (Chofer de transporte público)' => '$1,800.00 MXN',
            'Reposición de licencia' => '$600.00 MXN',
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
