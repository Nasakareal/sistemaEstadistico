<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class EstadisticasDelegacionesSettingsController extends Controller
{
    public function index()
    {
        return view('admin.settings.estadisticas_delegaciones.index');
    }

    public function excelDiario()
    {
        $disk = Storage::disk('local');
        $directorio = 'cortes/excel_delegaciones';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/excel_delegaciones_\d{4}-\d{2}-\d{2}\.xlsx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/excel_delegaciones_(\d{4}-\d{2}-\d{2})\.xlsx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_delegaciones.excel_diario.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_delegaciones.excel_diario.index', compact('cortes'));
    }

    public function descargarExcelDiario(string $fecha)
    {
        $nombreArchivo = 'excel_delegaciones_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_delegaciones/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function excelMensual()
    {
        $disk = Storage::disk('local');
        $directorio = 'cortes/excel_delegaciones_mensual';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/excel_delegaciones_\d{4}-\d{2}\.xlsx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/excel_delegaciones_(\d{4}-\d{2})\.xlsx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_delegaciones.excel_mensual.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_delegaciones.excel_mensual.index', compact('cortes'));
    }

    public function descargarExcelMensual(string $fecha)
    {
        $nombreArchivo = 'excel_delegaciones_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_delegaciones_mensual/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
