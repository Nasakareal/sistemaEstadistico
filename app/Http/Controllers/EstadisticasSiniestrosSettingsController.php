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
                    'url_descarga' => route('settings.estadisticas_siniestros.parte_novedades.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_siniestros.parte_novedades.index', compact('cortes'));
    }

    public function descargarParteNovedades(string $fecha)
    {
        $nombreArchivo = 'parte_novedades_' . $fecha . '.docx';
        $ruta = storage_path('app/cortes/parte_novedades/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    public function bitacora()
    {
        $disk = Storage::disk('local');
        $directorio = 'cortes/bitacora';

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/bitacora_\d{4}-\d{2}-\d{2}\.docx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/bitacora_(\d{4}-\d{2}-\d{2})\.docx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_siniestros.bitacora.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_siniestros.bitacora.index', compact('cortes'));
    }

    public function descargarBitacora(string $fecha)
    {
        $nombreArchivo = 'bitacora_' . $fecha . '.docx';
        $ruta = storage_path('app/cortes/bitacora/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    public function miniParte()
    {
        $disk = Storage::disk('local');
        $directorio = 'cortes/mini_parte';

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/mini_parte_\d{4}-\d{2}-\d{2}\.docx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/mini_parte_(\d{4}-\d{2}-\d{2})\.docx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_siniestros.mini_parte.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_siniestros.mini_parte.index', compact('cortes'));
    }

    public function descargarMiniParte(string $fecha)
    {
        $nombreArchivo = 'mini_parte_' . $fecha . '.docx';
        $ruta = storage_path('app/cortes/mini_parte/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }
}
