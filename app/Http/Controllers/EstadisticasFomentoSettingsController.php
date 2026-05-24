<?php

namespace App\Http\Controllers;

use App\Services\Fomento\ExcelFomentoGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class EstadisticasFomentoSettingsController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanViewFomentoStats($request);

        return view('admin.settings.estadisticas_fomento.index');
    }

    public function excelDiario(Request $request)
    {
        $this->ensureCanViewFomentoStats($request);

        $disk = Storage::disk('local');
        $directorio = 'cortes/excel_fomento';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/excel_fomento_\d{4}-\d{2}-\d{2}\.xlsx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/excel_fomento_(\d{4}-\d{2}-\d{2})\.xlsx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_fomento.excel_diario.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_fomento.excel_diario.index', compact('cortes'));
    }

    public function generarExcelDiario(Request $request)
    {
        $this->ensureCanViewFomentoStats($request);

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $tz = 'America/Mexico_City';
        $fechaCorte = Carbon::parse($data['fecha'], $tz)->format('Y-m-d');
        $tempPath = app(ExcelFomentoGenerator::class)->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/excel_fomento');

        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        $nombreArchivo = 'excel_fomento_' . $fechaCorte . '.xlsx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        File::copy($tempPath, $rutaDestino);
        @chmod($rutaDestino, 0664);

        return redirect()
            ->route('settings.estadisticas_fomento.excel_diario')
            ->with('success', 'Excel de Fomento generado para ' . Carbon::parse($fechaCorte)->format('d/m/Y') . '.');
    }

    public function descargarExcelDiario(Request $request, string $fecha)
    {
        $this->ensureCanViewFomentoStats($request);

        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha), 404);

        $nombreArchivo = 'excel_fomento_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_fomento/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function ensureCanViewFomentoStats(Request $request): void
    {
        abort_unless($request->user() && $request->user()->can('menu-estadisticas-actividades-fomento'), 403);
    }
}
