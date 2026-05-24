<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidenciaTipo;
use App\Models\Personal;
use App\Models\PersonalIncidencia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsPersonalController extends Controller
{
    private const TIPOS_INCIDENCIA = [
        'VACACIONES' => 1,
        'INCAPACIDAD' => 2,
        'PERMISO' => 3,
        'FALTA' => 4,
        'COMISION' => 5,
        'SUSPENSION' => 6,
        'OTRO' => 7,
    ];

    public function index(Request $request)
    {
        $actor = $request->user();
        $q = trim((string) $request->query('q', ''));
        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $personals = $this->queryPersonalVisibleParaActor($actor)
            ->with(['unidad', 'turno', 'patrulla', 'user'])
            ->withCount('incidencias')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('ap_paterno', 'like', "%{$q}%")
                        ->orWhere('ap_materno', 'like', "%{$q}%")
                        ->orWhere('numero_empleado', 'like', "%{$q}%")
                        ->orWhere('curp', 'like', "%{$q}%")
                        ->orWhere('cuip', 'like', "%{$q}%")
                        ->orWhere('cup', 'like', "%{$q}%");
                });
            })
            ->orderByRaw("CASE WHEN estatus = 'ACTIVO' THEN 0 ELSE 1 END")
            ->orderBy('nombre')
            ->orderBy('ap_paterno')
            ->orderBy('ap_materno')
            ->paginate($perPage);

        return response()->json([
            'data' => $personals->getCollection()
                ->map(fn (Personal $personal) => $this->serializePersonal($personal))
                ->values(),
            'pagination' => [
                'current_page' => $personals->currentPage(),
                'last_page' => $personals->lastPage(),
                'per_page' => $personals->perPage(),
                'total' => $personals->total(),
            ],
        ]);
    }

    public function show(Request $request, Personal $personal)
    {
        $actor = $request->user();
        abort_unless(
            $this->queryPersonalVisibleParaActor($actor)->whereKey($personal->id)->exists(),
            404
        );

        $personal->load([
            'unidad',
            'turno',
            'patrulla',
            'user',
            'incidencias.tipo',
        ]);

        $personal->setRelation(
            'incidencias',
            $personal->incidencias
                ->sortByDesc(fn (PersonalIncidencia $incidencia) => optional($incidencia->fecha_inicio)->timestamp ?? 0)
                ->values()
        );

        return response()->json([
            'data' => $this->serializePersonal($personal, true),
        ]);
    }

    public function storeIncidencia(Request $request, Personal $personal)
    {
        $actor = $request->user();
        abort_unless(
            $this->queryPersonalVisibleParaActor($actor)->whereKey($personal->id)->exists(),
            404
        );

        $validated = $request->validate([
            'tipo' => 'required|string|max:60',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'hora_inicio' => 'nullable|date_format:H:i',
            'hora_fin' => 'nullable|date_format:H:i',
            'folio' => 'nullable|string|max:60',
            'motivo' => 'nullable|string|max:1000',
            'observaciones' => 'nullable|string|max:1000',
            'documento_id' => 'nullable|integer',
        ]);

        $tipo = $this->resolverTipoIncidencia($validated['tipo']);

        $inicio = $validated['fecha_inicio'];
        $fin = $validated['fecha_fin'] ?? null;
        $finComparacion = $fin ?: '9999-12-31';

        $traslapa = PersonalIncidencia::query()
            ->where('personal_id', $personal->id)
            ->where('fecha_inicio', '<=', $finComparacion)
            ->where(function ($query) use ($inicio) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $inicio);
            })
            ->exists();

        if ($traslapa) {
            throw ValidationException::withMessages([
                'fecha_inicio' => ['La incidencia traslapa con otra incidencia registrada para este elemento.'],
            ]);
        }

        $incidencia = PersonalIncidencia::create([
            'personal_id' => $personal->id,
            'incidencia_tipo_id' => $tipo->id,
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'] ?? null,
            'hora_inicio' => $validated['hora_inicio'] ?? null,
            'hora_fin' => $validated['hora_fin'] ?? null,
            'folio' => $validated['folio'] ?? null,
            'motivo' => $validated['motivo'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'documento_id' => $validated['documento_id'] ?? null,
            'activo' => 1,
        ]);

        $incidencia->load('tipo');

        return response()->json([
            'message' => 'Incidencia registrada correctamente.',
            'data' => $this->serializeIncidencia($incidencia),
        ], 201);
    }

    private function queryPersonalVisibleParaActor(User $actor)
    {
        return Personal::query()
            ->when(!$this->actorTieneVisibilidadGlobal($actor), function ($query) use ($actor) {
                $query->where('unidad_id', $actor->unidad_id);
            });
    }

    private function actorTieneVisibilidadGlobal(User $actor): bool
    {
        return $actor->hasRole('Superadmin') || (int) ($actor->unidad_id ?? 0) === 3;
    }

    private function resolverTipoIncidencia(string $raw): IncidenciaTipo
    {
        $clave = strtoupper(trim($raw));

        $tipo = IncidenciaTipo::query()
            ->where('activo', 1)
            ->where(function ($query) use ($clave) {
                $query->where('clave', $clave)
                    ->orWhere('nombre', $clave);
            })
            ->first();

        if (!$tipo && array_key_exists($clave, self::TIPOS_INCIDENCIA)) {
            $tipo = IncidenciaTipo::query()->find(self::TIPOS_INCIDENCIA[$clave]);
        }

        if (!$tipo) {
            throw ValidationException::withMessages([
                'tipo' => ['Tipo de incidencia no valido.'],
            ]);
        }

        return $tipo;
    }

    private function serializePersonal(Personal $personal, bool $withIncidencias = false): array
    {
        $data = [
            'id' => $personal->id,
            'numero_empleado' => $personal->numero_empleado,
            'nombre' => $personal->nombre,
            'ap_paterno' => $personal->ap_paterno,
            'ap_materno' => $personal->ap_materno,
            'nombre_completo' => $this->nombreCompleto($personal),
            'curp' => $personal->curp,
            'rfc' => $personal->rfc,
            'cuip' => $personal->cuip,
            'cup' => $personal->cup,
            'grado' => $personal->grado,
            'puesto' => $personal->puesto,
            'adscripcion' => $personal->adscripcion,
            'area' => $personal->area,
            'categoria' => $personal->categoria,
            'estatus' => $personal->estatus,
            'fecha_ingreso' => optional($personal->fecha_ingreso)->toDateString(),
            'fecha_baja' => optional($personal->fecha_baja)->toDateString(),
            'unidad_id' => $personal->unidad_id,
            'turno_id' => $personal->turno_id,
            'patrulla_id' => $personal->patrulla_id,
            'user_id' => $personal->user_id,
            'unidad' => $this->serializeSimple($personal->unidad, 'nombre'),
            'turno' => $this->serializeSimple($personal->turno, 'nombre'),
            'patrulla' => $this->serializeSimple($personal->patrulla, 'numero_economico'),
            'user' => $this->serializeSimple($personal->user, 'name', ['email' => optional($personal->user)->email]),
            'incidencias_count' => (int) ($personal->incidencias_count ?? 0),
        ];

        if ($withIncidencias) {
            $data['incidencias'] = $personal->incidencias
                ->map(fn (PersonalIncidencia $incidencia) => $this->serializeIncidencia($incidencia))
                ->values();
            $data['incidencias_count'] = $data['incidencias']->count();
        }

        return $data;
    }

    private function serializeIncidencia(PersonalIncidencia $incidencia): array
    {
        return [
            'id' => $incidencia->id,
            'incidencia_tipo_id' => $incidencia->incidencia_tipo_id,
            'tipo' => optional($incidencia->tipo)->clave,
            'tipo_nombre' => optional($incidencia->tipo)->nombre ?: optional($incidencia->tipo)->clave,
            'fecha_inicio' => optional($incidencia->fecha_inicio)->toDateString(),
            'fecha_fin' => optional($incidencia->fecha_fin)->toDateString(),
            'hora_inicio' => $incidencia->hora_inicio,
            'hora_fin' => $incidencia->hora_fin,
            'folio' => $incidencia->folio,
            'motivo' => $incidencia->motivo,
            'observaciones' => $incidencia->observaciones,
            'documento_id' => $incidencia->documento_id,
            'activo' => (bool) $incidencia->activo,
            'created_at' => optional($incidencia->created_at)->toIso8601String(),
            'updated_at' => optional($incidencia->updated_at)->toIso8601String(),
        ];
    }

    private function serializeSimple($model, string $labelKey, array $extra = []): ?array
    {
        if (!$model) {
            return null;
        }

        return array_merge([
            'id' => $model->id,
            'nombre' => $model->{$labelKey},
        ], $extra);
    }

    private function nombreCompleto(Personal $personal): string
    {
        return trim(implode(' ', array_filter([
            $personal->nombre,
            $personal->ap_paterno,
            $personal->ap_materno,
        ])));
    }
}
