<?php

namespace App\Http\Controllers;

use App\Models\Personal;
use App\Models\DocumentoTipo;
use App\Models\PersonalDocumento;
use App\Models\PersonalLicencia;
use App\Models\Unidad;
use App\Models\Turno;
use App\Models\Patrulla;
use App\Models\Armamento;
use App\Models\PersonalAsignacion;
use App\Models\User;
use App\Services\Fotos\PersonalFotoStorage;
use App\Services\Documentos\DocumentoArchivoStorage;
use App\Services\Personal\PersonalExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class PersonalController extends Controller
{
    private function actor()
    {
        return Auth::user();
    }

    private function actorEsSuperadmin(): bool
    {
        $actor = $this->actor();
        return $actor && $actor->hasRole('Superadmin');
    }

    private function actorEsAdministrador(): bool
    {
        $actor = $this->actor();
        return $actor && $actor->hasRole('Administrador') && !$actor->hasRole('Superadmin');
    }

    private function actorTieneVisibilidadGlobal(): bool
    {
        $actor = $this->actor();
        return $this->actorEsSuperadmin() || (int) ($actor->unidad_id ?? 0) === 3;
    }

    private function unidadIdActor(): ?int
    {
        return optional($this->actor())->unidad_id;
    }

    private function queryPersonalVisibleParaActor()
    {
        return Personal::query()
            ->when(!$this->actorTieneVisibilidadGlobal(), function ($q) {
                $q->where('unidad_id', $this->unidadIdActor());
            });
    }

    private function buscarPersonalVisibleOFail($id): Personal
    {
        return $this->queryPersonalVisibleParaActor()->findOrFail($id);
    }

    private function unidadesDisponiblesParaActor()
    {
        return Unidad::query()
            ->where('activa', 1)
            ->when(!$this->actorTieneVisibilidadGlobal(), function ($q) {
                $q->where('id', $this->unidadIdActor());
            })
            ->orderBy('nombre')
            ->get();
    }

    private function turnosDisponiblesParaActor()
    {
        return Turno::query()
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();
    }

    private function patrullasDisponiblesParaActor(?int $unidadId = null, ?int $personalIdExcluir = null)
    {
        return Patrulla::query()
            ->where('activa', 1)
            ->when($this->actorTieneVisibilidadGlobal(), function ($q) use ($unidadId) {
                if (!empty($unidadId)) {
                    $q->where('unidad_id', $unidadId);
                }
            })
            ->when(!$this->actorTieneVisibilidadGlobal(), function ($q) {
                $q->where('unidad_id', $this->unidadIdActor());
            })
            ->when($personalIdExcluir !== null, function ($q) use ($personalIdExcluir) {
                $q->whereDoesntHave('personal', function ($subQ) use ($personalIdExcluir) {
                    $subQ->whereNull('deleted_at')
                        ->where('estatus', 'ACTIVO')
                        ->where('id', '!=', $personalIdExcluir);
                });
            }, function ($q) {
                $q->whereDoesntHave('personal', function ($subQ) {
                    $subQ->whereNull('deleted_at')
                        ->where('estatus', 'ACTIVO');
                });
            })
            ->orderBy('numero_economico')
            ->get();
    }

    private function usuariosDisponiblesParaActor(?int $userIdActual = null, ?int $unidadId = null)
    {
        return User::query()
            ->when(!$this->actorEsSuperadmin(), function ($q) {
                $q->whereDoesntHave('roles', function ($subQ) {
                    $subQ->where('name', 'Superadmin');
                });

                if (!$this->actorTieneVisibilidadGlobal()) {
                    $q->where('unidad_id', $this->unidadIdActor());
                }
            })
            ->when(!empty($unidadId), function ($q) use ($unidadId, $userIdActual) {
                $q->where(function ($subQ) use ($unidadId, $userIdActual) {
                    $subQ->where('unidad_id', $unidadId);

                    if ($userIdActual) {
                        $subQ->orWhere('id', $userIdActual);
                    }
                });
            })
            ->where(function ($q) use ($userIdActual) {
                $q->whereDoesntHave('personal');

                if ($userIdActual) {
                    $q->orWhere('id', $userIdActual);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'unidad_id']);
    }

    private function normalizarUnidadParaActor(?int $unidadId): ?int
    {
        if ($this->actorTieneVisibilidadGlobal()) {
            return $unidadId;
        }

        return $this->unidadIdActor();
    }

    private function patrullaPerteneceAUnidad(?int $patrullaId, ?int $unidadId): bool
    {
        if (empty($patrullaId)) {
            return true;
        }

        return Patrulla::query()
            ->where('id', $patrullaId)
            ->where('activa', 1)
            ->where('unidad_id', $unidadId)
            ->exists();
    }

    private function usuarioPerteneceAUnidadPermitida(?int $userId, ?int $unidadId, ?int $personalIdActual = null): bool
    {
        if (empty($userId)) {
            return true;
        }

        $query = User::query()->where('id', $userId);

        if (!$this->actorEsSuperadmin()) {
            $query->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Superadmin');
            });
        }

        $user = $query->first();

        if (!$user) {
            return false;
        }

        $esUsuarioActual = $personalIdActual !== null
            && Personal::query()
                ->whereKey($personalIdActual)
                ->where('user_id', $userId)
                ->exists();

        if ((int) $user->unidad_id !== (int) $unidadId && !$esUsuarioActual) {
            return false;
        }

        $ocupado = Personal::query()
            ->where('user_id', $userId)
            ->when($personalIdActual !== null, function ($q) use ($personalIdActual) {
                $q->where('id', '!=', $personalIdActual);
            })
            ->exists();

        return !$ocupado;
    }

    public function index()
    {
        $personals = $this->queryPersonalVisibleParaActor()
            ->with(['unidad', 'turno', 'patrulla', 'user'])
            ->orderByDesc('estatus')
            ->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->orderBy('nombre')
            ->get();
        $unidadImportacion = optional($this->actor())->unidad;

        return view('admin.settings.personal.index', compact('personals', 'unidadImportacion'));
    }

    public function importar(Request $request, PersonalExcelImportService $importador)
    {
        $validated = $request->validate([
            'archivo_personal' => 'required|file|mimes:xlsx,xls|max:51200',
        ], [
            'archivo_personal.required' => 'Seleccione el archivo Excel de personal.',
            'archivo_personal.mimes' => 'El archivo debe ser de Excel (.xlsx o .xls).',
            'archivo_personal.max' => 'El archivo no debe superar 50 MB.',
        ]);

        $actor = $this->actor();
        $unidad = $actor && $actor->unidad_id
            ? Unidad::query()->whereKey($actor->unidad_id)->where('activa', 1)->first()
            : null;

        if (!$unidad) {
            return redirect()
                ->route('personal.index')
                ->withErrors(['archivo_personal' => 'Su usuario no tiene una unidad activa asignada; no se realizó la importación.']);
        }

        try {
            $resultado = $importador->importar(
                $validated['archivo_personal']->getRealPath(),
                (int) $unidad->id
            );

            $resultado['unidad'] = $unidad->nombre;
            Log::info('Importación de personal finalizada.', [
                'user_id' => optional($actor)->id,
                'unidad_id' => $unidad->id,
                'total' => $resultado['total'] ?? 0,
                'importados' => $resultado['importados'] ?? 0,
                'restaurados' => $resultado['restaurados'] ?? 0,
                'complementados' => $resultado['complementados'] ?? 0,
                'omitidos' => $resultado['omitidos'] ?? 0,
                'contactos_importados' => $resultado['contactos_importados'] ?? 0,
                'emergencias_importadas' => $resultado['emergencias_importadas'] ?? 0,
                'advertencias' => count($resultado['advertencias'] ?? []),
            ]);

            return redirect()
                ->route('personal.index')
                ->with('import_result', $resultado);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('personal.index')
                ->withErrors(['archivo_personal' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('Error al importar personal desde Excel: ' . $e->getMessage(), [
                'user_id' => optional($actor)->id,
                'unidad_id' => optional($actor)->unidad_id,
                'exception' => $e,
            ]);

            return redirect()
                ->route('personal.index')
                ->withErrors(['archivo_personal' => 'No fue posible procesar el archivo. Revise la plantilla e inténtelo nuevamente.']);
        }
    }

    public function create()
    {
        $unidadIdDefault = $this->actorEsSuperadmin() ? null : $this->unidadIdActor();

        $unidades = $this->unidadesDisponiblesParaActor();
        $turnos = $this->turnosDisponiblesParaActor();
        $patrullas = $this->patrullasDisponiblesParaActor($unidadIdDefault);
        $usuariosDisponibles = $this->usuariosDisponiblesParaActor(null, $unidadIdDefault);
        $categoriasPersonal = ['OPERATIVO', 'ADMINISTRATIVO'];
        $tiposSangre = Personal::TIPOS_SANGRE;
        $gradosEstudio = Personal::GRADOS_ESTUDIO;
        $estadosAlergias = Personal::ESTADOS_ALERGIAS;

        return view('admin.settings.personal.create', compact(
            'unidades',
            'turnos',
            'patrullas',
            'usuariosDisponibles',
            'categoriasPersonal',
            'tiposSangre',
            'gradosEstudio',
            'estadosAlergias',
            'unidadIdDefault'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'user_id' => 'nullable|exists:users,id|unique:personals,user_id',

            'numero_empleado' => 'nullable|string|max:50',
            'numero_placa' => 'nullable|string|max:50',

            'nombre' => 'required|string|max:100',
            'ap_paterno' => 'nullable|string|max:100',
            'ap_materno' => 'nullable|string|max:100',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'tipo_sangre' => ['nullable', Rule::in(array_keys(Personal::TIPOS_SANGRE))],

            'curp' => 'nullable|string|max:18|unique:personals,curp',
            'rfc' => 'nullable|string|max:13',
            'numero_seguro_social' => 'nullable|string|max:20|unique:personals,numero_seguro_social',
            'correo_electronico' => 'nullable|email|max:255',

            'cuip' => 'nullable|string|max:30|unique:personals,cuip',
            'cup' => 'nullable|string|max:100|unique:personals,cup',

            'grado' => 'nullable|string|max:120',
            'puesto' => 'nullable|string|max:120',

            'adscripcion' => 'nullable|string|max:200',
            'area' => 'nullable|string|max:200',
            'ultimo_grado_estudios' => ['nullable', Rule::in(array_keys(Personal::GRADOS_ESTUDIO))],
            'alergias_estado' => ['nullable', Rule::in(array_keys(Personal::ESTADOS_ALERGIAS))],
            'alergias' => 'nullable|string|max:2000|required_if:alergias_estado,SI',

            'categoria' => 'required|in:OPERATIVO,ADMINISTRATIVO',
            'estatus' => 'required|string|max:30',
            'fecha_ingreso' => 'nullable|date',
            'fecha_ingreso_unidad' => 'nullable|date|after_or_equal:fecha_ingreso',
            'fecha_baja' => 'nullable|date',

            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'comprobante_estudios' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $validated['unidad_id'] = $this->normalizarUnidadParaActor($validated['unidad_id'] ?? null);

        try {
            if (!empty($validated['patrulla_id'])) {
                if (!$this->patrullaPerteneceAUnidad($validated['patrulla_id'], $validated['unidad_id'])) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'La patrulla seleccionada no pertenece a la unidad permitida o no está activa.'])
                        ->withInput();
                }

                $ocupada = Personal::query()
                    ->whereNull('deleted_at')
                    ->where('estatus', 'ACTIVO')
                    ->where('patrulla_id', $validated['patrulla_id'])
                    ->exists();

                if ($ocupada) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'Esa patrulla ya está asignada a otro elemento ACTIVO.'])
                        ->withInput();
                }
            }

            if (!empty($validated['user_id'])) {
                if (!$this->usuarioPerteneceAUnidadPermitida($validated['user_id'], $validated['unidad_id'])) {
                    return redirect()->back()
                        ->withErrors(['user_id' => 'El usuario seleccionado no pertenece a la unidad permitida o ya está asignado a otro registro de personal.'])
                        ->withInput();
                }
            }

            $fotoSubida = $request->file('foto');
            $comprobanteEstudios = $request->file('comprobante_estudios');
            unset($validated['foto']);
            unset($validated['comprobante_estudios']);

            if (($validated['alergias_estado'] ?? null) !== 'SI') {
                $validated['alergias'] = null;
            }

            $personal = Personal::create($validated);

            if ($comprobanteEstudios) {
                $this->guardarComprobanteEstudios($personal, $comprobanteEstudios);
            }

            if ($fotoSubida) {
                $rutaFoto = $this->guardarFotoPersonalPrivada($fotoSubida);

                $personal->update([
                    'foto' => $rutaFoto,
                ]);

                if (method_exists($personal, 'fotos')) {
                    $personal->fotos()->create([
                        'ruta' => $rutaFoto,
                        'nombre_original' => $fotoSubida->getClientOriginalName(),
                        'mime_type' => $fotoSubida->getClientMimeType(),
                        'tamano' => $fotoSubida->getSize(),
                    ]);
                }
            }

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal creado correctamente.');
        } catch (Throwable $e) {
            Log::error('Error al crear personal: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()
                ->back()
                ->withErrors('Hubo un error al crear el personal. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function show($id)
    {
        $personal = $this->buscarPersonalVisibleOFail($id);

        $personal->load([
            'user',
            'unidad',
            'turno',
            'patrulla',
            'rolesServicio',
            'incidencias.tipo',
            'documentos.documentoTipo',
            'licencias',
            'asignaciones',
            'contactos',
            'domicilios',
            'domicilioActual',
            'emergencias',
            'fotos',
            'fotoPrincipal',
            'asignaciones.armamento',
            'asignaciones.documento',
        ]);

        $asignacionesArmamentoActivas = PersonalAsignacion::query()
            ->where('personal_id', $personal->id)
            ->whereNotNull('armamento_id')
            ->whereNull('fecha_fin')
            ->with('armamento')
            ->orderByDesc('fecha_asignacion')
            ->get();

        $armamentosDisponibles = Armamento::query()
            ->where('estatus', 'ACTIVO')
            ->where('unidad_id', $personal->unidad_id)
            ->whereDoesntHave('asignaciones', function ($q) {
                $q->whereNull('fecha_fin');
            })
            ->orderBy('tipo')
            ->orderBy('clase')
            ->orderBy('marca')
            ->orderBy('modelo')
            ->get();

        $documentoTipos = DocumentoTipo::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $tiposLicencia = PersonalLicencia::tipos();

        return view('admin.settings.personal.show', compact(
            'personal',
            'documentoTipos',
            'tiposLicencia',
            'armamentosDisponibles',
            'asignacionesArmamentoActivas'
        ));
    }

    public function edit($id)
    {
        $personal = $this->buscarPersonalVisibleOFail($id);
        $personal->load('documentos.documentoTipo');

        $unidades = $this->unidadesDisponiblesParaActor();
        $turnos = $this->turnosDisponiblesParaActor();
        $patrullas = $this->patrullasDisponiblesParaActor(
            $this->actorEsSuperadmin() ? $personal->unidad_id : $this->unidadIdActor(),
            $personal->id
        );

        $usuariosDisponibles = $this->usuariosDisponiblesParaActor($personal->user_id, (int) $personal->unidad_id);
        $usuarioActual = $personal->user;
        $categoriasPersonal = ['OPERATIVO', 'ADMINISTRATIVO'];
        $tiposSangre = Personal::TIPOS_SANGRE;
        $gradosEstudio = Personal::GRADOS_ESTUDIO;
        $estadosAlergias = Personal::ESTADOS_ALERGIAS;
        $comprobanteEstudios = $personal->documentos
            ->first(fn (PersonalDocumento $documento) => optional($documento->documentoTipo)->clave === 'COMPROBANTE_ESTUDIOS');

        return view('admin.settings.personal.edit', compact(
            'personal',
            'unidades',
            'turnos',
            'patrullas',
            'usuariosDisponibles',
            'usuarioActual',
            'categoriasPersonal',
            'tiposSangre',
            'gradosEstudio',
            'estadosAlergias',
            'comprobanteEstudios'
        ));
    }

    public function update(Request $request, $id)
    {
        $personal = $this->buscarPersonalVisibleOFail($id);

        $validated = $request->validate([
            'unidad_id' => 'required|exists:unidades,id',
            'turno_id' => 'nullable|exists:turnos,id',
            'patrulla_id' => 'nullable|exists:patrullas,id',
            'user_id' => 'nullable|exists:users,id|unique:personals,user_id,' . $personal->id,

            'numero_empleado' => 'nullable|string|max:50',
            'numero_placa' => 'nullable|string|max:50',

            'nombre' => 'required|string|max:100',
            'ap_paterno' => 'nullable|string|max:100',
            'ap_materno' => 'nullable|string|max:100',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'tipo_sangre' => ['nullable', Rule::in(array_keys(Personal::TIPOS_SANGRE))],

            'curp' => 'nullable|string|max:18|unique:personals,curp,' . $personal->id,
            'rfc' => 'nullable|string|max:13',
            'numero_seguro_social' => 'nullable|string|max:20|unique:personals,numero_seguro_social,' . $personal->id,
            'correo_electronico' => 'nullable|email|max:255',

            'cuip' => 'nullable|string|max:30|unique:personals,cuip,' . $personal->id,
            'cup' => 'nullable|string|max:100|unique:personals,cup,' . $personal->id,

            'grado' => 'nullable|string|max:120',
            'puesto' => 'nullable|string|max:120',

            'adscripcion' => 'nullable|string|max:200',
            'area' => 'nullable|string|max:200',
            'ultimo_grado_estudios' => ['nullable', Rule::in(array_keys(Personal::GRADOS_ESTUDIO))],
            'alergias_estado' => ['nullable', Rule::in(array_keys(Personal::ESTADOS_ALERGIAS))],
            'alergias' => 'nullable|string|max:2000|required_if:alergias_estado,SI',

            'categoria' => 'required|in:OPERATIVO,ADMINISTRATIVO',
            'estatus' => 'required|string|max:30',
            'fecha_ingreso' => 'nullable|date',
            'fecha_ingreso_unidad' => 'nullable|date|after_or_equal:fecha_ingreso',
            'fecha_baja' => 'nullable|date',

            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'comprobante_estudios' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $validated['unidad_id'] = $this->normalizarUnidadParaActor($validated['unidad_id'] ?? null);

        try {
            if (!empty($validated['patrulla_id'])) {
                if (!$this->patrullaPerteneceAUnidad($validated['patrulla_id'], $validated['unidad_id'])) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'La patrulla seleccionada no pertenece a la unidad permitida o no está activa.'])
                        ->withInput();
                }

                $ocupada = Personal::query()
                    ->whereNull('deleted_at')
                    ->where('estatus', 'ACTIVO')
                    ->where('id', '!=', $personal->id)
                    ->where('patrulla_id', $validated['patrulla_id'])
                    ->exists();

                if ($ocupada) {
                    return redirect()->back()
                        ->withErrors(['patrulla_id' => 'Esa patrulla ya está asignada a otro elemento ACTIVO.'])
                        ->withInput();
                }
            }

            if (!empty($validated['user_id'])) {
                if (!$this->usuarioPerteneceAUnidadPermitida($validated['user_id'], $validated['unidad_id'], $personal->id)) {
                    return redirect()->back()
                        ->withErrors(['user_id' => 'El usuario seleccionado no pertenece a la unidad permitida o ya está asignado a otro registro de personal.'])
                        ->withInput();
                }
            }

            $fotoSubida = $request->file('foto');
            $comprobanteEstudios = $request->file('comprobante_estudios');
            unset($validated['foto']);
            unset($validated['comprobante_estudios']);

            if (($validated['alergias_estado'] ?? null) !== 'SI') {
                $validated['alergias'] = null;
            }

            $personal->update($validated);

            if ($comprobanteEstudios) {
                $this->guardarComprobanteEstudios($personal, $comprobanteEstudios);
            }

            if ($fotoSubida) {
                $rutaFoto = $this->guardarFotoPersonalPrivada($fotoSubida);

                $personal->update([
                    'foto' => $rutaFoto,
                ]);

                if (method_exists($personal, 'fotos')) {
                    $personal->fotos()->create([
                        'ruta' => $rutaFoto,
                        'nombre_original' => $fotoSubida->getClientOriginalName(),
                        'mime_type' => $fotoSubida->getClientMimeType(),
                        'tamano' => $fotoSubida->getSize(),
                    ]);
                }
            }

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal actualizado correctamente.');
        } catch (Throwable $e) {
            Log::error('Error al actualizar personal: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()
                ->back()
                ->withErrors('Hubo un error al actualizar el personal. Inténtelo nuevamente.')
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $personal = $this->buscarPersonalVisibleOFail($id);

        try {
            $personal->delete();

            return redirect()
                ->route('personal.index')
                ->with('success', 'Personal eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar personal: ' . $e->getMessage());

            return redirect()
                ->back()
                ->withErrors('Hubo un error al eliminar el personal. Inténtelo nuevamente.');
        }
    }

    private function guardarFotoPersonalPrivada($fotoSubida): string
    {
        return app(PersonalFotoStorage::class)->putUploadedFile($fotoSubida);
    }

    private function guardarComprobanteEstudios(Personal $personal, $archivo): void
    {
        $tipo = DocumentoTipo::query()->updateOrCreate(
            ['clave' => 'COMPROBANTE_ESTUDIOS'],
            [
                'nombre' => 'Comprobante de estudios',
                'requiere_vigencia' => false,
                'dias_vigencia' => null,
                'sensible' => true,
                'activo' => true,
            ]
        );

        $documento = PersonalDocumento::query()
            ->where('personal_id', $personal->id)
            ->where('documento_tipo_id', $tipo->id)
            ->first();
        $rutaAnterior = optional($documento)->archivo_path;
        $storage = app(DocumentoArchivoStorage::class);
        $ruta = $storage->putUploadedPdf($archivo, 'personals/' . $personal->id . '/documentos');

        try {
            PersonalDocumento::query()->updateOrCreate(
                [
                    'personal_id' => $personal->id,
                    'documento_tipo_id' => $tipo->id,
                ],
                [
                    'archivo_path' => $ruta,
                    'archivo_nombre' => $archivo->getClientOriginalName(),
                    'archivo_mime' => 'application/pdf',
                    'archivo_size' => $archivo->getSize(),
                    'hash_sha256' => hash_file('sha256', $archivo->getRealPath()),
                    'activo' => true,
                    'observaciones' => 'Comprobante del último grado de estudios registrado.',
                ]
            );
        } catch (Throwable $e) {
            $storage->delete($ruta);
            throw $e;
        }

        if ($rutaAnterior && $rutaAnterior !== $ruta) {
            $storage->delete($rutaAnterior);
        }
    }
}
