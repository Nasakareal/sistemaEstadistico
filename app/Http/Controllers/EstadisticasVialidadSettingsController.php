<?php

namespace App\Http\Controllers;

class EstadisticasVialidadSettingsController extends Controller
{
    public function index()
    {
        return redirect()
            ->route('settings.index')
            ->with('warning', 'El modulo de estadisticas de vialidad aun no esta configurado.');
    }

    public function informeGestion()
    {
        abort(404);
    }

    public function descargarInformeGestion(string $fecha)
    {
        abort(404);
    }
}
