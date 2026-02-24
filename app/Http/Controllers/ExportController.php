<?php

namespace App\Http\Controllers;

use App\Services\Exports\EstadoFuerzaExcelService;
use Carbon\Carbon;

class ExportController extends Controller
{
    public function estadoFuerza(EstadoFuerzaExcelService $service)
    {
        $corte = now('America/Mexico_City')->setTime(18, 0, 0);

        $ruta = $service->generar($corte);

        return response()->download($ruta)->deleteFileAfterSend(false);
    }
}
