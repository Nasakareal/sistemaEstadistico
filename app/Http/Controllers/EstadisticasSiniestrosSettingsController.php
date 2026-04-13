<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Services\EstadoFuerzaService;
use App\Services\TurnoService;
use App\Services\ActividadInformeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;


class EstadisticasSiniestrosSettingsController extends Controller
{
    protected TurnoService $turnoService;
    protected EstadoFuerzaService $estadoFuerzaService;
    protected ActividadInformeService $actividadInformeService;

    public function __construct(
        TurnoService $turnoService,
        EstadoFuerzaService $estadoFuerzaService,
        ActividadInformeService $actividadInformeService
    ) {
        $this->turnoService = $turnoService;
        $this->estadoFuerzaService = $estadoFuerzaService;
        $this->actividadInformeService = $actividadInformeService;
    }

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

    public function excelNovedades()
    {
        $disk = Storage::disk('local');
        $directorio = 'cortes/excel_novedades';

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/excel_novedades_\d{4}-\d{2}-\d{2}\.xlsx$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/excel_novedades_(\d{4}-\d{2}-\d{2})\.xlsx$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_siniestros.excel_novedades.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_siniestros.excel_novedades.index', compact('cortes'));
    }

    public function descargarExcelNovedades(string $fecha)
    {
        $nombreArchivo = 'excel_novedades_' . $fecha . '.xlsx';
        $ruta = storage_path('app/cortes/excel_novedades/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function sectorizaciones()
    {
        $disk = Storage::disk('local');
        $directorio = 'cortes/sectorizaciones';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(fn($file) => preg_match('/sectorizacion_\d{4}-\d{2}-\d{2}\.json$/', basename($file)))
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/sectorizacion_(\d{4}-\d{2}-\d{2})\.json$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_siniestros.sectorizaciones.descargar', $matches[1] ?? null),
                    'url_gestionar' => route('settings.estadisticas_siniestros.sectorizaciones.gestionar', $matches[1] ?? null),
                ];
            })
            ->filter(fn($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_siniestros.sectorizaciones.index', compact('cortes'));
    }

    public function dataSectorizacion(string $fecha)
    {
        $fechaHora = Carbon::parse($fecha . ' 00:00:00', 'America/Mexico_City');

        $turnoActivo = $this->turnoService->turnoActivoEn($fechaHora);
        $personal = $this->obtenerPersonalSectorizacion($fechaHora, $turnoActivo ? (int) $turnoActivo->id : null);
        $asignaciones = $this->leerAsignacionSectorizacion($fecha);

        return response()->json([
            'ok' => true,
            'fecha' => $fecha,
            'turno_activo' => $turnoActivo,
            'personal' => $personal,
            'asignaciones' => $asignaciones,
            'sectores' => [
                'I' => ['nombre' => 'Revolución', 'romano' => 'I'],
                'II' => ['nombre' => 'Nueva España', 'romano' => 'II'],
                'III' => ['nombre' => 'Independencia', 'romano' => 'III'],
                'IV' => ['nombre' => 'República', 'romano' => 'IV'],
            ],
        ]);
    }

    public function guardarSectorizacion(Request $request)
    {
        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'sectores' => ['required', 'array'],
            'sectores.I' => ['required', 'array'],
            'sectores.II' => ['required', 'array'],
            'sectores.III' => ['required', 'array'],
            'sectores.IV' => ['required', 'array'],
            'sectores.I.personal' => ['nullable', 'array'],
            'sectores.II.personal' => ['nullable', 'array'],
            'sectores.III.personal' => ['nullable', 'array'],
            'sectores.IV.personal' => ['nullable', 'array'],
            'sectores.I.personal.*' => ['integer'],
            'sectores.II.personal.*' => ['integer'],
            'sectores.III.personal.*' => ['integer'],
            'sectores.IV.personal.*' => ['integer'],
        ]);

