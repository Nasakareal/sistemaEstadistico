<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delegacion;
use App\Models\DelegacionActividadFisica;
use App\Support\HechoAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DelegacionActividadFisicaController extends Controller
{
    private const TZ = 'America/Mexico_City';
    private const UNIDAD_DELEGACIONES_ID = 2;
    private const UNIDAD_SEGURIDAD_VIAL_ID = 3;

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$this->puedeVerModulo($user)) {
            return response()->json([
                'message' => 'No tienes acceso a actividades fisicas de delegaciones.',
            ], 403);
        }

        $query = DelegacionActividadFisica::query()
            ->with([
                'delegacion:id,clave,nombre,municipio,delegacion_padre_id',
                'delegacion.padre:id,clave,nombre,municipio',
                'creador:id,name',
            ]);

        $this->aplicarAlcanceDelegacion($query, $user);
        $this->aplicarFiltros($query, $request, $user);

        $perPage = $this->clampInt($request->query('per_page', 25), 1, 100, 25);
        $actividades = $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id')
            ->paginate($perPage);

        $actividades->getCollection()->transform(fn ($actividad) => $this->actividadPayload($actividad));

        return response()->json($actividades);
    }

    public function show(Request $request, DelegacionActividadFisica $actividadFisica)
    {
        $user = $request->user();
        if (!$this->puedeVerModulo($user)) {
            return response()->json([
                'message' => 'No tienes acceso a actividades fisicas de delegaciones.',
            ], 403);
        }

        if (!$this->puedeVerActividad($actividadFisica, $user)) {
            return response()->json([
                'message' => 'Actividad fisica no encontrada.',
            ], 404);
        }

        $actividadFisica->load([
            'delegacion:id,clave,nombre,municipio,delegacion_padre_id',
            'delegacion.padre:id,clave,nombre,municipio',
            'creador:id,name',
            'actualizador:id,name',
        ]);

        return response()->json([
            'data' => $this->actividadPayload($actividadFisica, true),
        ]);
    }

    public function tipos(Request $request)
    {
        $user = $request->user();
        if (!$this->puedeVerModulo($user)) {
            return response()->json([
                'message' => 'No tienes acceso a actividades fisicas de delegaciones.',
            ], 403);
        }

        $query = DelegacionActividadFisica::query();
        $this->aplicarAlcanceDelegacion($query, $user);

        $tipos = $query
            ->select('tipo_ejercicio')
            ->whereNotNull('tipo_ejercicio')
            ->distinct()
            ->orderBy('tipo_ejercicio')
            ->pluck('tipo_ejercicio')
            ->values();

        return response()->json([
            'data' => $tipos,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$this->puedeCapturarModulo($user)) {
            return response()->json([
                'message' => 'No tienes permiso para capturar actividades fisicas de delegaciones.',
            ], 403);
        }

        $validated = $request->validate([
            'delegacion_id' => ['nullable', 'integer', 'exists:delegaciones,id'],
            'fecha' => ['nullable', 'date'],
            'hora' => ['nullable', 'date_format:H:i'],
            'tipo_ejercicio' => ['required', 'string', 'max:180'],
            'elementos_participantes' => ['required', 'integer', 'min:0', 'max:5000'],
            'foto' => ['required', 'image', 'max:4096'],
        ]);

        $delegacionId = (int) ($validated['delegacion_id'] ?? 0);
        if ($delegacionId <= 0) {
            $delegacionId = (int) ($user->delegacion_id ?? 0);
        }

        if ($delegacionId <= 0 || !$this->delegacionPermitida($delegacionId, $user)) {
            return response()->json([
                'message' => 'No puedes capturar actividades fisicas para esa delegacion.',
                'errors' => [
                    'delegacion_id' => ['Selecciona una delegacion valida para tu usuario.'],
                ],
            ], 422);
        }

        $foto = $request->file('foto');
        $fotoPath = $foto->store('delegaciones/actividades-fisicas', 'public');

        $actividad = DelegacionActividadFisica::create([
            'delegacion_id' => $delegacionId,
            'fecha' => $validated['fecha'] ?? Carbon::now(self::TZ)->toDateString(),
            'hora' => $validated['hora'] ?? null,
            'tipo_ejercicio' => Str::upper(trim((string) $validated['tipo_ejercicio'])),
            'elementos_participantes' => (int) $validated['elementos_participantes'],
            'foto_path' => $fotoPath,
            'foto_nombre_original' => $foto->getClientOriginalName(),
            'foto_hash' => hash_file('sha256', $foto->getRealPath()),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $actividad->load([
            'delegacion:id,clave,nombre,municipio,delegacion_padre_id',
            'delegacion.padre:id,clave,nombre,municipio',
            'creador:id,name',
            'actualizador:id,name',
        ]);

        return response()->json([
            'message' => 'Actividad fisica registrada correctamente.',
            'data' => $this->actividadPayload($actividad, true),
        ], 201);
    }

    private function aplicarFiltros($query, Request $request, $user): void
    {
        $fechaInicio = $request->query('fecha_inicio', $request->query('desde'));
        $fechaFin = $request->query('fecha_fin', $request->query('hasta'));

        if ($fechaInicio) {
            $query->whereDate('fecha', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->whereDate('fecha', '<=', $fechaFin);
        }

        $delegacionId = (int) $request->query('delegacion_id', 0);
        if ($delegacionId > 0) {
            if ($this->delegacionPermitida($delegacionId, $user)) {
                $query->where('delegacion_id', $delegacionId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $tipo = trim((string) $request->query('tipo_ejercicio', ''));
        if ($tipo !== '') {
            $query->where('tipo_ejercicio', Str::upper($tipo));
        }

        $buscar = trim((string) $request->query('buscar', $request->query('q', '')));
        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('tipo_ejercicio', 'like', "%{$buscar}%")
                    ->orWhere('elementos_participantes', 'like', "%{$buscar}%")
                    ->orWhereHas('delegacion', function ($delegacion) use ($buscar) {
                        $delegacion->where('nombre', 'like', "%{$buscar}%")
                            ->orWhere('clave', 'like', "%{$buscar}%")
                            ->orWhere('municipio', 'like', "%{$buscar}%");
                    });
            });
        }
    }

    private function aplicarAlcanceDelegacion($query, $user): void
    {
        $ids = $this->delegacionIdsPermitidas($user);

        if ($ids === null) {
            return;
        }

        if (empty($ids)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereIn('delegacion_id', $ids);
    }

    private function puedeVerActividad(DelegacionActividadFisica $actividad, $user): bool
    {
        $query = DelegacionActividadFisica::query()
            ->whereKey($actividad->getKey());

        $this->aplicarAlcanceDelegacion($query, $user);

        return $query->exists();
    }

    private function delegacionPermitida(int $delegacionId, $user): bool
    {
        $existe = Delegacion::query()
            ->whereKey($delegacionId)
            ->exists();

        if (!$existe) {
            return false;
        }

        $ids = $this->delegacionIdsPermitidas($user);

        return $ids === null || in_array($delegacionId, $ids, true);
    }

    private function delegacionIdsPermitidas($user): ?array
    {
        if (!$user) {
            return [];
        }

        if ($this->puedeVerTodasDelegaciones($user)) {
            return null;
        }

        if ((int) ($user->unidad_id ?? 0) !== self::UNIDAD_DELEGACIONES_ID) {
            return [];
        }

        return HechoAccess::delegacionIdsVisiblesParaUsuario($user);
    }

    private function puedeVerModulo($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->puedeVerTodasDelegaciones($user)
            || (int) ($user->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID;
    }

    private function puedeCapturarModulo($user): bool
    {
        return $this->puedeVerModulo($user);
    }

    private function puedeVerTodasDelegaciones($user): bool
    {
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperadmin') && $user->isSuperadmin()) {
            return true;
        }

        if ((int) ($user->unidad_id ?? 0) === self::UNIDAD_SEGURIDAD_VIAL_ID) {
            return true;
        }

        return (int) ($user->unidad_id ?? 0) === self::UNIDAD_DELEGACIONES_ID
            && ($user->hasRole('Administrador') || $user->hasRole('Subdirector'));
    }

    private function actividadPayload(DelegacionActividadFisica $actividad, bool $detallado = false): array
    {
        $delegacion = $actividad->delegacion;

        $payload = [
            'id' => (int) $actividad->id,
            'delegacion_id' => $actividad->delegacion_id ? (int) $actividad->delegacion_id : null,
            'delegacion' => $delegacion ? [
                'id' => (int) $delegacion->id,
                'clave' => $delegacion->clave,
                'nombre' => $delegacion->nombre,
                'municipio' => $delegacion->municipio,
                'padre' => $delegacion->padre ? [
                    'id' => (int) $delegacion->padre->id,
                    'clave' => $delegacion->padre->clave,
                    'nombre' => $delegacion->padre->nombre,
                    'municipio' => $delegacion->padre->municipio,
                ] : null,
            ] : null,
            'fecha' => optional($actividad->fecha)->toDateString(),
            'hora' => $actividad->hora ? substr((string) $actividad->hora, 0, 5) : null,
            'tipo_ejercicio' => $actividad->tipo_ejercicio,
            'elementos_participantes' => (int) $actividad->elementos_participantes,
            'foto_path' => $actividad->foto_path,
            'foto_url' => $actividad->foto_path ? asset('storage/' . ltrim($actividad->foto_path, '/')) : null,
            'created_by' => $actividad->created_by ? (int) $actividad->created_by : null,
            'capturo' => $actividad->creador ? [
                'id' => (int) $actividad->creador->id,
                'name' => $actividad->creador->name,
            ] : null,
            'created_at' => $this->formatDateTime($actividad->created_at),
            'updated_at' => $this->formatDateTime($actividad->updated_at),
        ];

        if ($detallado) {
            $payload['foto_nombre_original'] = $actividad->foto_nombre_original;
            $payload['updated_by'] = $actividad->updated_by ? (int) $actividad->updated_by : null;
            $payload['actualizo'] = $actividad->actualizador ? [
                'id' => (int) $actividad->actualizador->id,
                'name' => $actividad->actualizador->name,
            ] : null;
        }

        return $payload;
    }

    private function formatDateTime($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->timezone(self::TZ)->toDateTimeString();
    }

    private function clampInt($value, int $min, int $max, int $default): int
    {
        if (!is_numeric($value)) {
            return $default;
        }

        return max($min, min($max, (int) $value));
    }
}
