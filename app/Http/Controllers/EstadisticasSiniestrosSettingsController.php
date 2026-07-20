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
use Illuminate\Support\Str;
use App\Models\PersonalAsignacion;


class EstadisticasSiniestrosSettingsController extends Controller
{
    private const UNIDAD_SINIESTROS_ID = 1;

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
                    // El JSON es persistencia interna; la salida que recibe el usuario es PDF.
                    'archivo' => 'sectorizacion_' . ($matches[1] ?? '') . '.pdf',
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
        $fechaHora = $this->momentoOperativoSectorizacion($fecha);

        $turnoActivo = $this->turnoService->turnoActivoEn($fechaHora);
        $personal = $this->obtenerPersonalSectorizacion($fechaHora, $turnoActivo ? (int) $turnoActivo->id : null);
        $asignaciones = $this->leerAsignacionSectorizacion($fecha);
        $asignaciones['estado_fuerza'] = $this->obtenerEstadoFuerzaSectorizacion($fechaHora, $asignaciones);

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
        $this->abortIfSeguridadVialSoloLectura();

        $data = $request->validate([
            'fecha' => ['required', 'date'],
            'turno' => ['nullable', 'string', 'max:100'],
            'estado_fuerza' => ['nullable', 'array'],
            'elementos' => ['present', 'array'],
            'elementos.*.personal_id' => ['required', 'integer', 'distinct'],
            'elementos.*.sector' => ['required', 'in:I,II,III,IV'],
            'elementos.*.grupo' => ['nullable', 'string', 'max:100'],
            'elementos.*.x' => ['required', 'numeric', 'between:0,100'],
            'elementos.*.y' => ['required', 'numeric', 'between:0,100'],
            'elementos.*.lat' => ['required', 'numeric', 'between:-90,90'],
            'elementos.*.lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $fecha = $data['fecha'];
        $turnoActivo = $this->turnoService->turnoActivoEn($this->momentoOperativoSectorizacion($fecha));

        $payload = [
            'fecha' => $fecha,
            'turno' => trim((string) ($turnoActivo->nombre ?? ($data['turno'] ?? ''))),
            'capturado_en' => now('America/Mexico_City')->toDateTimeString(),
            'capturado_por' => optional(auth()->user())->id,
            'estado_fuerza' => $data['estado_fuerza'] ?? [],
            'elementos' => collect($data['elementos'])->map(function (array $elemento) {
                return [
                    'personal_id' => (int) $elemento['personal_id'],
                    'sector' => $elemento['sector'],
                    'grupo' => trim((string) ($elemento['grupo'] ?? '')) ?: null,
                    'x' => round((float) $elemento['x'], 2),
                    'y' => round((float) $elemento['y'], 2),
                    'lat' => round((float) $elemento['lat'], 7),
                    'lng' => round((float) $elemento['lng'], 7),
                ];
            })->values()->all(),
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
            'url_descarga' => route('settings.estadisticas_siniestros.sectorizaciones.descargar', $fecha),
        ]);
    }

