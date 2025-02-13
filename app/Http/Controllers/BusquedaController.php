<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hechos;
use App\Models\Vehiculo;
use App\Models\Conductor;

class BusquedaController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('query');

        if (!$query) {
            return view('busqueda.index', ['resultados' => []]);
        }

        // Buscar en Conductores
        $conductores = Conductor::where('nombre', 'LIKE', "%$query%")
            ->orWhere('telefono', 'LIKE', "%$query%")
            ->orWhere('domicilio', 'LIKE', "%$query%")
            ->orWhere('numero_licencia', 'LIKE', "%$query%")
            ->get();

        // Buscar en Vehículos
        $vehiculos = Vehiculo::where('marca', 'LIKE', "%$query%")
            ->orWhere('modelo', 'LIKE', "%$query%")
            ->orWhere('placas', 'LIKE', "%$query%")
            ->orWhere('serie', 'LIKE', "%$query%")
            ->get();

        // Buscar en Hechos
        $hechos = Hechos::where('folio_c5i', 'LIKE', "%$query%")
            ->orWhere('calle', 'LIKE', "%$query%")
            ->orWhere('colonia', 'LIKE', "%$query%")
            ->orWhere('municipio', 'LIKE', "%$query%")
            ->get();

        return view('busqueda.index', compact('conductores', 'vehiculos', 'hechos', 'query'));
    }
}
