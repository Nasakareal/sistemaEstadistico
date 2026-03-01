<?php

namespace App\Http\Controllers;

use App\Services\Exports\EstadoFuerzaExcelService;
use App\Services\ParteNovedadesGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function estadoFuerza(EstadoFuerzaExcelService $service)
    {
        $corte = now('America/Mexico_City')->setTime(18, 0, 0);

        $ruta = $service->generar($corte);

        $fileName = 'estado_fuerza_' . $corte->format('Y-m-d_His') . '.xlsx';

        return response()->download($ruta, $fileName)->deleteFileAfterSend(false);
    }

    public function parteNovedades(Request $request, ParteNovedadesGenerator $gen)
    {
        $tz = 'America/Mexico_City';

        $fecha = $request->input('fecha') ?? now($tz)->format('Y-m-d');

        $ruta = $gen->generar($fecha);

        $fileName = 'parte_novedades_' . Carbon::parse($fecha, $tz)->format('Y-m-d') . '.docx';

        return response()->download($ruta, $fileName)->deleteFileAfterSend(true);
    }
}
