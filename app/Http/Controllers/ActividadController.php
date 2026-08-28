<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\ActividadCategoria;
use App\Models\ActividadPersona;
use App\Models\ActividadSubcategoria;
use App\Models\Conductor;
use App\Models\Delegacion;
use App\Models\FomentoCulturaVialPrograma;
use App\Models\Grua;
use App\Models\LicenciaPuntoInfraccion;
use App\Models\Personal;
use App\Models\Unidad;
use App\Models\Vehiculo;
use App\Services\ActividadDuplicateGuard;
use App\Services\DelegacionesWhatsAppAlertService;
use App\Services\FomentoCulturaVialDetalleManager;
use App\Support\ActividadSubcategoriaCaptura;
use App\Support\HechoAccess;
use App\Support\GruaEditGuard;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActividadController extends Controller
{
    private const UNIDAD_DELEGACIONES_ID = 2;
    private const UNIDAD_SEGURIDAD_VIAL_ID = 3;

    public function __construct()
    {
        $this->middleware(['auth', 'can:ver actividades']);
    }

    public function index(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        $inicioDia = Carbon::parse($fechaSeleccionada, $tz)->startOfDay();
        $finDia = Carbon::parse($fechaSeleccionada, $tz)->endOfDay();

        $usuario = Auth::user();

        $query = $this->buildQuery($request, $inicioDia, $finDia);

        $actividades = $query->get();

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $unidadesFiltro = $this->unidadesDisponiblesParaFiltro($usuario);
        $delegacionesFiltro = $this->delegacionesDisponiblesParaFiltro($usuario);
        $mostrarFiltroDelegaciones = $this->debeMostrarFiltroDelegaciones($request, $usuario);

        return view('actividades.index', compact(
            'actividades',
            'categorias',
            'fechaSeleccionada',
            'unidadesFiltro',
            'delegacionesFiltro',
            'mostrarFiltroDelegaciones'
        ));
    }

    public function informeDiario(Request $request)
    {
        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        return $this->informeFecha($fechaSeleccionada, $request);
    }

    public function informeFecha($fecha, Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $tz = 'America/Mexico_City';

        try {
            $fechaSeleccionada = Carbon::createFromFormat('Y-m-d', (string) $fecha, $tz)->toDateString();
        } catch (\Throwable $e) {
            abort(404);
        }

        $inicioDia = Carbon::parse($fechaSeleccionada, $tz)->startOfDay();
        $finDia = Carbon::parse($fechaSeleccionada, $tz)->endOfDay();

        $actividades = $this->buildQuery($request, $inicioDia, $finDia)->get();

        $actividades->transform(function ($a) {
            $rel = $this->getOrCreatePdfImage($a->foto_path ?: $a->foto_thumbnail_path);
            $a->foto_pdf_path = $rel;
            $a->foto_pdf_abs = $rel ? public_path('storage/' . ltrim($rel, '/')) : null;
            return $a;
        });

        $pdfFacade = null;

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdfFacade = \Barryvdh\DomPDF\Facade\Pdf::class;
        } elseif (class_exists(\Barryvdh\DomPDF\Facade\PDF::class)) {
            $pdfFacade = \Barryvdh\DomPDF\Facade\PDF::class;
        }

        if (!$pdfFacade) {
            return back()->with('error', 'No está disponible el generador de PDF en el sistema.');
        }

        $nombreArchivo = 'INFORME_ACTIVIDADES_' . Carbon::parse($fechaSeleccionada, $tz)->format('d-m-Y') . '.pdf';

        $pdf = $pdfFacade::loadView('actividades.informe', [
            'actividades' => $actividades,
            'fechaSeleccionada' => $fechaSeleccionada,
            'tz' => $tz,
        ])->setPaper('letter', 'portrait')
          ->setOptions([
              'dpi' => 96,
              'defaultFont' => 'DejaVu Sans',
              'isRemoteEnabled' => true,
              'chroot' => base_path(),
          ]);

        return $pdf->download($nombreArchivo);
    }

    public function create()
    {
        $this->authorize('crear actividades');

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $usuario = Auth::user();
        $gruas = $this->obtenerGruasDisponiblesParaUsuario($usuario);
        $fomentoManager = app(FomentoCulturaVialDetalleManager::class);
        $usuarioEsFomento = $fomentoManager->usuarioEsFomento($usuario);
        $fomentoCategoriaIds = $fomentoManager->categoriaIds();
        $categoriaDefaultId = $usuarioEsFomento
            ? $this->categoriaCapacitacionesId()
            : null;
        $categoriaSeleccionada = (int) old('actividad_categoria_id', $categoriaDefaultId ?: 0);
        $mostrarFomentoCulturaVial = $usuarioEsFomento
            || in_array($categoriaSeleccionada, $fomentoCategoriaIds, true);
        $programasFomento = $this->obtenerProgramasFomentoCaptura();
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($usuario);
        $puedeEscribirCoordenadas = $this->userCanWriteCoordinates($usuario);

        $fundamentos = LicenciaPuntoInfraccion::activas()
            ->get()
            ->sortBy(function (LicenciaPuntoInfraccion $fundamento) {
                return [
                    $fundamento->articulo ? str_pad((string) $fundamento->articulo, 8, '0', STR_PAD_LEFT) : 'ZZZZZZZZ',
                    $fundamento->fraccion ?: 'ZZZZ',
                    $fundamento->inciso ?: 'ZZZZ',
                    $fundamento->nombre,
                ];
            })
            ->values();

        return view('actividades.create', compact('categorias', 'gruas', 'fundamentos', 'fomentoCategoriaIds', 'categoriaSeleccionada', 'mostrarFomentoCulturaVial', 'programasFomento', 'puedeCapturarFechaHora', 'puedeEscribirCoordenadas', 'usuarioEsFomento'));
    }

    public function store(Request $request)
    {
        $this->authorize('crear actividades');

        $user = Auth::user();
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($user);
        $puedeEscribirCoordenadas = $this->userCanWriteCoordinates($user);
        $fomentoManager = app(FomentoCulturaVialDetalleManager::class);

        $this->normalizeWritableCoordinates($request, $puedeEscribirCoordenadas);

        if ($fomentoManager->usuarioEsFomento($user)) {
            $request->merge(['vehiculos' => [], 'personas' => [], 'fundamento_ids' => []]);
        }

        $validated = $request->validate(array_merge([
            'actividad_categoria_id'         => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id'      => 'required|exists:actividad_subcategorias,id',
            'folio_c5i'                      => 'nullable|string|max:50',
            'fecha'                          => $puedeCapturarFechaHora ? 'required|date' : 'nullable',
            'hora'                           => $puedeCapturarFechaHora ? 'nullable|date_format:H:i' : 'nullable',
            'lugar'                          => 'nullable|string|max:255',
            'municipio'                      => 'nullable|string|max:255',
            'carretera'                      => 'nullable|string|max:255',
            'tramo'                          => 'nullable|string|max:255',
            'kilometro'                      => 'nullable|string|max:50',
            'lat'                            => 'nullable|numeric|between:-90,90',
            'lng'                            => 'nullable|numeric|between:-180,180',
            'coordenadas_texto'              => $this->coordinatesTextRules($puedeEscribirCoordenadas),
            'fuente_ubicacion'               => 'nullable|string|max:50',
            'nota_geo'                       => 'nullable|string|max:255',
            'motivo'                         => 'nullable|string',
            'narrativa'                      => 'nullable|string',
            'acciones_realizadas'            => 'nullable|string',
            'observaciones'                  => 'nullable|string',
            'personas_alcanzadas'            => 'nullable|integer|min:0',
            'personas_participantes'         => 'nullable|integer|min:0',
            'personas_detenidas'             => 'nullable|integer|min:0|max:3',
            'elementos_participantes_texto'  => 'nullable|string',
            'patrullas_participantes_texto'  => 'nullable|string',
            'destacamento_id'                => 'nullable|integer',
            'fotos'                          => 'required|array|min:1',
            'fotos.*'                        => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
            'vehiculos'                      => 'nullable|array',
            'vehiculos.*.marca'              => 'required|string|max:50',
            'vehiculos.*.modelo'             => 'nullable|string|max:10',
            'vehiculos.*.tipo'               => 'required|string|max:50',
            'vehiculos.*.linea'              => 'required|string|max:50',
            'vehiculos.*.color'              => 'required|string|max:30',
            'vehiculos.*.placas'             => 'nullable|string|max:15',
            'vehiculos.*.estado_placas'      => 'nullable|string|max:15',
            'vehiculos.*.serie'              => 'nullable|string|max:17',
            'vehiculos.*.capacidad_personas' => 'required|integer|min:0',
            'vehiculos.*.tipo_servicio'      => 'required|string|max:50',
            'vehiculos.*.tarjeta_circulacion_nombre' => 'nullable|string|max:60',
            'vehiculos.*.grua_id'            => 'nullable|integer|exists:gruas,id',
            'vehiculos.*.grua'               => 'nullable|string|max:255',
            'vehiculos.*.corralon'           => 'nullable|string|max:255',
            'vehiculos.*.aseguradora'        => 'nullable|string|max:100',
            'vehiculos.*.antecedente_vehiculo' => 'nullable|boolean',
            'vehiculos.*.monto_danos'        => 'nullable|numeric|min:0',
            'vehiculos.*.partes_danadas'     => 'nullable|string',
            'personas'                       => 'nullable|array|max:100',
            'personas.*.tipo_participacion'  => 'required|string|in:CONDUCTOR,PASAJERO,PEATON,OTRO',
            'personas.*.vehiculo_indice'     => 'nullable|integer|min:0',
            'personas.*.nombre'              => 'required|string|max:255',
            'personas.*.telefono'            => 'nullable|string|max:30',
            'personas.*.domicilio'           => 'nullable|string|max:255',
            'personas.*.sexo'                => 'nullable|string|in:MASCULINO,FEMENINO,OTRO',
            'personas.*.nacionalidad'        => 'nullable|string|max:80',
            'personas.*.ocupacion'           => 'nullable|string|max:255',
            'personas.*.edad'                => 'nullable|integer|min:0|max:120',
            'personas.*.tipo_licencia'       => 'nullable|string|max:80',
            'personas.*.estado_licencia'     => 'nullable|string|max:120',
            'personas.*.numero_licencia'     => 'nullable|string|max:80',
            'personas.*.vigencia_licencia'   => 'nullable|date',
            'personas.*.permanente'          => 'nullable|boolean',
            'personas.*.antecedentes'        => 'nullable|boolean',
            'personas.*.observaciones'       => 'nullable|string|max:2000',
            'fundamento_ids'                 => 'nullable|array|max:20',
            'fundamento_ids.*'               => 'required|integer|distinct|exists:licencia_punto_infracciones,id',
        ], FomentoCulturaVialDetalleManager::validationRules()), [
            'personas_detenidas.max' => 'No se pueden capturar mas de 3 personas detenidas.',
        ]);

        $validated['personas_participantes'] = min((int) ($validated['personas_participantes'] ?? 0), 15);
        $validated = $this->ajustarPayloadParaUsuarioFomento($validated, $user, $fomentoManager);

        $this->validarPersonasActividad(
            $validated['personas'] ?? [],
            count($validated['vehiculos'] ?? [])
        );
        $fundamentosActividad = $this->snapshotFundamentosActividad($validated['fundamento_ids'] ?? []);

        if ($response = $this->validarGruasPermitidasEnVehiculos($validated['vehiculos'] ?? [], $user)) {
            return $response;
        }

        $ahora = now('America/Mexico_City');
        $validated['fecha'] = $puedeCapturarFechaHora
            ? Carbon::parse($validated['fecha'] ?? $ahora->toDateString(), 'America/Mexico_City')->toDateString()
            : $ahora->toDateString();
        $validated['hora'] = $puedeCapturarFechaHora
            ? ($validated['hora'] ?? $ahora->format('H:i'))
            : $ahora->format('H:i');

        $validated['nombre'] = mb_strtoupper((string) ($user->name ?? ''), 'UTF-8');
        $validated['cantidad'] = 1;

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = $this->subcategoriaPermitidaParaUsuario(
                (int) $validated['actividad_categoria_id'],
                (int) $validated['actividad_subcategoria_id'],
                $user
            );

            if (!$ok) {
                $mensaje = $this->mensajeSubcategoriaNoPermitida(
                    (int) $validated['actividad_subcategoria_id'],
                    $user
                );

                return back()->withErrors([
                    'actividad_subcategoria_id' => $mensaje,
                ])->withInput();
            }
        }

        $archivos = collect($request->file('fotos', []))->filter()->values();
        $duplicateGuard = app(ActividadDuplicateGuard::class);
        $fotoHashes = $duplicateGuard->hashUploadedFiles($archivos);

        if ($duplicateGuard->hasRepeatedHashes($fotoHashes)) {
            return back()->withErrors([
                'fotos' => 'Estas intentando subir fotos duplicadas en la misma solicitud.',
            ])->withInput();
        }

        $duplicatePayload = array_merge($validated, [
            'unidad_org_id' => $user->unidad_id,
            'delegacion_id' => $user->delegacion_id,
        ]);

        if ($duplicateGuard->findRecentDuplicate((int) $user->id, $duplicatePayload, $fotoHashes)) {
            return back()->withErrors([
                'fotos' => ActividadDuplicateGuard::MESSAGE,
            ])->withInput();
        }

        return DB::transaction(function () use ($archivos, $fotoHashes, $validated, $user, $fomentoManager, $fundamentosActividad) {
            $actividad = Actividad::create([
                'client_uuid'                   => (string) Str::uuid(),
                'folio_c5i'                     => $this->toUpperOrNull($validated['folio_c5i'] ?? null),
                'sync_status'                   => 'local',
                'sync_error'                    => null,
                'synced_at'                     => null,
                'actividad_categoria_id'        => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id'     => $validated['actividad_subcategoria_id'] ?? null,
                'nombre'                        => $validated['nombre'],
                'cantidad'                      => 1,
                'foto_path'                     => null,
                'foto_nombre_original'          => null,
                'foto_hash'                     => null,
                'created_by'                    => $user->id,
                'updated_by'                    => $user->id,
                'unidad_org_id'                 => $user->unidad_id,
                'delegacion_id'                 => $user->delegacion_id,
                'destacamento_id'               => $validated['destacamento_id'] ?? null,
                'fecha'                         => $validated['fecha'],
                'hora'                          => $validated['hora'] ?? null,
                'lugar'                         => $this->toUpperOrNull($validated['lugar'] ?? null),
                'municipio'                     => $this->toUpperOrNull($validated['municipio'] ?? null),
                'carretera'                     => $this->toUpperOrNull($validated['carretera'] ?? null),
                'tramo'                         => $this->toUpperOrNull($validated['tramo'] ?? null),
                'kilometro'                     => $this->toUpperOrNull($validated['kilometro'] ?? null),
                'lat'                           => $validated['lat'] ?? null,
                'lng'                           => $validated['lng'] ?? null,
                'coordenadas_texto'             => $validated['coordenadas_texto'] ?? null,
                'fuente_ubicacion'              => $validated['fuente_ubicacion'] ?? null,
                'nota_geo'                      => $validated['nota_geo'] ?? null,
                'motivo'                        => $this->toUpperOrNull($validated['motivo'] ?? null),
                'narrativa'                     => $validated['narrativa'] ?? null,
                'acciones_realizadas'           => $validated['acciones_realizadas'] ?? null,
                'observaciones'                 => $validated['observaciones'] ?? null,
                'infracciones_actividad'        => $fundamentosActividad ?: null,
                'personas_alcanzadas'           => (int) ($validated['personas_alcanzadas'] ?? 0),
                'personas_participantes'        => (int) ($validated['personas_participantes'] ?? 0),
                'personas_detenidas'            => (int) ($validated['personas_detenidas'] ?? 0),
                'elementos_participantes_texto' => $validated['elementos_participantes_texto'] ?? null,
                'patrullas_participantes_texto' => $validated['patrullas_participantes_texto'] ?? null,
                'estado_revision'               => 'pendiente',
                'revisado_por'                  => null,
                'revisado_at'                   => null,
                'observacion_revision'          => null,
            ]);

            $fomentoManager->syncForActividad($actividad, $validated);

            $ordenBase = 0;

            foreach ($archivos as $index => $file) {
                $fotoHash = $fotoHashes[$index] ?? hash_file('sha256', $file->getRealPath());

                $yaExiste = $actividad->fotos()
                    ->where('foto_hash', $fotoHash)
                    ->exists();

                if ($yaExiste) {
                    continue;
                }

                $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                $fotoPath = $file->storeAs('actividades', $filename, 'public');

                $actividad->fotos()->create([
                    'foto_path'            => $fotoPath,
                    'foto_nombre_original' => $file->getClientOriginalName(),
                    'foto_hash'            => $fotoHash,
                    'orden'                => $ordenBase + $index,
                    'created_by'           => $user->id,
                    'updated_by'           => $user->id,
                ]);
            }

            $fotoPrincipal = $actividad->fotosTodas()
                ->whereNull('foto_archivada_at')
                ->whereNull('foto_eliminada_at')
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

            if ($fotoPrincipal) {
                $actividad->update([
                    'foto_path'             => $fotoPrincipal->foto_path,
                    'foto_thumbnail_path'   => null,
                    'foto_archivo_zip_path' => null,
                    'foto_archivada_at'     => null,
                    'foto_eliminada_at'     => null,
                    'foto_nombre_original'  => $fotoPrincipal->foto_nombre_original,
                    'foto_hash'             => $fotoPrincipal->foto_hash,
                ]);
            }

            $vehiculosCreados = [];
            foreach (($validated['vehiculos'] ?? []) as $index => $vehiculoData) {
                $vehiculosCreados[$index] = $this->crearVehiculoParaActividad($actividad, $vehiculoData);
            }

            $this->crearPersonasParaActividad(
                $actividad,
                $validated['personas'] ?? [],
                $vehiculosCreados
            );

            if ((int) ($actividad->personas_detenidas ?? 0) > 0) {
                DB::afterCommit(function () use ($actividad) {
                    app(DelegacionesWhatsAppAlertService::class)->notificarActividadConDetenidos($actividad);
                });
            }

            return redirect()->route('actividades.index')->with('success', 'Actividad creada correctamente.');
        });
    }

    public function show(Actividad $actividad)
    {
        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $actividad->load([
            'categoria',
            'subcategoria',
            'unidad',
            'delegacion',
            'destacamento',
            'creador',
            'actualizador',
            'revisor',
            'vehiculos',
            'vehiculos.conductores',
            'personas.vehiculo',
            'fomentoCulturaVialDetalle',
        ]);

        return view('actividades.show', compact('actividad'));
    }

    public function edit(Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $categorias = ActividadCategoria::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $subcategorias = $this->obtenerSubcategoriasDisponibles(
            (int) $actividad->actividad_categoria_id,
            $usuario
        );

        $actividad->load(['vehiculos', 'fomentoCulturaVialDetalle']);

        $gruas = $this->obtenerGruasDisponiblesParaUsuario($usuario);
        $fomentoManager = app(FomentoCulturaVialDetalleManager::class);
        $usuarioEsFomento = $fomentoManager->usuarioEsFomento($usuario);
        $fomentoCategoriaIds = $fomentoManager->categoriaIds();
        $categoriaSeleccionada = (int) old('actividad_categoria_id', $actividad->actividad_categoria_id);
        $mostrarFomentoCulturaVial = $usuarioEsFomento
            || $fomentoManager->actividadEsFomento($actividad)
            || in_array($categoriaSeleccionada, $fomentoCategoriaIds, true);
        $programasFomento = $this->obtenerProgramasFomentoCaptura();
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($usuario);
        $puedeEscribirCoordenadas = $this->userCanWriteCoordinates($usuario);

        return view('actividades.edit', compact('actividad', 'categorias', 'subcategorias', 'gruas', 'fomentoCategoriaIds', 'mostrarFomentoCulturaVial', 'programasFomento', 'puedeCapturarFechaHora', 'puedeEscribirCoordenadas', 'usuarioEsFomento'));
    }

    public function update(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();
        $puedeCapturarFechaHora = $this->userCanCaptureFechaHora($usuario);
        $puedeEscribirCoordenadas = $this->userCanWriteCoordinates($usuario);

        $this->normalizeWritableCoordinates($request, $puedeEscribirCoordenadas);

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $validated = $request->validate(array_merge([
            'actividad_categoria_id'         => 'required|exists:actividad_categorias,id',
            'actividad_subcategoria_id'      => 'required|exists:actividad_subcategorias,id',
            'folio_c5i'                      => 'nullable|string|max:50',
            'fecha'                          => $puedeCapturarFechaHora ? 'required|date' : 'nullable',
            'hora'                           => $puedeCapturarFechaHora ? 'nullable|date_format:H:i' : 'nullable',
            'lugar'                          => 'nullable|string|max:255',
            'municipio'                      => 'nullable|string|max:255',
            'carretera'                      => 'nullable|string|max:255',
            'tramo'                          => 'nullable|string|max:255',
            'kilometro'                      => 'nullable|string|max:50',
            'lat'                            => 'nullable|numeric|between:-90,90',
            'lng'                            => 'nullable|numeric|between:-180,180',
            'coordenadas_texto'              => $this->coordinatesTextRules($puedeEscribirCoordenadas),
            'fuente_ubicacion'               => 'nullable|string|max:50',
            'nota_geo'                       => 'nullable|string|max:255',
            'motivo'                         => 'nullable|string',
            'narrativa'                      => 'nullable|string',
            'acciones_realizadas'            => 'nullable|string',
            'observaciones'                  => 'nullable|string',
            'personas_alcanzadas'            => 'nullable|integer|min:0',
            'personas_participantes'         => 'nullable|integer|min:0',
            'personas_detenidas'             => 'nullable|integer|min:0|max:3',
            'elementos_participantes_texto'  => 'nullable|string',
            'patrullas_participantes_texto'  => 'nullable|string',
            'destacamento_id'                => 'nullable|integer',
            'fotos'                          => 'nullable|array|min:1',
            'fotos.*'                        => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], FomentoCulturaVialDetalleManager::validationRules()), [
            'personas_detenidas.max' => 'No se pueden capturar mas de 3 personas detenidas.',
        ]);

        $validated['personas_participantes'] = min((int) ($validated['personas_participantes'] ?? 0), 15);

        $fomentoManager = app(FomentoCulturaVialDetalleManager::class);
        $validated = $this->ajustarPayloadParaUsuarioFomento($validated, $usuario, $fomentoManager);

        if (!empty($validated['actividad_subcategoria_id'])) {
            $ok = $this->subcategoriaPermitidaParaUsuario(
                (int) $validated['actividad_categoria_id'],
                (int) $validated['actividad_subcategoria_id'],
                $usuario
            );

            if (!$ok) {
                $mensaje = $this->mensajeSubcategoriaNoPermitida(
                    (int) $validated['actividad_subcategoria_id'],
                    $usuario
                );

                return back()->withErrors([
                    'actividad_subcategoria_id' => $mensaje,
                ])->withInput();
            }
        }

        $detenidosAntes = (int) ($actividad->personas_detenidas ?? 0);
        $tz = 'America/Mexico_City';
        $fechaRespaldo = $actividad->created_at
            ? Carbon::parse($actividad->created_at, $tz)->toDateString()
            : now($tz)->toDateString();
        $horaRespaldo = $actividad->created_at
            ? Carbon::parse($actividad->created_at, $tz)->format('H:i')
            : now($tz)->format('H:i');
        $fechaCaptura = $puedeCapturarFechaHora
            ? Carbon::parse($validated['fecha'] ?? $actividad->fecha ?? $fechaRespaldo, $tz)->toDateString()
            : ($actividad->fecha ?? $fechaRespaldo);
        $horaCaptura = $puedeCapturarFechaHora
            ? ($validated['hora'] ?? $actividad->hora ?? $horaRespaldo)
            : ($actividad->hora ?? $horaRespaldo);

        return DB::transaction(function () use ($request, $validated, $actividad, $usuario, $detenidosAntes, $fechaCaptura, $horaCaptura, $fomentoManager) {
            $actividad->update([
                'sync_status'                   => $actividad->sync_status ?: 'local',
                'folio_c5i'                     => $this->toUpperOrNull($validated['folio_c5i'] ?? null),
                'actividad_categoria_id'        => $validated['actividad_categoria_id'],
                'actividad_subcategoria_id'     => $validated['actividad_subcategoria_id'] ?? null,
                'cantidad'                      => 1,
                'updated_by'                    => $usuario->id,
                'unidad_org_id'                 => $actividad->unidad_org_id ?? $usuario->unidad_id,
                'delegacion_id'                 => $actividad->delegacion_id ?? $usuario->delegacion_id,
                'destacamento_id'               => $validated['destacamento_id'] ?? null,
                'fecha'                         => $fechaCaptura,
                'hora'                          => $horaCaptura,
                'lugar'                         => $this->toUpperOrNull($validated['lugar'] ?? null),
                'municipio'                     => $this->toUpperOrNull($validated['municipio'] ?? null),
                'carretera'                     => $this->toUpperOrNull($validated['carretera'] ?? null),
                'tramo'                         => $this->toUpperOrNull($validated['tramo'] ?? null),
                'kilometro'                     => $this->toUpperOrNull($validated['kilometro'] ?? null),
                'lat'                           => $validated['lat'] ?? null,
                'lng'                           => $validated['lng'] ?? null,
                'coordenadas_texto'             => $validated['coordenadas_texto'] ?? null,
                'fuente_ubicacion'              => $validated['fuente_ubicacion'] ?? null,
                'nota_geo'                      => $validated['nota_geo'] ?? null,
                'motivo'                        => $this->toUpperOrNull($validated['motivo'] ?? null),
                'narrativa'                     => $validated['narrativa'] ?? null,
                'acciones_realizadas'           => $validated['acciones_realizadas'] ?? null,
                'observaciones'                 => $validated['observaciones'] ?? null,
                'personas_alcanzadas'           => (int) ($validated['personas_alcanzadas'] ?? 0),
                'personas_participantes'        => (int) ($validated['personas_participantes'] ?? 0),
                'personas_detenidas'            => (int) ($validated['personas_detenidas'] ?? 0),
                'elementos_participantes_texto' => $validated['elementos_participantes_texto'] ?? null,
                'patrullas_participantes_texto' => $validated['patrullas_participantes_texto'] ?? null,
            ]);

            $fomentoManager->syncForActividad($actividad, $validated);

            if ($request->hasFile('fotos')) {
                $ordenBase = (int) $actividad->fotos()->max('orden');
                $ordenBase = $ordenBase >= 0 ? $ordenBase + 1 : 0;

                foreach ($request->file('fotos', []) as $index => $file) {
                    $fotoHash = hash_file('sha256', $file->getRealPath());

                    $yaExiste = $actividad->fotos()
                        ->where('foto_hash', $fotoHash)
                        ->exists();

                    if ($yaExiste) {
                        continue;
                    }

                    $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
                    $filename = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $ext;
                    $fotoPath = $file->storeAs('actividades', $filename, 'public');

                    $actividad->fotos()->create([
                        'foto_path'            => $fotoPath,
                        'foto_nombre_original' => $file->getClientOriginalName(),
                        'foto_hash'            => $fotoHash,
                        'orden'                => $ordenBase + $index,
                        'created_by'           => $usuario->id,
                        'updated_by'           => $usuario->id,
                    ]);
                }
            }

            $fotoPrincipal = $actividad->fotosTodas()
                ->whereNull('foto_archivada_at')
                ->whereNull('foto_eliminada_at')
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

            $fotoArchivada = $fotoPrincipal ? null : $actividad->fotos()->orderBy('orden')->orderBy('id')->first();

            $actividad->update([
                'foto_path'             => optional($fotoPrincipal)->foto_path,
                'foto_thumbnail_path'   => $fotoPrincipal ? null : optional($fotoArchivada)->foto_thumbnail_path,
                'foto_archivo_zip_path' => $fotoPrincipal ? null : optional($fotoArchivada)->foto_archivo_zip_path,
                'foto_archivada_at'     => $fotoPrincipal ? null : optional($fotoArchivada)->foto_archivada_at,
                'foto_eliminada_at'     => null,
                'foto_nombre_original'  => optional($fotoPrincipal ?: $fotoArchivada)->foto_nombre_original,
                'foto_hash'             => optional($fotoPrincipal ?: $fotoArchivada)->foto_hash,
            ]);

            $alertService = app(DelegacionesWhatsAppAlertService::class);

            if ($alertService->debeNotificarActividadConDetenidos($detenidosAntes, $actividad)) {
                DB::afterCommit(function () use ($actividad) {
                    app(DelegacionesWhatsAppAlertService::class)->notificarActividadConDetenidos($actividad);
                });
            }

            return redirect()->route('actividades.index')->with('success', 'Actividad actualizada correctamente.');
        });
    }

    public function destroy(Actividad $actividad)
    {
        $this->authorize('eliminar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $actividad->loadMissing('vehiculos');

        if (
            GruaEditGuard::locksActividad($usuario, $actividad)
            && $actividad->vehiculos->contains(fn ($vehiculo) => GruaEditGuard::vehicleHasGruaData($vehiculo))
        ) {
            return back()->with('error', 'Esta actividad tiene grúa o corralón bloqueado. Solicita autorización de un Administrador.');
        }

        return DB::transaction(function () use ($actividad) {
            $actividad->load('fotos');

            foreach ($actividad->fotos as $foto) {
                if (!empty($foto->foto_path) && Storage::disk('public')->exists($foto->foto_path)) {
                    Storage::disk('public')->delete($foto->foto_path);
                }

                if (!empty($foto->foto_thumbnail_path) && Storage::disk('public')->exists($foto->foto_thumbnail_path)) {
                    Storage::disk('public')->delete($foto->foto_thumbnail_path);
                }

                $this->deletePdfCacheForOriginal($foto->foto_path);
            }

            if (!empty($actividad->foto_path) && Storage::disk('public')->exists($actividad->foto_path)) {
                Storage::disk('public')->delete($actividad->foto_path);
            }

            if (!empty($actividad->foto_thumbnail_path) && Storage::disk('public')->exists($actividad->foto_thumbnail_path)) {
                Storage::disk('public')->delete($actividad->foto_thumbnail_path);
            }

            $this->deletePdfCacheForOriginal($actividad->foto_path);

            $actividad->delete();

            return back()->with('success', 'Actividad eliminada correctamente.');
        });
    }

    public function subcategorias(ActividadCategoria $categoria)
    {
        $usuario = Auth::user();

        $items = $this->obtenerSubcategoriasDisponibles((int) $categoria->id, $usuario)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                ];
            })
            ->values();

        return response()->json($items);
    }

    private function obtenerSubcategoriasDisponibles(int $categoriaId, $usuario)
    {
        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $query = ActividadSubcategoria::query()
            ->where('actividad_categoria_id', $categoriaId)
            ->where('activo', 1);

        if ($categoriaId === 10 && $unidadId === 2) {
            $query->where('unidad_id', 2);
        } else {
            $query->where(function ($q) use ($unidadId) {
                $q->whereNull('unidad_id')
                  ->orWhere('unidad_id', $unidadId);
            });
        }

        $items = ActividadSubcategoriaCaptura::filtrarParaUsuario(
            $query->orderBy('nombre')->get(),
            $usuario
        );

        if (!$this->debePriorizarSubcategoriasFomento($categoriaId, $usuario)) {
            return $items;
        }

        $subcategoriasFomento = array_flip($this->fomentoSubcategoriaIdsConProgramas());

        if (empty($subcategoriasFomento)) {
            return $items;
        }

        return $items->sort(function ($a, $b) use ($subcategoriasFomento) {
            $prioridadA = isset($subcategoriasFomento[(int) $a->id]) ? 0 : 1;
            $prioridadB = isset($subcategoriasFomento[(int) $b->id]) ? 0 : 1;

            if ($prioridadA !== $prioridadB) {
                return $prioridadA <=> $prioridadB;
            }

            return strnatcasecmp((string) $a->nombre, (string) $b->nombre);
        })->values();
    }

    private function categoriaCapacitacionesId(): ?int
    {
        $categoria = ActividadCategoria::query()
            ->where('activo', 1)
            ->where(function ($query) {
                $query->where('slug', 'capacitaciones')
                    ->orWhereRaw('UPPER(nombre) = ?', ['CAPACITACIONES']);
            })
            ->first(['id']);

        return $categoria ? (int) $categoria->id : null;
    }

    private function categoriaEsCapacitaciones(int $categoriaId): bool
    {
        if ($categoriaId <= 0) {
            return false;
        }

        return ActividadCategoria::query()
            ->whereKey($categoriaId)
            ->where(function ($query) {
                $query->where('slug', 'capacitaciones')
                    ->orWhereRaw('UPPER(nombre) = ?', ['CAPACITACIONES']);
            })
            ->exists();
    }

    private function debePriorizarSubcategoriasFomento(int $categoriaId, $usuario): bool
    {
        return app(FomentoCulturaVialDetalleManager::class)->usuarioEsFomento($usuario)
            && $this->categoriaEsCapacitaciones($categoriaId);
    }

    private function fomentoSubcategoriaIdsConProgramas(): array
    {
        return FomentoCulturaVialPrograma::query()
            ->where('activo', 1)
            ->distinct()
            ->pluck('actividad_subcategoria_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();
    }

    private function ajustarPayloadParaUsuarioFomento(array $validated, $usuario, FomentoCulturaVialDetalleManager $fomentoManager): array
    {
        if (!$fomentoManager->usuarioEsFomento($usuario)) {
            return $validated;
        }

        $validated['motivo'] = null;
        $validated['acciones_realizadas'] = null;
        $validated['vehiculos'] = [];
        $validated['personas_alcanzadas'] = $this->totalPoblacionFomento($validated);

        return $validated;
    }

    private function totalPoblacionFomento(array $data): int
    {
        $fomento = $data['fomento'] ?? [];

        if (!is_array($fomento)) {
            return 0;
        }

        return collect(FomentoCulturaVialDetalleManager::NUMERIC_FIELDS)
            ->sum(function ($field) use ($fomento) {
                return max(0, (int) ($fomento[$field] ?? 0));
            });
    }

    private function obtenerProgramasFomentoCaptura()
    {
        return FomentoCulturaVialPrograma::query()
            ->where('activo', 1)
            ->orderBy('actividad_subcategoria_id')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'actividad_subcategoria_id', 'nombre']);
    }

    private function subcategoriaPermitidaParaUsuario(int $categoriaId, int $subcategoriaId, $usuario): bool
    {
        $unidadId = (int) ($usuario->unidad_id ?? 0);

        $query = ActividadSubcategoria::query()
            ->where('id', $subcategoriaId)
            ->where('actividad_categoria_id', $categoriaId)
            ->where('activo', 1);

        if ($categoriaId === 10 && $unidadId === 2) {
            $query->where('unidad_id', 2);
        } else {
            $query->where(function ($q) use ($unidadId) {
                $q->whereNull('unidad_id')
                  ->orWhere('unidad_id', $unidadId);
            });
        }

        $subcategoria = $query->first(['id', 'nombre']);

        return $subcategoria !== null
            && ActividadSubcategoriaCaptura::permitidaParaUsuario($subcategoria, $usuario);
    }

    private function mensajeSubcategoriaNoPermitida(int $subcategoriaId, $usuario): string
    {
        $subcategoria = ActividadSubcategoria::query()
            ->find($subcategoriaId, ['id', 'nombre']);

        if ($subcategoria) {
            $mensaje = ActividadSubcategoriaCaptura::mensajeRechazoParaUsuario(
                $subcategoria,
                $usuario
            );

            if ($mensaje !== null) {
                return $mensaje;
            }
        }

        return 'La subcategoría no pertenece a la categoría seleccionada o no está permitida para tu unidad.';
    }

    private function buildQuery(Request $request, Carbon $inicioDia, Carbon $finDia)
    {
        $query = Actividad::query()
            ->with(['categoria', 'subcategoria', 'unidad', 'delegacion', 'destacamento'])
            ->whereBetween('fecha', [$inicioDia->toDateString(), $finDia->toDateString()])
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id');

        $usuario = Auth::user();

        $this->applyActividadesVisibilityScope($query, $usuario);
        $this->applySeguridadVialExclusion($query);

        $horaDesde = $this->normalizeHourFilter($request->query('hora_desde', ''));
        $horaHasta = $this->normalizeHourFilter($request->query('hora_hasta', ''));

        if ($horaDesde !== null) {
            $query->whereTime('hora', '>=', $horaDesde);
        }

        if ($horaHasta !== null) {
            $query->whereTime('hora', '<=', $horaHasta);
        }

        $unidadFiltro = trim((string) $request->query('unidad_filtro', ''));

        if ($unidadFiltro !== '') {
            $unidadId = (int) $unidadFiltro;
            $unidadesPermitidas = $this->unidadesDisponiblesParaFiltro($usuario)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($unidadId === self::UNIDAD_SEGURIDAD_VIAL_ID || !in_array($unidadId, $unidadesPermitidas, true)) {
                $query->whereRaw('1 = 0');
            } elseif ($unidadId > 0) {
                $this->scopeActividadesUnidad($query, $unidadId);
            }
        }

        $delegacionFiltro = trim((string) $request->query('delegacion_filtro', ''));

        if ($delegacionFiltro !== '') {
            $delegacionId = (int) $delegacionFiltro;
            $unidadId = $unidadFiltro !== '' ? (int) $unidadFiltro : (int) ($usuario->unidad_id ?? 0);
            $delegacionesPermitidas = $this->delegacionesDisponiblesParaFiltro($usuario)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (
                $unidadId !== self::UNIDAD_DELEGACIONES_ID
                || $delegacionId <= 0
                || !in_array($delegacionId, $delegacionesPermitidas, true)
            ) {
                $query->whereRaw('1 = 0');
            } else {
                $this->scopeActividadesUnidad($query, self::UNIDAD_DELEGACIONES_ID);
                $query->where('delegacion_id', $delegacionId);
            }
        }

        if ($request->filled('actividad_categoria_id')) {
            $query->where('actividad_categoria_id', (int) $request->actividad_categoria_id);
        }

        if ($request->filled('actividad_subcategoria_id')) {
            $query->where('actividad_subcategoria_id', (int) $request->actividad_subcategoria_id);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('lugar', 'like', "%{$q}%")
                    ->orWhere('municipio', 'like', "%{$q}%")
                    ->orWhere('carretera', 'like', "%{$q}%")
                    ->orWhere('tramo', 'like', "%{$q}%")
                    ->orWhere('motivo', 'like', "%{$q}%")
                    ->orWhere('narrativa', 'like', "%{$q}%")
                    ->orWhere('elementos_participantes_texto', 'like', "%{$q}%")
                    ->orWhere('patrullas_participantes_texto', 'like', "%{$q}%");
            });
        }

        return $query;
    }

    private function applyActividadesVisibilityScope($query, $usuario): void
    {
        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Coordinador')
            || $unidadId === 3
        ) {
            return;
        }

        if ($unidadId === 2) {
            $this->scopeActividadesUnidad($query, 2);

            if ($this->esRolAdministrativoUnidad($usuario)) {
                return;
            }

            $delegacionId = (int) ($usuario->delegacion_id ?? 0);

            if ($delegacionId <= 0) {
                $query->whereRaw('1=0');
                return;
            }

            $ids = HechoAccess::delegacionIdsVisiblesParaUsuario($usuario);

            if (empty($ids)) {
                $query->whereRaw('1=0');
                return;
            }

            $query->whereIn('delegacion_id', $ids);
            return;
        }

        if ($unidadId > 0) {
            $this->scopeActividadesUnidad($query, $unidadId);
            return;
        }

        $query->whereRaw('1=0');
    }

    private function getOrCreatePdfImage(?string $fotoPath): ?string
    {
        if (!$fotoPath) {
            return null;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($fotoPath)) {
            return null;
        }

        $absOriginal = public_path('storage/' . ltrim($fotoPath, '/'));

        if (!is_file($absOriginal)) {
            return null;
        }

        $hash = @hash_file('sha1', $absOriginal);

        if (!$hash) {
            return $fotoPath;
        }

        $cacheRel = 'actividades/pdf_cache/' . $hash . '.jpg';

        if ($disk->exists($cacheRel)) {
            return $cacheRel;
        }

        $tmpDir = storage_path('app/tmp');

        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $tmpOut = $tmpDir . DIRECTORY_SEPARATOR . $hash . '.jpg';

        $ok = $this->resizeToJpeg($absOriginal, $tmpOut, 1280, 75);

        if (!$ok || !is_file($tmpOut)) {
            return $fotoPath;
        }

        $disk->put($cacheRel, file_get_contents($tmpOut));
        @unlink($tmpOut);

        return $cacheRel;
    }

    private function resizeToJpeg(string $src, string $dst, int $maxW, int $quality): bool
    {
        $info = @getimagesize($src);

        if (!$info || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
            return false;
        }

        $w = (int) $info[0];
        $h = (int) $info[1];
        $mime = (string) $info['mime'];

        if ($w <= 0 || $h <= 0) {
            return false;
        }

        $create = null;

        if ($mime === 'image/jpeg') {
            $create = 'imagecreatefromjpeg';
        }

        if ($mime === 'image/png') {
            $create = 'imagecreatefrompng';
        }

        if ($mime === 'image/webp') {
            $create = 'imagecreatefromwebp';
        }

        if (!$create || !function_exists($create)) {
            return false;
        }

        $srcIm = @$create($src);

        if (!$srcIm) {
            return false;
        }

        $newW = $w > $maxW ? $maxW : $w;
        $newH = (int) round($h * ($newW / $w));

        $dstIm = imagecreatetruecolor($newW, $newH);

        if (!$dstIm) {
            imagedestroy($srcIm);
            return false;
        }

        imagecopyresampled($dstIm, $srcIm, 0, 0, 0, 0, $newW, $newH, $w, $h);

        $saved = imagejpeg($dstIm, $dst, $quality);

        imagedestroy($srcIm);
        imagedestroy($dstIm);

        return (bool) $saved;
    }

    private function deletePdfCacheForOriginal(?string $fotoPath): void
    {
        if (!$fotoPath) {
            return;
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($fotoPath)) {
            return;
        }

        $absOriginal = public_path('storage/' . ltrim($fotoPath, '/'));

        if (!is_file($absOriginal)) {
            return;
        }

        $hash = @hash_file('sha1', $absOriginal);

        if (!$hash) {
            return;
        }

        $cacheRel = 'actividades/pdf_cache/' . $hash . '.jpg';

        if ($disk->exists($cacheRel)) {
            $disk->delete($cacheRel);
        }
    }

    private function toUpperOrNull($value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        if ($value === null || $value === '') {
            return null;
        }

        return mb_strtoupper($value, 'UTF-8');
    }

    private function puedeVerDelegacionesHijas($usuario): bool
    {
        return $usuario->hasAnyRole(['Delegado', 'Administrativo']);
    }

    private function esRolAdministrativoUnidad($usuario): bool
    {
        return $usuario->hasRole('Administrador')
            || $usuario->hasRole('Administrativo')
            || $usuario->hasRole('Subdirector');
    }

    private function userCanCaptureFechaHora($usuario): bool
    {
        if (!$usuario) {
            return false;
        }

        return $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Administrador')
            || $usuario->hasRole('Administrativo')
            || $usuario->hasRole('Subdirector');
    }

    private function scopeActividadesUnidad($query, int $unidadId): void
    {
        $query->where(function ($q) use ($unidadId) {
            $q->where('unidad_org_id', $unidadId)
                ->orWhere(function ($legacy) use ($unidadId) {
                    $legacy->whereNull('unidad_org_id')
                        ->whereHas('creador', function ($creador) use ($unidadId) {
                            $creador->where('unidad_id', $unidadId);
                        });
                });
        });
    }

    private function applySeguridadVialExclusion($query): void
    {
        $query->where(function ($scope) {
            $scope->where(function ($known) {
                $known->whereNotNull('unidad_org_id')
                    ->where('unidad_org_id', '<>', self::UNIDAD_SEGURIDAD_VIAL_ID);
            })->orWhere(function ($legacy) {
                $legacy->whereNull('unidad_org_id')
                    ->whereDoesntHave('creador', function ($creador) {
                        $creador->where('unidad_id', self::UNIDAD_SEGURIDAD_VIAL_ID);
                    });
            });
        });
    }

    private function unidadesDisponiblesParaFiltro($usuario)
    {
        $query = Unidad::query()
            ->where('id', '<>', self::UNIDAD_SEGURIDAD_VIAL_ID);

        if ($this->unidadTieneColumnaActiva()) {
            $query->where('activa', 1);
        }

        if (
            !$usuario
            || (!$usuario->hasRole('Superadmin') && (int) ($usuario->unidad_id ?? 0) !== self::UNIDAD_SEGURIDAD_VIAL_ID)
        ) {
            $query->where('id', (int) ($usuario->unidad_id ?? 0));
        }

        return $query->orderBy('nombre')->get();
    }

    private function delegacionesDisponiblesParaFiltro($usuario)
    {
        $query = Delegacion::query()
            ->where('activa', 1)
            ->orderBy('nombre');

        if (!$usuario) {
            return collect();
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if (
            $usuario->hasRole('Superadmin')
            || $usuario->hasRole('Coordinador')
            || $unidadId === self::UNIDAD_SEGURIDAD_VIAL_ID
            || ($unidadId === self::UNIDAD_DELEGACIONES_ID && $this->esRolAdministrativoUnidad($usuario))
        ) {
            return $query->get();
        }

        if ($unidadId !== self::UNIDAD_DELEGACIONES_ID) {
            return collect();
        }

        $delegacionIds = $this->delegacionIdsVisiblesParaUsuario($usuario);

        if (empty($delegacionIds)) {
            return collect();
        }

        return $query->whereIn('id', $delegacionIds)->get();
    }

    private function debeMostrarFiltroDelegaciones(Request $request, $usuario): bool
    {
        $unidadFiltro = trim((string) $request->query('unidad_filtro', ''));

        if ($unidadFiltro !== '') {
            return (int) $unidadFiltro === self::UNIDAD_DELEGACIONES_ID;
        }

        return (int) ($usuario->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID;
    }

    private function delegacionIdsVisiblesParaUsuario($usuario): array
    {
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);

        if ($delegacionId <= 0) {
            return [];
        }

        return HechoAccess::delegacionIdsVisiblesParaUsuario($usuario);
    }

    private function unidadTieneColumnaActiva(): bool
    {
        static $tieneColumna = null;

        if ($tieneColumna === null) {
            try {
                $tieneColumna = DB::getSchemaBuilder()->hasColumn('unidades', 'activa');
            } catch (\Throwable $e) {
                $tieneColumna = false;
            }
        }

        return $tieneColumna;
    }

    private function normalizeHourFilter($value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    public function compartir(Actividad $actividad)
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->can('ver actividades')) {
            abort(403);
        }

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $actividad->load([
            'categoria',
            'subcategoria',
            'unidad',
            'delegacion',
            'destacamento',
            'creador',
            'fotos',
            'vehiculos.conductores',
            'personas.vehiculo',
        ]);

        $fecha = $actividad->fecha ? \Carbon\Carbon::parse($actividad->fecha)->format('d/m/Y') : '';
        $hora = $this->formatearHoraActividad($actividad->hora);
        [$lat, $lng, $coordenadas] = $this->coordenadasActividad($actividad);
        $informante = $this->informanteActividad($actividad);

        $nombreUnidad = trim((string) optional($actividad->unidad)->nombre);
        $nombreDelegacion = trim((string) optional($actividad->delegacion)->nombre);
        $nombreDestacamento = trim((string) optional($actividad->destacamento)->nombre);

        $texto = "GUARDIA CIVIL\n\n";
        $texto .= "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n\n";

        if ($nombreUnidad !== '') {
            $texto .= $nombreUnidad . "\n\n";
        }

        if ($nombreDelegacion !== '') {
            $texto .= $nombreDelegacion . "\n\n";
        } elseif ($nombreDestacamento !== '') {
            $texto .= $nombreDestacamento . "\n\n";
        }

        $texto .= "ID DE ACTIVIDAD: {$actividad->id}\n\n";

        if ($fecha) {
            $texto .= "FECHA {$fecha}\n";
        }

        if ($hora) {
            $texto .= "HORA {$hora}\n";
        }

        if ($coordenadas !== '') {
            $texto .= "COORDENADAS: {$coordenadas}\n";

            if ($lat !== null && $lng !== null) {
                $texto .= "GOOGLE MAPS: https://www.google.com/maps?q={$lat},{$lng}\n";
            }
        }

        if ($hora || $coordenadas !== '') {
            $texto .= "\n";
        }

        if ($actividad->motivo) {
            $texto .= "ASUNTO: " . mb_strtoupper((string) $actividad->motivo, 'UTF-8') . "\n\n";
        }

        $fundamentos = is_array($actividad->infracciones_actividad)
            ? $actividad->infracciones_actividad
            : [];
        if ($fundamentos !== []) {
            $texto .= "FUNDAMENTO(S)\n";
            foreach ($fundamentos as $index => $fundamento) {
                if (!is_array($fundamento)) {
                    continue;
                }
                $referencia = trim((string) ($fundamento['referencia_legal_corta'] ?? $fundamento['codigo'] ?? ''));
                $nombre = trim((string) ($fundamento['nombre'] ?? $fundamento['descripcion'] ?? 'Fundamento legal'));
                $legal = trim((string) ($fundamento['fundamento_legal'] ?? ''));
                $texto .= ($index + 1) . '. ' . trim($referencia . ' ' . $nombre) . "\n";
                if ($legal !== '') {
                    $texto .= "   {$legal}\n";
                }
            }
            $texto .= "\n";
        }

        if ($actividad->narrativa) {
            $texto .= trim((string) $actividad->narrativa) . "\n\n";
        }

        if ($actividad->acciones_realizadas) {
            $texto .= trim((string) $actividad->acciones_realizadas) . "\n\n";
        }

        if ($actividad->observaciones) {
            $texto .= trim((string) $actividad->observaciones) . "\n\n";
        }

        if ($actividad->personas_alcanzadas !== null || $actividad->personas_participantes !== null || $actividad->personas_detenidas !== null) {
            $texto .= "DATOS GENERALES\n";
            $texto .= "PERSONAS ALCANZADAS: " . (int) ($actividad->personas_alcanzadas ?? 0) . "\n";
            $texto .= "PERSONAS PARTICIPANTES: " . (int) ($actividad->personas_participantes ?? 0) . "\n";
            $texto .= "PERSONAS DETENIDAS: " . (int) ($actividad->personas_detenidas ?? 0) . "\n\n";
        }

        $conductores = $actividad->vehiculos->flatMap(function (Vehiculo $vehiculo) {
            return $vehiculo->conductores->map(fn (Conductor $conductor) => [
                'persona' => $conductor,
                'vehiculo' => $vehiculo,
            ]);
        });
        if ($conductores->isNotEmpty() || $actividad->personas->isNotEmpty()) {
            $texto .= "CONDUCTORES Y PERSONAS\n";
            foreach ($conductores as $item) {
                $placas = trim((string) ($item['vehiculo']->placas ?: 'SIN PLACAS'));
                $texto .= '- CONDUCTOR: ' . $item['persona']->nombre . " ({$placas})\n";
            }
            foreach ($actividad->personas as $persona) {
                $relacion = $persona->vehiculo
                    ? ' (' . ($persona->vehiculo->placas ?: trim($persona->vehiculo->marca . ' ' . $persona->vehiculo->linea)) . ')'
                    : '';
                $texto .= '- ' . $persona->tipo_participacion . ': ' . $persona->nombre . $relacion . "\n";
            }
            $texto .= "\n";
        }

        if ($actividad->elementos_participantes_texto) {
            $texto .= "ESTADO DE FUERZA\n";
            $texto .= trim((string) $actividad->elementos_participantes_texto) . "\n\n";
        }

        if ($actividad->patrullas_participantes_texto) {
            $texto .= "CRP\n";
            $texto .= trim((string) $actividad->patrullas_participantes_texto) . "\n\n";
        }

        $texto .= "Informa '{$informante}'\n\n";

        $fotos = $actividad->fotos
            ->sortBy([['orden', 'asc'], ['id', 'asc']])
            ->map(function ($foto) {
                $path = $foto->foto_path ?: $foto->foto_blob_path ?: $foto->foto_thumbnail_path ?: $foto->foto_thumbnail_blob_path;

                return $path ? route('actividades.fotos.archivo', [$foto->id, 'original']) : null;
            })
            ->filter()
            ->values();

        $fotoActividad = $actividad->foto_path ?: $actividad->foto_blob_path ?: $actividad->foto_thumbnail_path ?: $actividad->foto_thumbnail_blob_path;

        if ($fotos->isEmpty() && $fotoActividad) {
            $fotos = collect([
                route('actividades.fotos.principal_archivo', [$actividad->id, 'original']),
            ]);
        }

        return response()->json([
            'texto' => trim($texto),
            'fotos' => $fotos,
        ]);
    }

    public function compartirTotalesWhatsapp(Request $request)
    {
        $usuario = Auth::user();

        if (!$usuario || !$usuario->can('ver actividades')) {
            abort(403);
        }

        $tz = 'America/Mexico_City';

        $fechaSeleccionada = $request->filled('fecha')
            ? $request->input('fecha')
            : now($tz)->toDateString();

        try {
            $fecha = Carbon::createFromFormat('Y-m-d', (string) $fechaSeleccionada, $tz)->toDateString();
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'La fecha proporcionada no es válida.',
            ], 422);
        }

        $unidadId = (int) ($usuario->unidad_id ?? 0);

        if ($unidadId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'El usuario no tiene una unidad asignada.',
            ], 422);
        }

        $unidad = Unidad::find($unidadId);

        if (!$unidad) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró la unidad del usuario.',
            ], 404);
        }

        $categorias = ActividadCategoria::query()
            ->select(
                'actividad_categorias.id',
                'actividad_categorias.nombre',
                DB::raw('SUM(actividades.cantidad) as total')
            )
            ->join('actividades', 'actividades.actividad_categoria_id', '=', 'actividad_categorias.id')
            ->whereDate('actividades.fecha', $fecha)
            ->where('actividades.unidad_org_id', $unidadId)
            ->groupBy('actividad_categorias.id', 'actividad_categorias.nombre')
            ->orderBy('actividad_categorias.nombre')
            ->get();

        if ($categorias->isEmpty()) {
            return response()->json([
                'ok' => false,
                'message' => 'No hay actividades registradas para esa fecha.',
            ], 404);
        }

        $subcategoriasPorCategoria = ActividadSubcategoria::query()
            ->select(
                'actividad_subcategorias.actividad_categoria_id',
                'actividad_subcategorias.nombre'
            )
            ->join('actividades', 'actividades.actividad_subcategoria_id', '=', 'actividad_subcategorias.id')
            ->whereDate('actividades.fecha', $fecha)
            ->where('actividades.unidad_org_id', $unidadId)
            ->whereNotNull('actividades.actividad_subcategoria_id')
            ->groupBy('actividad_subcategorias.actividad_categoria_id', 'actividad_subcategorias.nombre')
            ->orderBy('actividad_subcategorias.nombre')
            ->get()
            ->groupBy('actividad_categoria_id');

        $fechaCarbon = Carbon::parse($fecha, $tz)->locale('es');
        $fechaTexto = mb_strtoupper($fechaCarbon->translatedFormat('l d F Y'), 'UTF-8');

        $nombreUnidad = mb_strtoupper((string) $unidad->nombre, 'UTF-8');

        $texto = "GUARDIA CIVIL\n";
        $texto .= "COORDINACIÓN DEL AGRUPAMIENTO DE SEGURIDAD VIAL\n";
        $texto .= $nombreUnidad . "\n";
        $texto .= $fechaTexto . "\n";
        $texto .= "ACTIVIDADES RELEVANTES DE LAS 06:00 A LAS 21:00 HORAS\n\n";

        foreach ($categorias as $categoria) {
            $texto .= '- ' . trim($categoria->nombre) . ': ' . str_pad((string) ((int) $categoria->total), 2, '0', STR_PAD_LEFT) . "\n";

            $subcats = $subcategoriasPorCategoria->get($categoria->id, collect());

            foreach ($subcats as $subcat) {
                $texto .= '- ' . trim($subcat->nombre) . "\n";
            }

            $texto .= "\n";
        }

        $subdirector = DB::table('personals')
            ->where('unidad_id', $unidadId)
            ->whereRaw('UPPER(puesto) = ?', ['SUBDIRECTOR'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        if ($subdirector) {
            $nombreSubdirector = trim(collect([
                $subdirector->grado ?? null,
                Personal::formarNombreCompleto(
                    $subdirector->nombre ?? null,
                    $subdirector->ap_paterno ?? null,
                    $subdirector->ap_materno ?? null
                ),
            ])->filter()->implode(' '));

            $texto .= "RESPETUOSAMENTE\n";
            $texto .= 'SUBDIRECTOR DE ' . $nombreUnidad . "\n";
            $texto .= mb_strtoupper($nombreSubdirector, 'UTF-8');
        }

        return response()->json([
            'ok' => true,
            'texto' => trim($texto),
            'fotos' => [],
        ]);
    }

    public function storeVehiculo(Request $request, Actividad $actividad)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        if (app(FomentoCulturaVialDetalleManager::class)->usuarioEsFomento($usuario)) {
            return back()->with('error', 'Fomento a la Cultura Vial no captura vehículos relacionados en actividades.');
        }

        $validated = $this->validarVehiculoRequest($request);

        if (!$this->gruaPermitidaParaUsuario($validated['grua_id'] ?? null, $usuario)) {
            return back()->withErrors([
                'grua_id' => 'La grúa seleccionada no está disponible para tu unidad o delegación.',
            ])->withInput();
        }

        return DB::transaction(function () use ($actividad, $validated) {
            $this->crearVehiculoParaActividad($actividad, $validated);

            return back()->with('success', 'Vehículo agregado correctamente.');
        });
    }

    public function destroyVehiculo(Actividad $actividad, $vehiculoId)
    {
        $this->authorize('editar actividades');

        $usuario = Auth::user();

        $q = Actividad::query()->whereKey($actividad->id);
        $this->applyActividadesVisibilityScope($q, $usuario);

        if (!$q->exists()) {
            abort(404);
        }

        $vehiculo = Vehiculo::findOrFail($vehiculoId);

        if (!$actividad->vehiculos()->where('vehiculos.id', $vehiculo->id)->exists()) {
            abort(404);
        }

        if (
            GruaEditGuard::locksActividad($usuario, $actividad)
            && GruaEditGuard::vehicleHasGruaData($vehiculo)
        ) {
            return back()->with('error', 'La grúa o corralón de este vehículo está bloqueado. Solicita autorización de un Administrador.');
        }

        return DB::transaction(function () use ($actividad, $vehiculoId) {
            $actividad->vehiculos()->detach($vehiculoId);

            $tieneOtroOrigen = DB::table('hecho_vehiculo')->where('vehiculo_id', $vehiculoId)->exists()
                || DB::table('actividad_vehiculo')->where('vehiculo_id', $vehiculoId)->exists()
                || DB::table('operativo_dispositivo_vehiculo')->where('vehiculo_id', $vehiculoId)->exists()
                || DB::table('puestas_disposicion_vehiculos')->where('vehiculo_id', $vehiculoId)->exists();

            if (!$tieneOtroOrigen) {
                DB::table('servicios')->where('vehiculo_id', $vehiculoId)->delete();
            }

            return back()->with('success', 'Vehículo desvinculado correctamente.');
        });
    }

    private function validarPersonasActividad(array $personas, int $vehiculosCount): void
    {
        $conductoresPorVehiculo = [];

        foreach (array_values($personas) as $index => $persona) {
            $tipo = mb_strtoupper(trim((string) ($persona['tipo_participacion'] ?? '')), 'UTF-8');
            $tieneVehiculo = array_key_exists('vehiculo_indice', $persona)
                && $persona['vehiculo_indice'] !== null
                && $persona['vehiculo_indice'] !== '';
            $vehiculoIndice = $tieneVehiculo ? (int) $persona['vehiculo_indice'] : null;

            if ($tieneVehiculo && ($vehiculoIndice < 0 || $vehiculoIndice >= $vehiculosCount)) {
                throw ValidationException::withMessages([
                    "personas.{$index}.vehiculo_indice" => 'El vehículo seleccionado ya no está disponible.',
                ]);
            }

            if (in_array($tipo, ['CONDUCTOR', 'PASAJERO'], true) && !$tieneVehiculo) {
                throw ValidationException::withMessages([
                    "personas.{$index}.vehiculo_indice" => "Selecciona el vehículo de esta persona ({$tipo}).",
                ]);
            }

            if ($tipo !== 'CONDUCTOR') {
                continue;
            }

            if (isset($conductoresPorVehiculo[$vehiculoIndice])) {
                throw ValidationException::withMessages([
                    "personas.{$index}.vehiculo_indice" => 'Cada vehículo admite únicamente un conductor en esta actividad.',
                ]);
            }

            $conductoresPorVehiculo[$vehiculoIndice] = true;
        }

    }

    private function userCanWriteCoordinates($usuario): bool
    {
        return $usuario && $usuario->hasRole('Administrativo');
    }

    private function coordinatesTextRules(bool $canWrite): array
    {
        $rules = ['nullable', 'string', 'max:100'];

        if ($canWrite) {
            $rules[] = function ($attribute, $value, $fail) {
                $text = trim((string) $value);

                if ($text !== '' && !$this->parseCoordinates($text)) {
                    $fail('Captura las coordenadas en formato latitud, longitud.');
                }
            };
        }

        return $rules;
    }

    private function normalizeWritableCoordinates(Request $request, bool $canWrite): void
    {
        if (!$canWrite || !$request->exists('coordenadas_texto')) {
            return;
        }

        $text = trim((string) $request->input('coordenadas_texto', ''));

        if ($text === '') {
            $request->merge([
                'lat' => null,
                'lng' => null,
                'coordenadas_texto' => null,
                'fuente_ubicacion' => null,
                'nota_geo' => null,
            ]);
            return;
        }

        $coordinates = $this->parseCoordinates($text);

        if (!$coordinates) {
            return;
        }

        $submittedLat = $request->input('lat');
        $submittedLng = $request->input('lng');
        $lat = number_format($coordinates['lat'], 7, '.', '');
        $lng = number_format($coordinates['lng'], 7, '.', '');
        $source = strtoupper(trim((string) $request->input('fuente_ubicacion', '')));
        $coordinatesUnchanged = is_numeric($submittedLat) && is_numeric($submittedLng)
            && number_format((float) $submittedLat, 7, '.', '') === $lat
            && number_format((float) $submittedLng, 7, '.', '') === $lng;
        $finalSource = ($source === 'GPS_WEB' || $coordinatesUnchanged)
            ? ($source ?: 'MANUAL_WEB')
            : 'MANUAL_WEB';

        $request->merge([
            'lat' => $lat,
            'lng' => $lng,
            'coordenadas_texto' => "{$lat}, {$lng}",
            'fuente_ubicacion' => $finalSource,
            'nota_geo' => ($source === 'GPS_WEB' || $coordinatesUnchanged) ? $request->input('nota_geo') : null,
        ]);
    }

    private function parseCoordinates(string $text): ?array
    {
        if (!preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $text, $matches)) {
            return null;
        }

        $lat = (float) $matches[1];
        $lng = (float) $matches[2];

        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    private function snapshotFundamentosActividad(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $catalogo = LicenciaPuntoInfraccion::activas()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($catalogo->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'fundamento_ids' => 'Uno de los fundamentos seleccionados ya no está disponible.',
            ]);
        }

        return collect($ids)->map(function (int $id) use ($catalogo) {
            /** @var LicenciaPuntoInfraccion $fundamento */
            $fundamento = $catalogo->get($id);

            return [
                'id' => $fundamento->id,
                'codigo' => $this->textoNullable($fundamento->codigo),
                'nombre' => $this->textoNullable($fundamento->nombre) ?: 'Fundamento legal',
                'etiqueta_operativa' => $this->textoNullable($fundamento->etiqueta_operativa),
                'texto_operativo' => $this->textoNullable($fundamento->texto_operativo),
                'descripcion' => $this->textoNullable($fundamento->descripcion),
                'fundamento_legal' => $this->textoNullable($fundamento->fundamento_legal),
                'referencia_legal_corta' => $this->textoNullable($fundamento->referencia_legal_corta),
                'resumen_sanciones' => $this->textoNullable($fundamento->resumen_sanciones),
                'retencion_vehiculo' => (bool) $fundamento->retencion_vehiculo,
                'deposito_si_sin_persona_habilitada' => (bool) $fundamento->deposito_si_sin_persona_habilitada,
            ];
        })->all();
    }

    private function crearPersonasParaActividad(Actividad $actividad, array $personas, array $vehiculos): void
    {
        foreach (array_values($personas) as $persona) {
            $tipo = mb_strtoupper(trim((string) ($persona['tipo_participacion'] ?? 'OTRO')), 'UTF-8');
            $vehiculoIndice = isset($persona['vehiculo_indice']) && $persona['vehiculo_indice'] !== ''
                ? (int) $persona['vehiculo_indice']
                : null;
            $vehiculo = $vehiculoIndice !== null ? ($vehiculos[$vehiculoIndice] ?? null) : null;

            if ($tipo === 'CONDUCTOR') {
                $conductor = Conductor::create([
                    'client_uuid' => (string) Str::uuid(),
                    'nombre' => $this->toUpperOrNull($persona['nombre'] ?? null),
                    'edad' => $persona['edad'] ?? null,
                    'domicilio' => $this->toUpperOrNull($persona['domicilio'] ?? null),
                    'antecedentes' => (bool) ($persona['antecedentes'] ?? false),
                    'estado_licencia' => $this->toUpperOrNull($persona['estado_licencia'] ?? null),
                    'vigencia_licencia' => $persona['vigencia_licencia'] ?? null,
                    'numero_licencia' => $this->toUpperOrNull($persona['numero_licencia'] ?? null),
                    'permanente' => (bool) ($persona['permanente'] ?? false),
                    'ocupacion' => $this->toUpperOrNull($persona['ocupacion'] ?? null),
                    'telefono' => trim((string) ($persona['telefono'] ?? '')) ?: null,
                    'sexo' => $this->toUpperOrNull($persona['sexo'] ?? null),
                    'tipo_licencia' => $this->toUpperOrNull($persona['tipo_licencia'] ?? null),
                ]);

                $vehiculo->conductores()->syncWithoutDetaching([$conductor->id]);
                continue;
            }

            ActividadPersona::create([
                'actividad_id' => $actividad->id,
                'vehiculo_id' => $vehiculo ? $vehiculo->id : null,
                'tipo_participacion' => $tipo,
                'nombre' => $this->toUpperOrNull($persona['nombre'] ?? null),
                'telefono' => trim((string) ($persona['telefono'] ?? '')) ?: null,
                'domicilio' => $this->toUpperOrNull($persona['domicilio'] ?? null),
                'sexo' => $this->toUpperOrNull($persona['sexo'] ?? null),
                'nacionalidad' => $this->toUpperOrNull($persona['nacionalidad'] ?? null),
                'ocupacion' => $this->toUpperOrNull($persona['ocupacion'] ?? null),
                'edad' => $persona['edad'] ?? null,
                'observaciones' => $this->toUpperOrNull($persona['observaciones'] ?? null),
            ]);
        }
    }

    private function textoNullable($value): ?string
    {
        $texto = trim((string) ($value ?? ''));

        return $texto !== '' ? $texto : null;
    }

    private function validarVehiculoRequest(Request $request): array
    {
        $request->merge($this->normalizarVehiculoData($request->only(array_keys($this->vehiculoRules()))));

        return $request->validate($this->vehiculoRules());
    }

    private function vehiculoRules(): array
    {
        return [
            'marca' => 'required|string|max:50',
            'modelo' => 'nullable|string|max:10',
            'tipo' => 'required|string|max:50',
            'linea' => 'required|string|max:50',
            'color' => 'required|string|max:30',
            'placas' => 'nullable|string|max:15',
            'estado_placas' => 'nullable|string|max:15',
            'serie' => 'nullable|string|max:17',
            'capacidad_personas' => 'required|integer|min:0',
            'tipo_servicio' => 'required|string|max:50',
            'tarjeta_circulacion_nombre' => 'nullable|string|max:60',
            'grua_id' => 'nullable|integer|exists:gruas,id',
            'grua' => 'nullable|string|max:255',
            'corralon' => 'nullable|string|max:255',
            'aseguradora' => 'nullable|string|max:100',
            'antecedente_vehiculo' => 'nullable|boolean',
            'monto_danos' => 'nullable|numeric|min:0',
            'partes_danadas' => 'nullable|string',
        ];
    }

    private function normalizarVehiculoData(array $data): array
    {
        foreach ([
            'marca',
            'modelo',
            'tipo',
            'linea',
            'color',
            'placas',
            'estado_placas',
            'serie',
            'tipo_servicio',
            'tarjeta_circulacion_nombre',
            'grua_id',
            'grua',
            'corralon',
            'aseguradora',
            'partes_danadas',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        return $data;
    }

    private function crearVehiculoParaActividad(Actividad $actividad, array $data): Vehiculo
    {
        $data = $this->normalizarVehiculoData($data);
        $gruaId = !empty($data['grua_id']) ? (int) $data['grua_id'] : null;
        $nombreGrua = null;

        if ($gruaId) {
            $nombreGrua = Grua::query()
                ->whereKey($gruaId)
                ->value('nombre');
        }

        $vehiculo = Vehiculo::create([
            'client_uuid' => (string) Str::uuid(),
            'marca' => $this->toUpperOrNull($data['marca'] ?? null),
            'modelo' => $this->toUpperOrNull($data['modelo'] ?? null),
            'tipo' => $this->toUpperOrNull($data['tipo'] ?? null),
            'linea' => $this->toUpperOrNull($data['linea'] ?? null),
            'color' => $this->toUpperOrNull($data['color'] ?? null),
            'placas' => $this->toUpperOrNull(str_replace('-', '', (string) ($data['placas'] ?? ''))),
            'estado_placas' => $this->toUpperOrNull($data['estado_placas'] ?? null),
            'serie' => $this->toUpperOrNull(str_replace('-', '', (string) ($data['serie'] ?? ''))),
            'capacidad_personas' => $data['capacidad_personas'] ?? 0,
            'tipo_servicio' => $this->toUpperOrNull($data['tipo_servicio'] ?? null),
            'tarjeta_circulacion_nombre' => $this->toUpperOrNull($data['tarjeta_circulacion_nombre'] ?? null),
            'grua' => $this->toUpperOrNull($nombreGrua ?: ($data['grua'] ?? null)),
            'grua_id' => $gruaId,
            'corralon' => $this->toUpperOrNull($data['corralon'] ?? null),
            'aseguradora' => $this->toUpperOrNull($data['aseguradora'] ?? null),
            'fotos' => null,
            'antecedente_vehiculo' => (int) ($data['antecedente_vehiculo'] ?? 0),
            'monto_danos' => $data['monto_danos'] ?? 0,
            'partes_danadas' => $this->toUpperOrNull($data['partes_danadas'] ?? null),
        ]);

        $actividad->vehiculos()->syncWithoutDetaching([$vehiculo->id]);

        $this->registrarServicioGruaParaActividad($actividad, $vehiculo, $data);

        return $vehiculo;
    }

    private function registrarServicioGruaParaActividad(Actividad $actividad, Vehiculo $vehiculo, array $data): void
    {
        $gruaId = !empty($data['grua_id']) ? (int) $data['grua_id'] : null;

        if (!$gruaId) {
            return;
        }

        $unidadId = (int) ($actividad->unidad_org_id ?? 0);
        $unidadId = $unidadId > 0 ? $unidadId : 1;

        DB::table('servicios')->updateOrInsert(
            ['vehiculo_id' => $vehiculo->id],
            [
                'grua_id' => $gruaId,
                'unidad_id' => $unidadId,
                'delegacion_id' => $actividad->delegacion_id,
                'tipo_vehiculo' => $this->toUpperOrNull($data['tipo'] ?? $vehiculo->tipo),
                'aseguradora' => $this->toUpperOrNull($data['aseguradora'] ?? $vehiculo->aseguradora) ?? '',
                'created_at' => $this->fechaServicioActividad($actividad),
                'updated_at' => now(),
            ]
        );
    }

    private function fechaServicioActividad(Actividad $actividad): string
    {
        $fecha = $actividad->fecha
            ? Carbon::parse($actividad->fecha)->format('Y-m-d')
            : now('America/Mexico_City')->toDateString();

        $hora = $actividad->hora
            ? Carbon::parse($actividad->hora)->format('H:i:s')
            : '12:00:00';

        return $fecha . ' ' . $hora;
    }

    private function formatearHoraActividad($hora): string
    {
        if (!$hora) {
            return '';
        }

        if ($hora instanceof \DateTimeInterface) {
            return $hora->format('H:i');
        }

        $horaTexto = trim((string) $hora);

        if ($horaTexto === '') {
            return '';
        }

        if (preg_match('/\b(\d{1,2}):(\d{2})(?::\d{2})?\b/', $horaTexto, $matches)) {
            return sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        }

        return '';
    }

    private function coordenadasActividad(Actividad $actividad): array
    {
        $lat = $this->formatearCoordenadaActividad($actividad->lat ?? null);
        $lng = $this->formatearCoordenadaActividad($actividad->lng ?? null);

        if ($lat !== null && $lng !== null) {
            return [$lat, $lng, "{$lat}, {$lng}"];
        }

        $coordenadasTexto = trim((string) ($actividad->coordenadas_texto ?? ''));

        return [null, null, $coordenadasTexto];
    }

    private function formatearCoordenadaActividad($coordenada): ?string
    {
        if ($coordenada === null || $coordenada === '') {
            return null;
        }

        if (!is_numeric($coordenada)) {
            return null;
        }

        return number_format((float) $coordenada, 7, '.', '');
    }

    private function informanteActividad(Actividad $actividad): string
    {
        $nombre = trim((string) optional($actividad->creador)->name);

        return $nombre !== '' ? $nombre : 'USUARIO NO REGISTRADO';
    }

    private function obtenerGruasDisponiblesParaUsuario($usuario)
    {
        return $this->gruasDisponiblesQuery($usuario)->get();
    }

    private function gruaPermitidaParaUsuario($gruaId, $usuario): bool
    {
        if (empty($gruaId)) {
            return true;
        }

        return $this->gruasDisponiblesQuery($usuario)
            ->where('gruas.id', (int) $gruaId)
            ->exists();
    }

    private function validarGruasPermitidasEnVehiculos(array $vehiculos, $usuario)
    {
        foreach ($vehiculos as $index => $vehiculo) {
            if (!$this->gruaPermitidaParaUsuario($vehiculo['grua_id'] ?? null, $usuario)) {
                return back()->withErrors([
                    "vehiculos.{$index}.grua_id" => 'La grúa seleccionada no está disponible para tu unidad o delegación.',
                ])->withInput();
            }
        }

        return null;
    }

    private function gruasDisponiblesQuery($usuario)
    {
        $query = Grua::query()->orderBy('nombre');

        if (!$usuario) {
            return $query->whereRaw('1 = 0');
        }

        if (GruaEditGuard::canViewFullGruaCatalog($usuario)) {
            return $query;
        }

        if (GruaEditGuard::usesSiniestrosGruaCatalog($usuario)) {
            return $query->whereHas('unidades', function ($q) {
                $q->where('unidades.id', 1);
            });
        }

        if ((int) $usuario->unidad_id === 2) {
            $delegacionIds = $this->obtenerIdsDelegacionesGruasUsuario($usuario);

            if (empty($delegacionIds)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereHas('delegaciones', function ($q) use ($delegacionIds) {
                $q->whereIn('delegaciones.id', $delegacionIds);
            });
        }

        return $query->whereRaw('1 = 0');
    }

    private function obtenerIdsDelegacionesGruasUsuario($usuario): array
    {
        $ids = [];

        if (!empty($usuario->delegacion_id)) {
            $ids[] = (int) $usuario->delegacion_id;
        }

        $idsPivot = DB::table('delegacion_user')
            ->where('user_id', $usuario->id)
            ->pluck('delegacion_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->toArray();

        return array_values(array_unique(array_merge($ids, $idsPivot)));
    }
}
