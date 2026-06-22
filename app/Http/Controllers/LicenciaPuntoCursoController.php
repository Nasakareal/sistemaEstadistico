<?php

namespace App\Http\Controllers;

use App\Models\LicenciaPuntoCuenta;
use App\Models\LicenciaPuntoCurso;
use App\Models\LicenciaPuntoCursoMaterial;
use App\Models\LicenciaPuntoCursoParticipante;
use App\Models\LicenciaPuntoMovimiento;
use App\Models\User;
use App\Services\BigBlueButtonService;
use App\Services\FomentoCulturaVialDetalleManager;
use App\Services\LicenciaPuntosService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class LicenciaPuntoCursoController extends Controller
{
    public function index(Request $request)
    {
        $query = LicenciaPuntoCurso::query()
            ->with(['instructor', 'unidad'])
            ->withCount([
                'participantes',
                'participantes as participantes_acreditados_count' => function ($q) {
                    $q->where('estado', LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO);
                },
            ])
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id');

        $this->scopeCursosParaUsuario($query, $request->user());

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->query('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('lugar', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->query('estado'));
        }

        $cursos = $query->paginate(20)->appends($request->query());

        $statsQuery = LicenciaPuntoCurso::query();
        $this->scopeCursosParaUsuario($statsQuery, $request->user());

        $participantStatsQuery = LicenciaPuntoCursoParticipante::query()
            ->whereHas('curso', function ($q) use ($request) {
                $this->scopeCursosParaUsuario($q, $request->user());
            });

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'programados' => (clone $statsQuery)->where('estado', LicenciaPuntoCurso::ESTADO_PROGRAMADO)->count(),
            'cerrados' => (clone $statsQuery)->where('estado', LicenciaPuntoCurso::ESTADO_CERRADO)->count(),
            'acreditados' => (clone $participantStatsQuery)
                ->where('estado', LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO)
                ->count(),
        ];

        $puedeCrearCursos = $this->puedeGestionarCursos($request->user());

        return view('licencias_puntos.cursos.index', compact('cursos', 'stats', 'puedeCrearCursos'));
    }

    public function create(Request $request)
    {
        $this->autorizarCrearCurso($request);

        $instructores = $this->instructoresDisponibles($request->user());

        return view('licencias_puntos.cursos.create', compact('instructores'));
    }

    public function store(Request $request)
    {
        $this->autorizarCrearCurso($request);

        $validated = $this->validateCurso($request);
        $actor = $request->user();
        $instructorId = $this->instructorIdDesdeRequest($request, $validated);
        $instructor = $instructorId ? User::find($instructorId) : $actor;
        $modalidad = $this->modalidadData($request, $validated);

        $curso = LicenciaPuntoCurso::create([
            'folio' => $this->generarFolio(),
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'lugar' => $validated['lugar'] ?? null,
            'instructor_id' => $instructor ? $instructor->id : null,
            'unidad_id' => $instructor ? $instructor->unidad_id : ($actor ? $actor->unidad_id : null),
            'fecha_inicio' => $validated['fecha_inicio'] ?? null,
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'horas_totales' => LicenciaPuntoCurso::HORAS_REQUERIDAS,
            'puntos_recuperacion' => LicenciaPuntoCuenta::SALDO_MAXIMO,
            'clase_en_vivo' => $modalidad['clase_en_vivo'],
            'materiales_pdf' => $modalidad['materiales_pdf'],
            'examen_habilitado' => $modalidad['examen_habilitado'],
            'calificacion_por_instructor' => $modalidad['calificacion_por_instructor'],
            'calificacion_minima' => $modalidad['calificacion_minima'],
            'cupo' => $validated['cupo'] ?? null,
            'estado' => LicenciaPuntoCurso::ESTADO_PROGRAMADO,
            'observaciones' => $validated['observaciones'] ?? null,
            'bbb_record' => $modalidad['bbb_record'],
            'bbb_mute_on_start' => $modalidad['bbb_mute_on_start'],
            'bbb_lock_viewers_microphone' => $modalidad['bbb_lock_viewers_microphone'],
            'bbb_anyone_can_talk' => $modalidad['bbb_anyone_can_talk'],
            'created_by' => $actor ? $actor->id : null,
            'updated_by' => $actor ? $actor->id : null,
        ]);

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', 'Curso de recuperacion creado correctamente.');
    }

    public function show(Request $request, LicenciaPuntoCurso $curso)
    {
        $this->autorizarVerCurso($request, $curso);

        $curso->load(['instructor', 'unidad', 'materiales']);

        $participantes = $curso->participantes()
            ->with(['cuenta', 'movimiento', 'conductor', 'calificador', 'curso'])
            ->orderBy('titular_nombre')
            ->paginate(50);

        $stats = [
            'total' => $curso->participantes()->count(),
            'horas_completas' => $curso->participantes()
                ->where('asistencia_horas', '>=', $curso->horas_totales)
                ->count(),
            'acreditados' => $curso->participantes()
                ->where('estado', LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO)
                ->count(),
            'puntos' => (int) $curso->participantes()->sum('puntos_acreditados'),
        ];

        $bbbDisponible = app(BigBlueButtonService::class)->enabled();
        $puedeGestionar = $this->puedeGestionarCurso($request->user(), $curso);

        return view('licencias_puntos.cursos.show', compact('curso', 'participantes', 'stats', 'bbbDisponible', 'puedeGestionar'));
    }

    public function edit(Request $request, LicenciaPuntoCurso $curso)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);

        $instructores = $this->instructoresDisponibles($request->user());

        return view('licencias_puntos.cursos.edit', compact('curso', 'instructores'));
    }

    public function update(Request $request, LicenciaPuntoCurso $curso)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);

        $validated = $this->validateCurso($request);
        $actor = $request->user();
        $instructorId = $this->instructorIdDesdeRequest($request, $validated);
        $instructor = $instructorId ? User::find($instructorId) : $curso->instructor;
        $modalidad = $this->modalidadData($request, $validated);

        $curso->update([
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'lugar' => $validated['lugar'] ?? null,
            'instructor_id' => $instructor ? $instructor->id : $curso->instructor_id,
            'unidad_id' => $instructor ? $instructor->unidad_id : $curso->unidad_id,
            'fecha_inicio' => $validated['fecha_inicio'] ?? null,
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'clase_en_vivo' => $modalidad['clase_en_vivo'],
            'materiales_pdf' => $modalidad['materiales_pdf'],
            'examen_habilitado' => $modalidad['examen_habilitado'],
            'calificacion_por_instructor' => $modalidad['calificacion_por_instructor'],
            'calificacion_minima' => $modalidad['calificacion_minima'],
            'cupo' => $validated['cupo'] ?? null,
            'estado' => $validated['estado'] ?? $curso->estado,
            'observaciones' => $validated['observaciones'] ?? null,
            'bbb_record' => $modalidad['bbb_record'],
            'bbb_mute_on_start' => $modalidad['bbb_mute_on_start'],
            'bbb_lock_viewers_microphone' => $modalidad['bbb_lock_viewers_microphone'],
            'bbb_anyone_can_talk' => $modalidad['bbb_anyone_can_talk'],
            'updated_by' => $actor ? $actor->id : null,
        ]);

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', 'Curso actualizado correctamente.');
    }

    public function storeParticipante(Request $request, LicenciaPuntoCurso $curso, LicenciaPuntosService $service)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);

        if ($curso->cupo && $curso->participantes()->count() >= (int) $curso->cupo) {
            throw ValidationException::withMessages([
                'cupo' => 'El curso ya alcanzo el cupo capturado.',
            ]);
        }

        $validated = $request->validate([
            'numero_licencia' => ['required', 'string', 'max:80'],
            'titular_nombre' => ['nullable', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'asistencia_horas' => ['nullable', 'numeric', 'min:0', 'max:' . $curso->horas_totales],
            'observaciones' => ['nullable', 'string'],
        ]);

        $numeroLicencia = $service->normalizarLicencia($validated['numero_licencia']);

        if ($curso->participantes()->where('numero_licencia', $numeroLicencia)->exists()) {
            throw ValidationException::withMessages([
                'numero_licencia' => 'Esta licencia ya esta inscrita en el curso.',
            ]);
        }

        $cuenta = LicenciaPuntoCuenta::where('numero_licencia', $numeroLicencia)->first();
        $actorId = $request->user() ? $request->user()->id : null;
        $titular = $this->normalizarTexto($validated['titular_nombre'] ?? null)
            ?: ($cuenta ? $cuenta->titular_nombre : null);

        if (!$titular) {
            throw ValidationException::withMessages([
                'titular_nombre' => 'Debes capturar el nombre si la licencia aun no tiene cuenta de puntos.',
            ]);
        }

        LicenciaPuntoCursoParticipante::create([
            'curso_id' => $curso->id,
            'cuenta_id' => $cuenta ? $cuenta->id : null,
            'conductor_id' => $cuenta ? $cuenta->conductor_id : null,
            'numero_licencia' => $numeroLicencia,
            'titular_nombre' => $titular,
            'curp' => $this->normalizarTexto($validated['curp'] ?? null) ?: ($cuenta ? $cuenta->curp : null),
            'telefono' => $this->soloDigitosONull($validated['telefono'] ?? null) ?: ($cuenta ? $cuenta->telefono : null),
            'asistencia_horas' => (float) ($validated['asistencia_horas'] ?? 0),
            'estado' => LicenciaPuntoCursoParticipante::ESTADO_INSCRITO,
            'observaciones' => $validated['observaciones'] ?? null,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', 'Participante agregado al curso.');
    }

    public function updateParticipante(Request $request, LicenciaPuntoCurso $curso, LicenciaPuntoCursoParticipante $participante)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);
        $this->asegurarParticipanteDelCurso($curso, $participante);

        if ($participante->estado === LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO) {
            throw ValidationException::withMessages([
                'participante' => 'El participante ya fue acreditado y no puede modificarse.',
            ]);
        }

        $validated = $request->validate([
            'asistencia_horas' => ['required', 'numeric', 'min:0', 'max:' . $curso->horas_totales],
            'observaciones' => ['nullable', 'string'],
        ]);

        $participante->update([
            'asistencia_horas' => (float) $validated['asistencia_horas'],
            'estado' => LicenciaPuntoCursoParticipante::ESTADO_INSCRITO,
            'observaciones' => $validated['observaciones'] ?? null,
            'updated_by' => $request->user() ? $request->user()->id : null,
        ]);

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', 'Asistencia actualizada.');
    }

    public function destroyParticipante(Request $request, LicenciaPuntoCurso $curso, LicenciaPuntoCursoParticipante $participante)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);
        $this->asegurarParticipanteDelCurso($curso, $participante);

        if ($participante->estado === LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO) {
            throw ValidationException::withMessages([
                'participante' => 'No se puede eliminar un participante acreditado.',
            ]);
        }

        $participante->delete();

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', 'Participante retirado del curso.');
    }

    public function acreditarParticipante(
        Request $request,
        LicenciaPuntoCurso $curso,
        LicenciaPuntoCursoParticipante $participante,
        LicenciaPuntosService $service
    ) {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);
        $this->asegurarParticipanteDelCurso($curso, $participante);

        $resultado = $this->aplicarAcreditacion($curso, $participante, $service, $request->user(), false);

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', $resultado['mensaje']);
    }

    public function cerrar(Request $request, LicenciaPuntoCurso $curso, LicenciaPuntosService $service)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);

        $acreditados = 0;
        $sinAcreditar = 0;
        $puntos = 0;

        $participantes = $curso->participantes()->with('cuenta')->orderBy('id')->get();

        foreach ($participantes as $participante) {
            if ((float) $participante->asistencia_horas < (float) $curso->horas_totales) {
                $participante->update([
                    'estado' => LicenciaPuntoCursoParticipante::ESTADO_NO_ACREDITADO,
                    'updated_by' => $request->user() ? $request->user()->id : null,
                ]);
                $sinAcreditar++;
                continue;
            }

            try {
                $resultado = $this->aplicarAcreditacion($curso, $participante, $service, $request->user(), true);
                $acreditados++;
                $puntos += (int) $resultado['puntos'];
            } catch (ValidationException $e) {
                $this->marcarNoAcreditado($participante, $this->mensajeValidacion($e), $request->user());
                $sinAcreditar++;
            }
        }

        $curso->update([
            'estado' => LicenciaPuntoCurso::ESTADO_CERRADO,
            'closed_at' => Carbon::now('America/Mexico_City'),
            'updated_by' => $request->user() ? $request->user()->id : null,
        ]);

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', "Curso cerrado. Acreditados: {$acreditados}. No acreditados: {$sinAcreditar}. Puntos recuperados: {$puntos}.");
    }

    public function iniciarClaseEnVivo(Request $request, LicenciaPuntoCurso $curso, BigBlueButtonService $bbb)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);

        abort_unless($curso->clase_en_vivo, 404);

        try {
            $curso = $bbb->createMeeting($curso);

            if ($curso->estado === LicenciaPuntoCurso::ESTADO_PROGRAMADO) {
                $curso->update([
                    'estado' => LicenciaPuntoCurso::ESTADO_EN_CURSO,
                    'updated_by' => $request->user() ? $request->user()->id : null,
                ]);
            }

            return redirect()->away($bbb->moderatorJoinUrl($curso->fresh(), $request->user()));
        } catch (RuntimeException $e) {
            return back()->withErrors(['bbb' => $e->getMessage()]);
        }
    }

    public function entrarClaseEnVivo(
        Request $request,
        LicenciaPuntoCurso $curso,
        LicenciaPuntoCursoParticipante $participante,
        BigBlueButtonService $bbb
    ) {
        $this->asegurarParticipanteDelCurso($curso, $participante);
        abort_unless($curso->clase_en_vivo, 404);

        try {
            return redirect()->away($bbb->attendeeJoinUrl($curso, $participante));
        } catch (RuntimeException $e) {
            abort(409, $e->getMessage());
        }
    }

    public function storeMaterial(Request $request, LicenciaPuntoCurso $curso)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);
        abort_unless($curso->materiales_pdf, 404);

        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'tipo' => ['required', Rule::in([
                LicenciaPuntoCursoMaterial::TIPO_PDF,
                LicenciaPuntoCursoMaterial::TIPO_LINK,
                LicenciaPuntoCursoMaterial::TIPO_TEXTO,
            ])],
            'archivo' => ['required_if:tipo,pdf', 'nullable', 'file', 'mimes:pdf', 'max:20480'],
            'url' => ['required_if:tipo,link', 'nullable', 'url', 'max:500'],
            'contenido' => ['required_if:tipo,texto', 'nullable', 'string'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $archivoPath = null;
        if (($validated['tipo'] ?? null) === LicenciaPuntoCursoMaterial::TIPO_PDF && $request->hasFile('archivo')) {
            $archivoPath = $request->file('archivo')->store('licencias-puntos/cursos/' . $curso->id, 'public');
        }

        LicenciaPuntoCursoMaterial::create([
            'curso_id' => $curso->id,
            'titulo' => $validated['titulo'],
            'tipo' => $validated['tipo'],
            'archivo_path' => $archivoPath,
            'url' => $validated['url'] ?? null,
            'contenido' => $validated['contenido'] ?? null,
            'orden' => (int) ($validated['orden'] ?? 0),
            'activo' => $request->boolean('activo', true),
            'created_by' => $request->user() ? $request->user()->id : null,
            'updated_by' => $request->user() ? $request->user()->id : null,
        ]);

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', 'Material agregado al curso.');
    }

    public function destroyMaterial(Request $request, LicenciaPuntoCurso $curso, LicenciaPuntoCursoMaterial $material)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);
        abort_unless((int) $material->curso_id === (int) $curso->id, 404);

        if ($material->archivo_path) {
            Storage::disk('public')->delete($material->archivo_path);
        }

        $material->delete();

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', 'Material retirado del curso.');
    }

    public function calificarParticipante(Request $request, LicenciaPuntoCurso $curso, LicenciaPuntoCursoParticipante $participante)
    {
        $this->autorizarGestionCurso($request, $curso);
        $this->asegurarCursoEditable($curso);
        $this->asegurarParticipanteDelCurso($curso, $participante);

        if ($participante->estado === LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO) {
            throw ValidationException::withMessages([
                'participante' => 'El participante ya fue acreditado y no puede recalificarse.',
            ]);
        }

        $validated = $request->validate([
            'calificacion' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $calificacion = $validated['calificacion'] ?? null;

        $participante->update([
            'calificacion' => $calificacion,
            'calificado_at' => is_null($calificacion) ? null : Carbon::now('America/Mexico_City'),
            'calificado_by' => is_null($calificacion) ? null : ($request->user() ? $request->user()->id : null),
            'updated_by' => $request->user() ? $request->user()->id : null,
        ]);

        return redirect()
            ->route('licencias_puntos.cursos.show', $curso)
            ->with('success', 'Calificacion guardada.');
    }

    private function aplicarAcreditacion(
        LicenciaPuntoCurso $curso,
        LicenciaPuntoCursoParticipante $participante,
        LicenciaPuntosService $service,
        ?User $actor,
        bool $desdeCierre
    ): array {
        $participante->loadMissing('cuenta');

        if ($participante->estado === LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO) {
            return [
                'puntos' => (int) $participante->puntos_acreditados,
                'mensaje' => 'El participante ya estaba acreditado.',
            ];
        }

        if ((float) $participante->asistencia_horas < (float) $curso->horas_totales) {
            throw ValidationException::withMessages([
                'asistencia_horas' => 'El participante aun no completa las 15 horas del curso.',
            ]);
        }

        if ($curso->requiere_calificacion) {
            if (is_null($participante->calificacion)) {
                throw ValidationException::withMessages([
                    'calificacion' => 'Falta que el instructor capture la calificacion del examen.',
                ]);
            }

            if ((int) $participante->calificacion < (int) $curso->calificacion_minima) {
                throw ValidationException::withMessages([
                    'calificacion' => 'El participante no alcanzo la calificacion minima del curso.',
                ]);
            }
        }

        $cuenta = $participante->cuenta;

        if (!$cuenta) {
            $cuenta = LicenciaPuntoCuenta::where('numero_licencia', $participante->numero_licencia)->first();

            if ($cuenta) {
                $participante->forceFill([
                    'cuenta_id' => $cuenta->id,
                    'conductor_id' => $cuenta->conductor_id,
                ])->save();
            }
        }

        if (!$cuenta) {
            throw ValidationException::withMessages([
                'numero_licencia' => 'No existe una cuenta de puntos para esta licencia.',
            ]);
        }

        $actorId = $actor ? $actor->id : null;
        $saldoAnterior = (int) $cuenta->saldo_actual;

        if ($saldoAnterior >= LicenciaPuntoCuenta::SALDO_MAXIMO) {
            $participante->update([
                'estado' => LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO,
                'puntos_acreditados' => 0,
                'acreditado_at' => Carbon::now('America/Mexico_City'),
                'updated_by' => $actorId,
            ]);

            return [
                'puntos' => 0,
                'mensaje' => 'Participante acreditado; la licencia ya tenia saldo completo.',
            ];
        }

        $movimientoAnteriorId = (int) $cuenta->movimientos()->max('id');

        $service->acreditarCapacitacion($cuenta, [
            'puntos' => (int) $curso->puntos_recuperacion,
            'fecha_movimiento' => Carbon::now('America/Mexico_City'),
            'referencia' => $curso->folio,
            'descripcion' => sprintf('Curso de recuperacion de puntos "%s" (%s horas).', $curso->nombre, $curso->horas_totales),
            'validado_por' => 'Unidad de Fomento a la Cultura Vial',
            'curso_id' => $curso->id,
            'curso_folio' => $curso->folio,
            'curso_nombre' => $curso->nombre,
            'participante_id' => $participante->id,
            'horas_curso' => (float) $curso->horas_totales,
            'asistencia_horas' => (float) $participante->asistencia_horas,
            'calificacion' => $participante->calificacion,
            'calificacion_minima' => $curso->calificacion_minima,
            'instructor_id' => $curso->instructor_id,
        ], $actor);

        $movimiento = LicenciaPuntoMovimiento::query()
            ->where('cuenta_id', $cuenta->id)
            ->where('id', '>', $movimientoAnteriorId)
            ->where('tipo', 'recuperacion_capacitacion')
            ->orderByDesc('id')
            ->first();

        $puntos = $movimiento ? (int) $movimiento->puntos : max(0, LicenciaPuntoCuenta::SALDO_MAXIMO - $saldoAnterior);

        $participante->update([
            'movimiento_id' => $movimiento ? $movimiento->id : null,
            'estado' => LicenciaPuntoCursoParticipante::ESTADO_ACREDITADO,
            'puntos_acreditados' => $puntos,
            'acreditado_at' => Carbon::now('America/Mexico_City'),
            'updated_by' => $actorId,
        ]);

        return [
            'puntos' => $puntos,
            'mensaje' => $desdeCierre
                ? 'Participante acreditado durante el cierre del curso.'
                : "Participante acreditado. Puntos recuperados: {$puntos}.",
        ];
    }

    private function validateCurso(Request $request): array
    {
        $estados = [
            LicenciaPuntoCurso::ESTADO_PROGRAMADO,
            LicenciaPuntoCurso::ESTADO_EN_CURSO,
            LicenciaPuntoCurso::ESTADO_CANCELADO,
        ];

        return $request->validate([
            'nombre' => ['required', 'string', 'max:180'],
            'descripcion' => ['nullable', 'string'],
            'lugar' => ['nullable', 'string', 'max:180'],
            'instructor_id' => ['nullable', 'integer', 'exists:users,id'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'cupo' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'estado' => ['nullable', Rule::in($estados)],
            'clase_en_vivo' => ['nullable', 'boolean'],
            'materiales_pdf' => ['nullable', 'boolean'],
            'examen_habilitado' => ['nullable', 'boolean'],
            'calificacion_por_instructor' => ['nullable', 'boolean'],
            'calificacion_minima' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bbb_record' => ['nullable', 'boolean'],
            'bbb_mute_on_start' => ['nullable', 'boolean'],
            'bbb_lock_viewers_microphone' => ['nullable', 'boolean'],
            'bbb_anyone_can_talk' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string'],
        ]);
    }

    private function modalidadData(Request $request, array $validated): array
    {
        $examenHabilitado = $request->boolean('examen_habilitado');

        return [
            'clase_en_vivo' => $request->boolean('clase_en_vivo'),
            'materiales_pdf' => $request->boolean('materiales_pdf'),
            'examen_habilitado' => $examenHabilitado,
            'calificacion_por_instructor' => $examenHabilitado && $request->boolean('calificacion_por_instructor', true),
            'calificacion_minima' => (int) ($validated['calificacion_minima'] ?? 80),
            'bbb_record' => $request->boolean('bbb_record', true),
            'bbb_mute_on_start' => $request->boolean('bbb_mute_on_start', true),
            'bbb_lock_viewers_microphone' => $request->boolean('bbb_lock_viewers_microphone'),
            'bbb_anyone_can_talk' => $request->boolean('bbb_anyone_can_talk'),
        ];
    }

    private function instructorIdDesdeRequest(Request $request, array $validated): ?int
    {
        if ($request->user() && $request->user()->hasRole('Superadmin')) {
            return !empty($validated['instructor_id']) ? (int) $validated['instructor_id'] : $request->user()->id;
        }

        return $request->user() ? $request->user()->id : null;
    }

    private function instructoresDisponibles(?User $actor)
    {
        if (!$actor || !$actor->hasRole('Superadmin')) {
            return $actor ? collect([$actor]) : collect();
        }

        return User::query()
            ->with('unidad')
            ->where(function ($q) {
                $q->whereHas('unidad', function ($unidad) {
                    $unidad->whereIn('slug', ['fomento-cultura-vial', 'cultura-vial', 'fomento'])
                        ->orWhere(function ($nombre) {
                            $nombre->where('nombre', 'like', '%Fomento%')
                                ->where('nombre', 'like', '%Cultura Vial%');
                        });
                })->orWhereHas('roles', function ($roles) {
                    $roles->whereIn('name', ['Instructor', 'Instructor Fomento', 'Instructor de Fomento']);
                });
            })
            ->orderBy('name')
            ->get();
    }

    private function autorizarCrearCurso(Request $request): void
    {
        abort_unless($this->puedeGestionarCursos($request->user()), 403);
    }

    private function autorizarVerCurso(Request $request, LicenciaPuntoCurso $curso): void
    {
        abort_unless($this->puedeVerCursos($request->user()), 403);

        if ($this->usuarioVeTodoCurso($request->user())) {
            return;
        }

        abort_unless(
            $this->isFomentoCulturaVialUser($request->user())
            && (
                (int) $curso->unidad_id === (int) $request->user()->unidad_id
                || (int) $curso->instructor_id === (int) $request->user()->id
            ),
            403
        );
    }

    private function autorizarGestionCurso(Request $request, LicenciaPuntoCurso $curso): void
    {
        abort_unless($this->puedeGestionarCurso($request->user(), $curso), 403);
    }

    private function puedeVerCursos(?User $user): bool
    {
        return $user
            && (
                $user->hasRole('Superadmin')
                || $this->isFomentoCulturaVialUser($user)
                || $this->isSeguridadVialUser($user)
            );
    }

    private function puedeGestionarCursos(?User $user): bool
    {
        return $user
            && (
                $user->hasRole('Superadmin')
                || $user->can('acreditar capacitacion puntos licencias')
            );
    }

    private function puedeGestionarCurso(?User $user, LicenciaPuntoCurso $curso): bool
    {
        if (!$this->puedeGestionarCursos($user)) {
            return false;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        return (int) $curso->instructor_id === (int) $user->id;
    }

    private function scopeCursosParaUsuario($query, ?User $user): void
    {
        if (!$this->puedeVerCursos($user)) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($this->usuarioVeTodoCurso($user)) {
            return;
        }

        $query->where(function ($q) use ($user) {
            $q->where('unidad_id', $user->unidad_id)
                ->orWhere('instructor_id', $user->id);
        });
    }

    private function usuarioVeTodoCurso(?User $user): bool
    {
        return $user && ($user->hasRole('Superadmin') || $this->isSeguridadVialUser($user));
    }

    private function isSeguridadVialUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return (int) ($user->unidad_id ?? 0) === 3
            || optional($user->unidad)->slug === 'seguridad-vial';
    }

    private function isFomentoCulturaVialUser(?User $user): bool
    {
        return app(FomentoCulturaVialDetalleManager::class)->usuarioEsFomento($user);
    }

    private function asegurarCursoEditable(LicenciaPuntoCurso $curso): void
    {
        if (!$curso->puede_modificarse) {
            throw ValidationException::withMessages([
                'curso' => 'Este curso ya no puede modificarse.',
            ]);
        }
    }

    private function asegurarParticipanteDelCurso(LicenciaPuntoCurso $curso, LicenciaPuntoCursoParticipante $participante): void
    {
        abort_unless((int) $participante->curso_id === (int) $curso->id, 404);
    }

    private function generarFolio(): string
    {
        $prefix = 'CUR-PTS-' . Carbon::now('America/Mexico_City')->format('Y');
        $next = LicenciaPuntoCurso::where('folio', 'like', $prefix . '-%')->count() + 1;

        do {
            $folio = sprintf('%s-%04d', $prefix, $next);
            $next++;
        } while (LicenciaPuntoCurso::where('folio', $folio)->exists());

        return $folio;
    }

    private function marcarNoAcreditado(LicenciaPuntoCursoParticipante $participante, string $motivo, ?User $actor): void
    {
        $observaciones = trim((string) $participante->observaciones);
        $observaciones = trim($observaciones . ($observaciones ? "\n" : '') . $motivo);

        $participante->update([
            'estado' => LicenciaPuntoCursoParticipante::ESTADO_NO_ACREDITADO,
            'observaciones' => $observaciones,
            'updated_by' => $actor ? $actor->id : null,
        ]);
    }

    private function mensajeValidacion(ValidationException $e): string
    {
        return collect($e->errors())->flatten()->first() ?: 'No fue posible acreditar al participante.';
    }

    private function normalizarTexto($value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? mb_strtoupper($value, 'UTF-8') : null;
    }

    private function soloDigitosONull($value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }
}