        $fecha = $data['fecha'];
        $ids = collect([
            ...($data['sectores']['I']['personal'] ?? []),
            ...($data['sectores']['II']['personal'] ?? []),
            ...($data['sectores']['III']['personal'] ?? []),
            ...($data['sectores']['IV']['personal'] ?? []),
        ])->filter()->map(fn ($id) => (int) $id)->values();

        if ($ids->count() !== $ids->unique()->count()) {
            return response()->json([
                'ok' => false,
                'message' => 'Hay personal repetido en más de un sector.',
            ], 422);
        }

        $payload = [
            'fecha' => $fecha,
            'capturado_en' => now('America/Mexico_City')->toDateTimeString(),
            'capturado_por' => optional(auth()->user())->id,
            'sectores' => [
                'I' => [
                    'nombre' => 'Revolución',
                    'romano' => 'I',
                    'personal' => array_values(array_map('intval', $data['sectores']['I']['personal'] ?? [])),
                ],
                'II' => [
                    'nombre' => 'Nueva España',
                    'romano' => 'II',
                    'personal' => array_values(array_map('intval', $data['sectores']['II']['personal'] ?? [])),
                ],
                'III' => [
                    'nombre' => 'Independencia',
                    'romano' => 'III',
                    'personal' => array_values(array_map('intval', $data['sectores']['III']['personal'] ?? [])),
                ],
                'IV' => [
                    'nombre' => 'República',
                    'romano' => 'IV',
                    'personal' => array_values(array_map('intval', $data['sectores']['IV']['personal'] ?? [])),
                ],
            ],
        ];

        $disk = Storage::disk('local');
        $directorio = 'cortes/sectorizaciones';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $nombreArchivo = 'sectorizacion_' . $fecha . '.json';
        $disk->put($directorio . '/' . $nombreArchivo, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return response()->json([
            'ok' => true,
            'message' => 'Sectorización guardada correctamente.',
            'archivo' => $nombreArchivo,
            'url_descarga' => route('settings.estadisticas_siniestros.sectorizaciones.descargar', $nombreArchivo),
        ]);
    }

    protected function obtenerPersonalSectorizacion(Carbon $fechaHora, ?int $turnoId = null): array
    {
        $q = Personal::query()
            ->with([
                'turno:id,nombre,slug,tipo_rol',
                'patrulla:id,numero_economico',
                'incidencias.tipo:id,nombre',
            ])
            ->where(function ($query) {
                $query->whereNull('fecha_baja')
                    ->orWhereDate('fecha_baja', '>', now('America/Mexico_City')->toDateString());
            })
            ->whereRaw('UPPER(TRIM(COALESCE(estatus, ""))) = ?', ['ACTIVO']);

        if ($turnoId) {
            $q->where('turno_id', $turnoId);
        }

        return $q->orderBy('nombre')->get()->map(function ($personal) use ($fechaHora) {
            $estado = $this->estadoFuerzaService->estado($personal, $fechaHora);

            return [
                'id' => (int) $personal->id,
                'nombre' => $personal->nombre,
                'apellido_paterno' => $personal->apellido_paterno,
                'apellido_materno' => $personal->apellido_materno,
                'nombre_completo' => trim(collect([
                    $personal->nombre,
                    $personal->apellido_paterno,
                    $personal->apellido_materno,
                ])->filter()->implode(' ')),
                'numero_empleado' => $personal->numero_empleado,
                'turno_id' => $personal->turno_id,
                'turno' => optional($personal->turno)->nombre,
                'tipo_rol' => optional($personal->turno)->tipo_rol,
                'patrulla_id' => $personal->patrulla_id,
                'patrulla' => optional($personal->patrulla)->numero_economico,
                'estado_fuerza' => $estado,
            ];
        })->values()->all();
    }

