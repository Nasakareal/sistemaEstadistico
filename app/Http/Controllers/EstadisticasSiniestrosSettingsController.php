<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class EstadisticasSiniestrosSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.estadisticas_siniestros.index');
    }

    public function parteNovedades()
    {
        $disk = Storage::disk('local');
        $directorio = 'cortes/parte_novedades';

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/parte_novedades_\d{4}-\d{2}-\d{2}\.docx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/parte_novedades_(\d{4}-\d{2}-\d{2})\.docx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'fecha_archivo' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_siniestros.parte_novedades.descargar', $nombre),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_siniestros.parte_novedades.index', compact('cortes'));
    }

    public function descargarParteNovedades(string $archivo)
    {
        $disk = Storage::disk('local');
        $ruta = 'cortes/parte_novedades/' . $archivo;

        abort_unless($disk->exists($ruta), 404);

        return Response::download(
            $disk->path($ruta),
            $archivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    public function bitacora()
    {
        return view('admin.settings.estadisticas_siniestros.bitacora.index');
    }

    public function miniParte()
    {
        return view('admin.settings.estadisticas_siniestros.mini_parte.index');
    }
}
