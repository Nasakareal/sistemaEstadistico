<?php

namespace App\Http\Controllers;

use App\Services\Exports\EstadoFuerzaExcelService;
use App\Services\ParteNovedadesGenerator;
use App\Services\BitacoraGenerator;
use App\Services\MiniParteGenerator;
use App\Services\BitacoraTurnoGenerator;
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

    public function bitacora(Request $request, BitacoraGenerator $gen)
    {
        $tz = 'America/Mexico_City';

        $fecha = $request->input('fecha') ?? now($tz)->format('Y-m-d');

        $ruta = $gen->generar($fecha);

        $fileName = 'bitacora_' . Carbon::parse($fecha, $tz)->format('Y-m-d') . '.docx';

        return response()->download($ruta, $fileName)->deleteFileAfterSend(true);
    }

    public function miniParte(Request $request, MiniParteGenerator $gen)
    {
        $tz = 'America/Mexico_City';

        $fecha = $request->input('fecha') ?? now($tz)->format('Y-m-d');

        $ruta = $gen->generar($fecha);

        $fileName = 'mini_parte_' . Carbon::parse($fecha, $tz)->format('Y-m-d') . '.docx';

        return response()->download($ruta, $fileName)->deleteFileAfterSend(true);
    }

    public function bitacoraTurno(Request $request, BitacoraTurnoGenerator $gen)
    {
        $tz = 'America/Mexico_City';

        $fecha = (string) ($request->query('fecha') ?? now($tz)->format('Y-m-d'));
        $turno = $request->query('turno') ?? 'A';

        $ruta = $gen->generar($fecha, $turno);

        $turnoLetra = is_numeric($turno) ? (string)$turno : strtoupper(trim((string)$turno));

        $fileName = 'bitacora_turno_' . $turnoLetra . '_' . Carbon::parse($fecha, $tz)->format('Y-m-d') . '.docx';

        return response()->download($ruta, $fileName)->deleteFileAfterSend(true);
    }
}