    protected function obtenerPersonalSectorizacion(Carbon $fechaHora, ?int $turnoId = null): array
    {
        $q = Personal::query()
            ->with([
                'turno:id,nombre,slug,tipo_rol',
                'patrulla:id,numero_economico,tipo',
                'user:id,patrulla_id',
                'user.patrulla:id,numero_economico,tipo',
                'incidencias.tipo:id,nombre',
            ])
            ->where(function ($query) use ($fechaHora) {
                $query->whereNull('fecha_baja')
                    ->orWhereDate('fecha_baja', '>', $fechaHora->toDateString());
            })
            ->where('unidad_id', self::UNIDAD_SINIESTROS_ID)
            ->whereRaw('UPPER(TRIM(COALESCE(estatus, ""))) = ?', ['ACTIVO']);

        if ($turnoId) {
            $q->where('turno_id', $turnoId);
        }

        return $q->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function ($personal) use ($fechaHora) {
                $estado = $this->estadoFuerzaService->estado($personal, $fechaHora);
                // La sectorización refleja exclusivamente la asignación operativa
                // capturada en el usuario vinculado.
                $patrulla = optional($personal->user)->patrulla;

                return [
                    'id' => (int) $personal->id,
                    'nombre' => $personal->nombre,
                    'apellido_paterno' => $personal->ap_paterno,
                    'apellido_materno' => $personal->ap_materno,
                    'nombre_completo' => $personal->nombre_completo,
                    'numero_empleado' => $personal->numero_empleado,
                    'turno_id' => $personal->turno_id,
                    'turno' => optional($personal->turno)->nombre,
                    'tipo_rol' => optional($personal->turno)->tipo_rol,
                    'patrulla_id' => optional($patrulla)->id,
                    'patrulla' => optional($patrulla)->numero_economico,
                    'patrulla_tipo' => optional($patrulla)->tipo,
                    'estado_fuerza' => $estado,
                ];
            })->values()->all();
    }

    protected function obtenerEstadoFuerzaSectorizacion(Carbon $fechaHora, array $asignaciones): array
    {
        $plantilla = Personal::query()
            ->with([
                'turno:id,nombre,slug,tipo_rol,ciclo_inicio,trabajo_horas,descanso_horas',
                'user:id,patrulla_id,turno_id,estado',
                'user.patrulla:id,numero_economico,tipo',
                'user.turno:id,nombre,slug,tipo_rol,ciclo_inicio,trabajo_horas,descanso_horas',
                'user.roles:id,name',
                'incidencias.tipo:id,nombre',
            ])
            ->where('unidad_id', self::UNIDAD_SINIESTROS_ID)
            ->whereRaw('UPPER(TRIM(COALESCE(estatus, ""))) = ?', ['ACTIVO'])
            ->where(function ($query) use ($fechaHora) {
                $query->whereNull('fecha_baja')
                    ->orWhereDate('fecha_baja', '>', $fechaHora->toDateString());
            })
            ->get();

        $turnoActivo = $this->turnoService->turnoActivoEn($fechaHora);
        $inicioJornada = $turnoActivo
            ? $this->turnoService->inicioDeBloqueTrabajoActual($turnoActivo, $fechaHora)
            : $fechaHora->copy()->startOfDay()->addHours(7);
        $momentosRadio = [
            $inicioJornada->copy()->addMinute(),
            $inicioJornada->copy()->addHours(12)->addMinute(),
        ];

        $estados = $plantilla->mapWithKeys(function (Personal $personal) use ($fechaHora, $momentosRadio) {
            $tipoRol = strtoupper(trim((string) optional($personal->turno)->tipo_rol));

            if ($tipoRol !== 'RADIO_12X36') {
                return [$personal->id => $this->estadoFuerzaService->estado($personal, $fechaHora)];
            }

            // El parte representa las 24 horas completas. Para radio se revisan
            // ambos relevos de 12 horas, no sólo quién está activo al mediodía.
            $estadosRelevo = collect($momentosRadio)
                ->map(fn (Carbon $momento) => $this->estadoFuerzaService->estado($personal, $momento));
            $estado = $estadosRelevo->contains('EN_SERVICIO')
                ? 'EN_SERVICIO'
                : ($estadosRelevo->first(fn ($actual) => $actual !== 'FRANCO') ?? 'FRANCO');

            return [$personal->id => $estado];
        });

        $conteo = fn (string $estado): int => $estados
            ->filter(fn ($actual) => $actual === $estado)
            ->count();
        $idsEnRecorrido = collect($asignaciones['elementos'] ?? [])
            ->pluck('personal_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $patrullasEnRecorrido = $plantilla
            ->whereIn('id', $idsEnRecorrido)
            ->map(fn (Personal $personal) => optional($personal->user)->patrulla)
            ->filter()
            ->unique('id')
            ->values();
        $usuariosLaborando = $this->filtrarPersonalLaborando($plantilla, $estados);

        return [
            'estado_fuerza' => $plantilla->count(),
            'laborando' => $conteo('EN_SERVICIO'),
            'cmdt_turno' => $usuariosLaborando
                ->filter(fn (Personal $personal) => $this->usuarioTieneRol($personal, 'Jefe de Grupo'))
                ->count(),
            'base_radio' => $plantilla->filter(function (Personal $personal) use ($estados) {
                return strtoupper(trim((string) optional($personal->turno)->tipo_rol)) === 'RADIO_12X36'
                    && $estados->get($personal->id) === 'EN_SERVICIO';
            })->count(),
            'elementos_recorrido' => $idsEnRecorrido->count(),
            'modulo' => $usuariosLaborando
                ->filter(fn (Personal $personal) => $this->usuarioTieneRol($personal, 'Evaluador Teórico'))
                ->count(),
            'curso' => $conteo('CURSOS'),
            'permiso' => $conteo('PERMISO'),
            'incapacidad' => $conteo('INCAPACIDAD'),
            'francos' => $conteo('FRANCO'),
            'vacaciones' => $conteo('VACACIONES'),
            'faltando' => $conteo('FALTANDO'),
            'comisionados' => $conteo('COMISIONADOS'),
            'crp' => $patrullasEnRecorrido
                ->reject(fn ($patrulla) => str_contains(strtoupper((string) $patrulla->tipo), 'MOTO'))
                ->count(),
            'motos' => $patrullasEnRecorrido
                ->filter(fn ($patrulla) => str_contains(strtoupper((string) $patrulla->tipo), 'MOTO'))
                ->count(),
        ];
    }

    protected function filtrarPersonalLaborando($plantilla, $estados)
    {
        return $plantilla->filter(function (Personal $personal) use ($estados) {
            return $personal->user
                && Str::upper(Str::ascii(trim((string) $personal->user->estado))) === 'ACTIVO'
                // El estado de fuerza se calcula desde el registro de Personal.
                // Usar aquí el turno del usuario podía contradecir ese resultado
                // y excluir evaluadores que sí están laborando según su jornada.
                && $estados->get($personal->id) === 'EN_SERVICIO';
        });
    }

    protected function usuarioTieneRol(Personal $personal, string $rol): bool
    {
        if (!$personal->user || !$personal->user->relationLoaded('roles')) {
            return false;
        }

        $rolEsperado = Str::upper(Str::ascii(trim($rol)));

        return $personal->user->roles->contains(function ($rolUsuario) use ($rolEsperado) {
            return Str::upper(Str::ascii(trim((string) $rolUsuario->name))) === $rolEsperado;
        });
    }

    protected function leerAsignacionSectorizacion(string $fecha): array
    {
        $disk = Storage::disk('local');
        $ruta = 'cortes/sectorizaciones/sectorizacion_' . $fecha . '.json';

        if (!$disk->exists($ruta)) {
            return $this->asignacionSectorizacionVacia($fecha);
        }

        $json = json_decode($disk->get($ruta), true);

        if (!is_array($json)) {
            return $this->asignacionSectorizacionVacia($fecha);
        }

        if (isset($json['elementos']) && is_array($json['elementos'])) {
            $json['estado_fuerza'] = is_array($json['estado_fuerza'] ?? null) ? $json['estado_fuerza'] : [];

            return $json;
        }

        // Compatibilidad con los primeros cortes, que guardaban únicamente IDs por sector.
        $posiciones = [
            'I' => ['x' => 72, 'y' => 28],
            'II' => ['x' => 64, 'y' => 68],
            'III' => ['x' => 28, 'y' => 66],
            'IV' => ['x' => 28, 'y' => 26],
        ];
        $elementos = [];

        foreach (['I', 'II', 'III', 'IV'] as $sector) {
            foreach (($json['sectores'][$sector]['personal'] ?? []) as $indice => $personalId) {
                $elementos[] = [
                    'personal_id' => (int) $personalId,
                    'sector' => $sector,
                    'x' => max(4, min(96, $posiciones[$sector]['x'] + (($indice % 3) - 1) * 4)),
                    'y' => max(4, min(96, $posiciones[$sector]['y'] + intdiv($indice, 3) * 5)),
                ];
            }
        }

        return array_merge($json, [
            'fecha' => $fecha,
            'turno' => $json['turno'] ?? '',
            'estado_fuerza' => is_array($json['estado_fuerza'] ?? null) ? $json['estado_fuerza'] : [],
            'elementos' => $elementos,
        ]);
    }

    protected function asignacionSectorizacionVacia(string $fecha): array
    {
        return [
            'fecha' => $fecha,
            'turno' => '',
            'estado_fuerza' => [],
            'elementos' => [],
        ];
    }

    public function descargarSectorizacion(string $fecha)
    {
        return redirect()->route('settings.estadisticas_siniestros.sectorizaciones.gestionar', [
            'fecha' => $fecha,
            'descargar' => 'pdf',
        ]);
    }

    public function gestionarSectorizacion(Request $request, string $fecha)
    {
        if ($request->query('descargar') !== 'pdf') {
            $this->abortIfSeguridadVialSoloLectura();
        }

        $fechaHora = $this->momentoOperativoSectorizacion($fecha);
        $turnoActivo = $this->turnoService->turnoActivoEn($fechaHora);
        $personal = $this->obtenerPersonalSectorizacion($fechaHora, $turnoActivo ? (int) $turnoActivo->id : null);
        $asignaciones = $this->leerAsignacionSectorizacion($fecha);
        $estadoFuerza = $this->obtenerEstadoFuerzaSectorizacion($fechaHora, $asignaciones);

        return view('admin.settings.estadisticas_siniestros.sectorizaciones.gestionar', [
            'fecha' => $fecha,
            'turnoActivo' => $turnoActivo,
            'personal' => $personal,
            'asignaciones' => $asignaciones,
            'estadoFuerza' => $estadoFuerza,
            'sectores' => [
                'I' => ['nombre' => 'Revolución', 'romano' => 'I'],
                'II' => ['nombre' => 'Nueva España', 'romano' => 'II'],
                'III' => ['nombre' => 'Independencia', 'romano' => 'III'],
                'IV' => ['nombre' => 'República', 'romano' => 'IV'],
            ],
        ]);
    }

    protected function momentoOperativoSectorizacion(string $fecha): Carbon
    {
        // La sectorización corresponde al turno que recibe la jornada por la mañana.
        // Consultar a medianoche todavía puede devolver el turno del día anterior.
        return Carbon::parse($fecha . ' 12:00:00', 'America/Mexico_City');
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

    public function relacionArmamento()
    {
        return view('admin.settings.estadisticas_siniestros.relacion_armamento.index');
    }

    public function dataRelacionArmamento(Request $request)
    {
        $rows = $this->obtenerRelacionArmamento($request);

        return response()->json([
            'ok' => true,
            'data' => $rows,
        ]);
    }

    public function descargarRelacionArmamento(Request $request)
    {
        $rows = $this->obtenerRelacionArmamento($request);

        $filename = "relacion_armamento.csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $callback = function() use ($rows) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Número',
                'Elemento',
                'Grado',
                'Unidad',
                'Tipo',
                'Clase',
                'Marca',
                'Modelo',
                'Matrícula',
                'Calibre',
                'Cargadores',
                'Cartuchos'
            ]);

            foreach ($rows as $row) {
                fputcsv($file, [
                    $row['index'],
                    $row['elemento'],
                    $row['grado'],
                    $row['unidad'],
                    $row['tipo'],
                    $row['clase'],
                    $row['marca'],
                    $row['modelo'],
                    $row['matricula'],
                    $row['calibre'],
                    $row['cargadores'],
                    $row['cartuchos'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function obtenerRelacionArmamento(Request $request): array
    {
        $asignaciones = PersonalAsignacion::query()
            ->with([
                'personal.unidad:id,nombre',
                'armamento:id,unidad_id,tipo,clase,marca,modelo,matricula,serie,calibre,cargadores_cantidad,cartuchos_cantidad,estatus,deleted_at',
            ])
            ->whereNotNull('armamento_id')
            ->where('activo', 1)
            ->whereNull('fecha_fin')
            ->whereHas('personal', function ($query) {
                $query->whereNull('deleted_at')
                    ->where('unidad_id', self::UNIDAD_SINIESTROS_ID)
                    ->whereRaw('UPPER(TRIM(COALESCE(estatus, ""))) = ?', ['ACTIVO']);
            })
            ->whereHas('armamento', function ($query) {
                $query->whereNull('deleted_at')
                    ->where('unidad_id', self::UNIDAD_SINIESTROS_ID)
                    ->whereRaw('UPPER(TRIM(COALESCE(estatus, ""))) = ?', ['ACTIVO']);
            })
            ->get();

        $rows = [];

        $agrupado = $asignaciones->groupBy(function ($asignacion) {
            return $asignacion->personal_id;
        });

        foreach ($agrupado as $grupo) {

            $ordenados = $grupo->sortBy(function ($asignacion) {
                return strtoupper($asignacion->armamento->tipo ?? '');
            });

            foreach ($ordenados as $asignacion) {

                $personal = $asignacion->personal;
                $armamento = $asignacion->armamento;

                $rows[] = [
                    'elemento' => $personal ? $personal->nombre_completo : '',
                    'grado' => $personal->grado ?? '',
                    'unidad' => optional($personal->unidad)->nombre ?? ($personal->area ?? ''),
                    'tipo' => $armamento->tipo ?? '',
                    'clase' => $armamento->clase ?? '',
                    'marca' => $armamento->marca ?? '',
                    'modelo' => $armamento->modelo ?? '',
                    'matricula' => $armamento->matricula ?? '',
                    'calibre' => $armamento->calibre ?? '',
                    'cargadores' => $armamento->cargadores_cantidad ?? 0,
                    'cartuchos' => $armamento->cartuchos_cantidad ?? 0,
                ];
            }
        }

        return collect($rows)->values()->map(function ($row, $index) {
            $row['index'] = $index + 1;
            return $row;
        })->all();
    }

    private function abortIfSeguridadVialSoloLectura(): void
    {
        $user = auth()->user();

        if ($user && (int) ($user->unidad_id ?? 0) === 3 && !$user->hasRole('Superadmin')) {
            abort(403);
        }
    }
}
