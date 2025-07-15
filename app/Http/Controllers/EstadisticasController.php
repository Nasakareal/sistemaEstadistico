<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hechos;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class EstadisticasController extends Controller
{
    public function index()
    {
        return view('admin.settings.estadisticas.index');
    }

    public function parteNovedades(Request $request)
    {
        $fecha = $request->input('fecha') ?? now()->format('Y-m-d');

        $inicio = Carbon::parse($fecha)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha)->setTime(18, 0);

        $hechos = Hechos::whereBetween('created_at', [$inicio, $fin])->get();

        return view('admin.settings.estadisticas.parte-novedades', compact('hechos', 'fecha'));
    }

    public function descargarParte(Request $request)
    {
        $fecha = $request->input('fecha') ?? now()->format('Y-m-d');
        $inicio = Carbon::parse($fecha)->setTime(18, 0)->subDay();
        $fin    = Carbon::parse($fecha)->setTime(18, 0);

        $hechos = Hechos::whereBetween('created_at', [$inicio, $fin])->get();

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addText("Parte de novedades del día " . Carbon::parse($fecha)->format('d/m/Y'));

        foreach ($hechos as $hecho) {
            $section->addText("- {$hecho->descripcion} (Registrado a las {$hecho->created_at->format('H:i')})");
        }

        $filename = "parte_novedades_{$fecha}.docx";
        $tempPath = storage_path("app/public/{$filename}");
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        return response()->download($tempPath)->deleteFileAfterSend(true);
    }
}
