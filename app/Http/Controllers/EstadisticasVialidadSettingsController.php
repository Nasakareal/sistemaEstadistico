<?php

namespace App\Http\Controllers;

use App\Services\VialidadesUrbanas\ExcelVialidadesUrbanasGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class EstadisticasVialidadSettingsController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureCanViewVialidadesStats($request);

        return view('admin.settings.estadisticas_vialidad.index');
    }

    public function excelDiario(Request $request)
    {
        $this->ensureCanViewVialidadesStats($request);

        $disk = Storage::disk('local');
        $directorio = 'cortes/excel_vialidades_urbanas';
        $tz = 'America/Mexico_City';
        $fechaSugerida = now($tz)->toDateString();
        [$inicioSugerido, $finSugerido] = app(ExcelVialidadesUrbanasGenerator::class)->rangoCorte($fechaSugerida);
        $horaCorte = config('cortes.hora_corte_vialidades_urbanas', '17:00:00');
        $horaCorteDisplay = substr($horaCorte, 0, 5);

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/excel_vialidades_urbanas_\d{4}-\d{2}-\d{2}\.xlsx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/excel_vialidades_urbanas_(\d{4}-\d{2}-\d{2})\.xlsx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_vialidad.excel_diario.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_vialidad.excel_diario.index', compact(
            'cortes',
            'fechaSugerida',
            'inicioSugerido',
            'finSugerido',
            'horaCorte',
            'horaCorteDisplay'
        ));
    }

    public function generarExcelDiario(Request $request)
    {
        $this->ensureCanViewVialidadesStats($request);

        $data = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $tz = 'America/Mexico_City';
        $fechaCorte = Carbon::parse($data['fecha'], $tz)->format('Y-m-d');
        $tempPath = app(ExcelVialidadesUrbanasGenerator::class)->generar($fechaCorte);

        $directorioDestino = storage_path('app/cortes/excel_vialidades_urbanas');

        if (!File::exists($directorioDestino)) {
            File::makeDirectory($directorioDestino, 0775, true);
        }

        $nombreArchivo = 'excel_vialidades_urbanas_' . $fechaCorte . '.xlsx';
        $rutaDestino = $directorioDestino . DIRECTORY_SEPARATOR . $nombreArchivo;

        File::copy($tempPath, $rutaDestino);
        @chmod($rutaDestino, 0664);
        File::delete($tempPath);

        return redirect()
            ->route('settings.estadisticas_vialidad.excel_diario')
            ->with('success', 'Excel diario de Vialidades Urbanas generado para ' . Carbon::parse($fechaCorte)->format('d/m/Y') . '.');
    }

    public function descargarExcelDiario(Request $request, string $fecha)
    {
        $this->ensureCanViewVialidadesStats($request);

        abort_unless(preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha), 404);

        $nombreArchivo = 'excel_vialidades_urbanas_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_vialidades_urbanas/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function informeGestion()
    {
        abort(404);
    }

    public function descargarInformeGestion(string $fecha)
    {
        abort(404);
    }

    private function ensureCanViewVialidadesStats(Request $request): void
    {
        abort_unless($request->user() && $request->user()->can('menu-vialidades-urbanas'), 403);
    }
}