    protected function leerAsignacionSectorizacion(string $fecha): array
    {
        $disk = Storage::disk('local');
        $ruta = 'cortes/sectorizaciones/sectorizacion_' . $fecha . '.json';

        if (!$disk->exists($ruta)) {
            return [
                'fecha' => $fecha,
                'sectores' => [
                    'I' => ['nombre' => 'Revolución', 'romano' => 'I', 'personal' => []],
                    'II' => ['nombre' => 'Nueva España', 'romano' => 'II', 'personal' => []],
                    'III' => ['nombre' => 'Independencia', 'romano' => 'III', 'personal' => []],
                    'IV' => ['nombre' => 'República', 'romano' => 'IV', 'personal' => []],
                ],
            ];
        }

        $json = json_decode($disk->get($ruta), true);

        return is_array($json) ? $json : [
            'fecha' => $fecha,
            'sectores' => [
                'I' => ['nombre' => 'Revolución', 'romano' => 'I', 'personal' => []],
                'II' => ['nombre' => 'Nueva España', 'romano' => 'II', 'personal' => []],
                'III' => ['nombre' => 'Independencia', 'romano' => 'III', 'personal' => []],
                'IV' => ['nombre' => 'República', 'romano' => 'IV', 'personal' => []],
            ],
        ];
    }

    public function descargarSectorizacion(string $fecha)
    {
        $nombreArchivo = 'sectorizacion_' . $fecha . '.json';
        $ruta = storage_path('app/cortes/sectorizaciones/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download($ruta, $nombreArchivo);
    }

    public function gestionarSectorizacion(string $fecha)
    {
        $fechaHora = Carbon::parse($fecha . ' 00:00:00', 'America/Mexico_City');
        $turnoActivo = $this->turnoService->turnoActivoEn($fechaHora);
        $personal = $this->obtenerPersonalSectorizacion($fechaHora, $turnoActivo ? (int) $turnoActivo->id : null);
        $asignaciones = $this->leerAsignacionSectorizacion($fecha);

        return view('admin.settings.estadisticas_siniestros.sectorizaciones.gestionar', [
            'fecha' => $fecha,
            'turnoActivo' => $turnoActivo,
            'personal' => $personal,
            'asignaciones' => $asignaciones,
            'sectores' => [
                'I' => ['nombre' => 'Revolución', 'romano' => 'I'],
                'II' => ['nombre' => 'Nueva España', 'romano' => 'II'],
                'III' => ['nombre' => 'Independencia', 'romano' => 'III'],
                'IV' => ['nombre' => 'República', 'romano' => 'IV'],
            ],
        ]);
    }

    public function actividades(Request $request)
    {
        $fecha = $request->input('fecha', now('America/Mexico_City')->toDateString());

        $disk = Storage::disk('local');
        $directorio = 'cortes/actividades';

        if (!$disk->exists($directorio)) {
            $disk->makeDirectory($directorio);
        }

        $cortes = collect($disk->files($directorio))
            ->filter(function ($file) {
                return preg_match('/actividades_\d{4}-\d{2}-\d{2}\.pdf$/', basename($file));
            })
            ->map(function ($file) {
                $nombre = basename($file);

                preg_match('/actividades_(\d{4}-\d{2}-\d{2})\.pdf$/', $nombre, $matches);

                return [
                    'archivo' => $nombre,
                    'ruta' => $file,
                    'fecha' => $matches[1] ?? null,
                    'url_descarga' => route('settings.estadisticas_siniestros.actividades.descargar', $matches[1] ?? null),
                ];
            })
            ->filter(fn ($item) => !empty($item['fecha']))
            ->sortByDesc('fecha')
            ->values();

        return view('admin.settings.estadisticas_siniestros.actividades.index', compact('cortes', 'fecha'));
    }

    public function descargarActividades(string $fecha)
    {
        $nombreArchivo = 'actividades_' . $fecha . '.pdf';
        $ruta = storage_path('app/cortes/actividades/' . $nombreArchivo);

        abort_unless(file_exists($ruta), 404);

        return response()->download(
            $ruta,
            $nombreArchivo,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function generarActividades(Request $request, string $fecha)
    {
        $this->actividadInformeService->generarYGuardarEnCortes($fecha, $request);

        return redirect()
            ->route('settings.estadisticas_siniestros.actividades')
            ->with('success', 'PDF generado correctamente');
    }
}
