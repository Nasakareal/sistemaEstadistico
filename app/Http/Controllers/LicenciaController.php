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
            ['nombre' => 'Módulo Central', 'direccion' => 'Av. Central #123, Centro, Ciudad X', 'horario' => 'Lunes a Viernes, 9:00 a 17:00'],
            ['nombre' => 'Módulo Norte', 'direccion' => 'Calle Norte #456, Colonia Y, Ciudad X', 'horario' => 'Lunes a Sábado, 10:00 a 18:00'],
            ['nombre' => 'Módulo Sur', 'direccion' => 'Blvd. Sur #789, Ciudad Z', 'horario' => 'Lunes a Viernes, 8:00 a 16:00'],
        ];

        return view('licencias.ubicaciones', compact('ubicaciones'));
    }
}
