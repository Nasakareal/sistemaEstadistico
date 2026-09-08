<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaleaEncuesta;
use App\Models\CaleaEncuestaAsignacion;
use App\Models\CaleaEncuestaIntento;
use App\Models\CaleaEncuestaOpcion;
use App\Models\CaleaEncuestaPregunta;
use App\Models\CaleaEncuestaRespuesta;
use App\Models\Unidad;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CaleaEncuestaController extends Controller
{
    public function activas(Request $request)
    {
        $user = $request->user();
        $now = now();
        $encuestas = CaleaEncuesta::query()
            ->where('activa', true)
            ->where(fn ($q) => $q->whereNull('disponible_desde')->orWhere('disponible_desde', '<=', $now))
            ->where(fn ($q) => $q->whereNull('disponible_hasta')->orWhere('disponible_hasta', '>=', $now))
            ->whereHas('asignaciones', function ($q) use ($user) {
                $q->where('todos', true)->orWhere('user_id', $user->id);
                if ($user->unidad_id) $q->orWhere('unidad_id', $user->unidad_id);
            })
            ->withCount('preguntas')->orderByDesc('id')->get();

        return response()->json(['data' => $encuestas->map(fn ($e) => $this->resumenPara($e, $user))]);
    }

    public function administrar(Request $request)
    {
        $actor = $this->admin($request);
        $query = CaleaEncuesta::withCount(['preguntas', 'intentos'])->with('creador:id,name')->orderByDesc('id');
        $this->scope($query, $actor);
        return response()->json(['data' => $query->get()->map(fn ($e) => $this->resumen($e))]);
    }

    public function catalogos(Request $request)
    {
        $actor = $this->admin($request);
        $users = User::query()->select('id', 'name', 'email', 'unidad_id')->where('estado', 'Activo');
        $units = Unidad::query()->select('id', 'nombre');
        if (!$this->global($actor)) {
            $users->where('unidad_id', $actor->unidad_id);
            $units->where('id', $actor->unidad_id);
        }
        return response()->json(['data' => ['usuarios' => $users->orderBy('name')->get(), 'unidades' => $units->orderBy('nombre')->get(), 'alcance_global' => $this->global($actor)]]);
    }

    public function store(Request $request)
    {
        $actor = $this->admin($request);
        $data = $request->validate([
            'titulo' => 'required|string|max:180', 'descripcion' => 'nullable|string|max:3000',
            'duracion_minutos' => 'required|integer|min:1|max:180', 'calificacion_minima' => 'required|integer|min:0|max:100',
            'activa' => 'sometimes|boolean', 'permite_externos' => 'sometimes|boolean',
            'disponible_desde' => 'nullable|date', 'disponible_hasta' => 'nullable|date|after:disponible_desde',
            'asignacion.tipo' => ['required', Rule::in(['todos', 'unidad', 'usuarios'])],
            'asignacion.unidad_id' => 'nullable|integer|exists:unidades,id', 'asignacion.usuarios' => 'nullable|array|max:500',
            'asignacion.usuarios.*' => 'integer|exists:users,id',
            'preguntas' => 'required|array|min:1|max:100', 'preguntas.*.texto' => 'required|string|max:2000',
            'preguntas.*.tipo' => ['required', Rule::in(['opcion_unica', 'texto'])], 'preguntas.*.puntos' => 'nullable|integer|min:0|max:100',
            'preguntas.*.opciones' => 'required_if:preguntas.*.tipo,opcion_unica|array',
            'preguntas.*.opciones.*.texto' => 'required|string|max:1000', 'preguntas.*.opciones.*.es_correcta' => 'sometimes|boolean',
        ]);

        $this->validarAsignacion($actor, $data['asignacion']);
        foreach ($data['preguntas'] as $index => $pregunta) {
            if ($pregunta['tipo'] === 'opcion_unica') {
                $options = $pregunta['opciones'] ?? [];
                abort_unless(count($options) >= 2, 422, 'La pregunta '.($index + 1).' necesita al menos dos opciones.');
                abort_unless(collect($options)->where('es_correcta', true)->count() === 1, 422, 'La pregunta '.($index + 1).' debe tener exactamente una respuesta correcta.');
            }
        }

        $survey = DB::transaction(function () use ($data, $actor) {
            $survey = CaleaEncuesta::create([
                'titulo' => trim($data['titulo']), 'descripcion' => $data['descripcion'] ?? null,
                'duracion_minutos' => $data['duracion_minutos'], 'calificacion_minima' => $data['calificacion_minima'],
                'activa' => $data['activa'] ?? true, 'permite_externos' => $data['permite_externos'] ?? false,
                'codigo_publico' => !empty($data['permite_externos']) ? strtoupper(Str::random(10)) : null,
                'unidad_id' => $this->global($actor) ? ($data['asignacion']['unidad_id'] ?? $actor->unidad_id) : $actor->unidad_id,
                'creada_por' => $actor->id, 'disponible_desde' => $data['disponible_desde'] ?? null, 'disponible_hasta' => $data['disponible_hasta'] ?? null,
            ]);
            foreach ($data['preguntas'] as $i => $row) {
                $q = CaleaEncuestaPregunta::create(['encuesta_id' => $survey->id, 'texto' => trim($row['texto']), 'tipo' => $row['tipo'], 'orden' => $i + 1, 'puntos' => $row['puntos'] ?? 1, 'obligatoria' => true]);
                foreach (($row['opciones'] ?? []) as $j => $option) CaleaEncuestaOpcion::create(['pregunta_id' => $q->id, 'texto' => trim($option['texto']), 'orden' => $j + 1, 'es_correcta' => $option['es_correcta'] ?? false]);
            }
            $this->crearAsignaciones($survey, $actor, $data['asignacion']);
            return $survey;
        });
        return response()->json(['message' => 'Encuesta creada y asignada.', 'data' => $this->resumen($survey->loadCount('preguntas'))], 201);
    }

    public function iniciar(Request $request, CaleaEncuesta $encuesta)
    {
        $user = $request->user();
        abort_unless($this->asignada($encuesta, $user), 403, 'Esta encuesta no está asignada a tu cuenta.');
        return $this->nuevoIntento($request, $encuesta, $user);
    }

    public function publicShow(string $codigo)
    {
        $survey = CaleaEncuesta::where('codigo_publico', strtoupper($codigo))->where('activa', true)->where('permite_externos', true)->withCount('preguntas')->firstOrFail();
        $this->vigente($survey);
        return response()->json(['data' => $this->resumen($survey)]);
    }

    public function publicStart(Request $request, string $codigo)
    {
        $survey = CaleaEncuesta::where('codigo_publico', strtoupper($codigo))->where('activa', true)->where('permite_externos', true)->firstOrFail();
        $data = $request->validate(['nombre' => 'required|string|max:180', 'email' => 'nullable|email|max:180', 'telefono' => 'required|string|max:30', 'identificador' => 'required|string|max:80', 'unidad_id' => 'nullable|integer|exists:unidades,id']);
        return $this->nuevoIntento($request, $survey, null, $data);
    }

    public function intento(Request $request, string $uuid)
    {
        return response()->json(['data' => $this->intentoPayload($this->intentoAutorizado($request, $uuid))]);
    }

    public function guardarRespuesta(Request $request, string $uuid)
    {
        $attempt = $this->intentoAutorizado($request, $uuid);
        $this->enCurso($attempt);
        $data = $request->validate(['pregunta_id' => 'required|integer', 'opcion_id' => 'nullable|integer', 'respuesta_texto' => 'nullable|string|max:5000']);
        $question = CaleaEncuestaPregunta::where('encuesta_id', $attempt->encuesta_id)->with('opciones')->findOrFail($data['pregunta_id']);
        $option = null;
        if ($question->tipo === 'opcion_unica') {
            $option = $question->opciones->firstWhere('id', (int) ($data['opcion_id'] ?? 0));
            abort_unless($option, 422, 'Selecciona una opción válida.');
        }
        CaleaEncuestaRespuesta::updateOrCreate(['intento_id' => $attempt->id, 'pregunta_id' => $question->id], ['opcion_id' => $option ? $option->id : null, 'respuesta_texto' => $question->tipo === 'texto' ? trim($data['respuesta_texto'] ?? '') : null, 'correcta' => $option ? $option->es_correcta : null]);
        return response()->json(['message' => 'Respuesta guardada.', 'server_now' => now()->toIso8601String(), 'expira_at' => $attempt->expira_at->toIso8601String()]);
    }

    public function finalizar(Request $request, string $uuid)
    {
        $attempt = $this->intentoAutorizado($request, $uuid);
        $this->enCurso($attempt);
        $attempt->load(['encuesta.preguntas', 'respuestas']);
        $required = $attempt->encuesta->preguntas->where('obligatoria', true)->pluck('id');
        abort_unless($required->diff($attempt->respuestas->pluck('pregunta_id'))->isEmpty(), 422, 'Debes responder todas las preguntas antes de terminar.');
        $graded = $attempt->respuestas->whereNotNull('correcta');
        $hits = $graded->where('correcta', true)->count();
        $score = $graded->count() ? round($hits * 100 / $graded->count(), 2) : 100;
        $attempt->update(['estado' => 'finalizado', 'finalizado_at' => now(), 'aciertos' => $hits, 'total_preguntas' => $attempt->encuesta->preguntas->count(), 'calificacion' => $score, 'aprobado' => $score >= $attempt->encuesta->calificacion_minima]);
        return response()->json(['message' => 'Encuesta finalizada.', 'data' => $this->intentoPayload($attempt->fresh())]);
    }

    public function cancelar(Request $request, string $uuid)
    {
        $attempt = $this->intentoAutorizado($request, $uuid);
        if ($attempt->estado === 'en_curso') $attempt->update(['estado' => 'cancelado', 'finalizado_at' => now()]);
        return response()->json(['message' => 'Intento cancelado. Un administrador deberá autorizar otro intento.']);
    }

    public function reautorizar(Request $request, CaleaEncuesta $encuesta, User $user)
    {
        $actor = $this->admin($request);
        abort_unless($this->global($actor) || (int) $encuesta->unidad_id === (int) $actor->unidad_id, 403, 'No puedes administrar encuestas de otra unidad.');
        $this->assertUserScope($actor, $user);
        abort_unless($this->asignada($encuesta, $user), 422, 'La encuesta no está asignada a este usuario.');
        $data = $request->validate(['motivo' => 'nullable|string|max:1000']);
        DB::table('calea_encuesta_reautorizaciones')->insert(['encuesta_id' => $encuesta->id, 'user_id' => $user->id, 'autorizada_por' => $actor->id, 'motivo' => $data['motivo'] ?? null, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['message' => 'Nuevo intento autorizado.']);
    }

    public function resultados(Request $request, CaleaEncuesta $encuesta)
    {
        $actor = $this->admin($request);
        abort_unless($this->global($actor) || (int) $encuesta->unidad_id === (int) $actor->unidad_id, 403, 'No puedes consultar encuestas de otra unidad.');
        $rows = CaleaEncuestaIntento::query()
            ->where('encuesta_id', $encuesta->id)
            ->leftJoin('users', 'users.id', '=', 'calea_encuesta_intentos.user_id')
            ->select('calea_encuesta_intentos.*', 'users.name as usuario_nombre')
            ->orderByDesc('calea_encuesta_intentos.id')->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'user_id' => $row->user_id,
                'participante' => $row->usuario_nombre ?: $row->participante_nombre ?: 'Participante externo',
                'identificador' => $row->participante_identificador,
                'estado' => $row->estado,
                'numero_intento' => $row->numero_intento,
                'iniciado_at' => optional($row->iniciado_at)->toIso8601String(),
                'finalizado_at' => optional($row->finalizado_at)->toIso8601String(),
                'calificacion' => $row->calificacion,
                'aprobado' => $row->aprobado,
            ]);
        return response()->json(['data' => $rows]);
    }

    private function nuevoIntento(Request $request, CaleaEncuesta $survey, ?User $user, array $guest = [])
    {
        $this->vigente($survey);
        return DB::transaction(function () use ($request, $survey, $user, $guest) {
            if ($user) {
                $previous = CaleaEncuestaIntento::where('encuesta_id', $survey->id)->where('user_id', $user->id)->lockForUpdate()->orderByDesc('id')->first();
                if ($previous && $previous->estado === 'en_curso' && $previous->expira_at->isFuture()) return response()->json(['data' => $this->intentoPayload($previous)]);
                if ($previous && $previous->estado === 'en_curso') $previous->update(['estado' => 'expirado', 'finalizado_at' => now()]);
                if ($previous) {
                    $permit = DB::table('calea_encuesta_reautorizaciones')->where('encuesta_id', $survey->id)->where('user_id', $user->id)->whereNull('consumida_at')->lockForUpdate()->first();
                    abort_unless($permit, 409, 'Ya utilizaste tu intento. Solicita una reautorización al administrador.');
                    DB::table('calea_encuesta_reautorizaciones')->where('id', $permit->id)->update(['consumida_at' => now(), 'updated_at' => now()]);
                }
            }
            $count = CaleaEncuestaIntento::where('encuesta_id', $survey->id)->when($user, fn ($q) => $q->where('user_id', $user->id), fn ($q) => $q->where('participante_identificador', $guest['identificador'] ?? ''))->count();
            $attempt = CaleaEncuestaIntento::create(['uuid' => (string) Str::uuid(), 'encuesta_id' => $survey->id, 'user_id' => $user ? $user->id : null, 'participante_nombre' => $guest['nombre'] ?? null, 'participante_email' => $guest['email'] ?? null, 'participante_telefono' => $guest['telefono'] ?? null, 'participante_identificador' => $guest['identificador'] ?? null, 'participante_unidad_id' => $guest['unidad_id'] ?? ($user ? $user->unidad_id : null), 'estado' => 'en_curso', 'numero_intento' => $count + 1, 'iniciado_at' => now(), 'expira_at' => now()->addMinutes($survey->duracion_minutos), 'ip_inicio' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 500, '')]);
            return response()->json(['data' => $this->intentoPayload($attempt)], 201);
        });
    }

    private function intentoPayload(CaleaEncuestaIntento $attempt): array
    {
        if ($attempt->estado === 'en_curso' && $attempt->expira_at->isPast()) { $attempt->update(['estado' => 'expirado', 'finalizado_at' => now()]); $attempt->refresh(); }
        $attempt->loadMissing('encuesta.preguntas.opciones', 'respuestas');
        return ['uuid' => $attempt->uuid, 'estado' => $attempt->estado, 'server_now' => now()->toIso8601String(), 'expira_at' => $attempt->expira_at->toIso8601String(), 'calificacion' => $attempt->calificacion, 'aprobado' => $attempt->aprobado, 'encuesta' => ['id' => $attempt->encuesta->id, 'titulo' => $attempt->encuesta->titulo, 'descripcion' => $attempt->encuesta->descripcion, 'duracion_minutos' => $attempt->encuesta->duracion_minutos, 'calificacion_minima' => $attempt->encuesta->calificacion_minima, 'preguntas' => $attempt->encuesta->preguntas->map(fn ($q) => ['id' => $q->id, 'texto' => $q->texto, 'tipo' => $q->tipo, 'orden' => $q->orden, 'obligatoria' => $q->obligatoria, 'opciones' => $q->opciones->map(fn ($o) => ['id' => $o->id, 'texto' => $o->texto, 'orden' => $o->orden])->values()])->values()], 'respuestas' => $attempt->respuestas->map(fn ($r) => ['pregunta_id' => $r->pregunta_id, 'opcion_id' => $r->opcion_id, 'respuesta_texto' => $r->respuesta_texto])->values()];
    }

    private function resumenPara($survey, User $user): array
    {
        $row = $this->resumen($survey);
        $attempt = CaleaEncuestaIntento::where('encuesta_id', $survey->id)->where('user_id', $user->id)->orderByDesc('id')->first();
        $permit = DB::table('calea_encuesta_reautorizaciones')->where('encuesta_id', $survey->id)->where('user_id', $user->id)->whereNull('consumida_at')->exists();
        $row['ultimo_intento'] = $attempt ? ['uuid' => $attempt->uuid, 'estado' => $attempt->estado, 'expira_at' => optional($attempt->expira_at)->toIso8601String(), 'calificacion' => $attempt->calificacion, 'aprobado' => $attempt->aprobado] : null;
        $row['puede_iniciar'] = !$attempt || $permit || ($attempt->estado === 'en_curso' && $attempt->expira_at->isFuture());
        return $row;
    }

    private function resumen($e): array { return ['id' => $e->id, 'titulo' => $e->titulo, 'descripcion' => $e->descripcion, 'duracion_minutos' => $e->duracion_minutos, 'calificacion_minima' => $e->calificacion_minima, 'activa' => $e->activa, 'permite_externos' => $e->permite_externos, 'codigo_publico' => $e->codigo_publico, 'unidad_id' => $e->unidad_id, 'disponible_desde' => optional($e->disponible_desde)->toIso8601String(), 'disponible_hasta' => optional($e->disponible_hasta)->toIso8601String(), 'preguntas_count' => $e->preguntas_count ?? null, 'intentos_count' => $e->intentos_count ?? null]; }
    private function vigente(CaleaEncuesta $e): void { abort_unless($e->activa, 409, 'La encuesta no está activa.'); abort_if($e->disponible_desde && $e->disponible_desde->isFuture(), 409, 'La encuesta aún no está disponible.'); abort_if($e->disponible_hasta && $e->disponible_hasta->isPast(), 409, 'La encuesta ya expiró.'); }
    private function asignada(CaleaEncuesta $e, User $u): bool { return $e->asignaciones()->where(fn ($q) => $q->where('todos', true)->orWhere('user_id', $u->id)->when($u->unidad_id, fn ($x) => $x->orWhere('unidad_id', $u->unidad_id)))->exists(); }
    private function intentoAutorizado(Request $r, string $uuid): CaleaEncuestaIntento { $a = CaleaEncuestaIntento::where('uuid', $uuid)->firstOrFail(); abort_if($a->user_id && (!$r->user() || $r->user()->id !== $a->user_id), 403); return $a; }
    private function enCurso(CaleaEncuestaIntento $a): void { if ($a->estado === 'en_curso' && $a->expira_at->isPast()) $a->update(['estado' => 'expirado', 'finalizado_at' => now()]); abort_unless($a->fresh()->estado === 'en_curso', 409, 'Este intento ya no está activo.'); }
    private function admin(Request $r): User { $u = $r->user(); abort_unless($u && ($u->isSuperadmin() || $u->hasRole('Administrador')), 403, 'Sólo administradores pueden gestionar encuestas.'); return $u; }
    private function global(User $u): bool { return $u->isSuperadmin() || ((int) $u->unidad_id === 3 && $u->hasRole('Administrador')); }
    private function scope($q, User $u): void { if (!$this->global($u)) $q->where('unidad_id', $u->unidad_id); }
    private function assertUserScope(User $a, User $u): void { abort_unless($this->global($a) || ((int) $a->unidad_id === (int) $u->unidad_id), 403, 'No puedes administrar usuarios de otra unidad.'); }
    private function validarAsignacion(User $a, array $x): void { if (!$this->global($a)) { abort_if(($x['tipo'] ?? '') === 'todos', 403, 'Tu alcance está limitado a tu unidad.'); abort_if(isset($x['unidad_id']) && (int) $x['unidad_id'] !== (int) $a->unidad_id, 403, 'No puedes asignar a otra unidad.'); foreach (($x['usuarios'] ?? []) as $id) $this->assertUserScope($a, User::findOrFail($id)); } }
    private function crearAsignaciones(CaleaEncuesta $e, User $a, array $x): void { if ($x['tipo'] === 'todos') CaleaEncuestaAsignacion::create(['encuesta_id' => $e->id, 'todos' => true, 'asignada_por' => $a->id]); elseif ($x['tipo'] === 'unidad') CaleaEncuestaAsignacion::create(['encuesta_id' => $e->id, 'unidad_id' => $x['unidad_id'] ?? $a->unidad_id, 'asignada_por' => $a->id]); else foreach (array_unique($x['usuarios'] ?? []) as $id) CaleaEncuestaAsignacion::create(['encuesta_id' => $e->id, 'user_id' => $id, 'asignada_por' => $a->id]); }
}
