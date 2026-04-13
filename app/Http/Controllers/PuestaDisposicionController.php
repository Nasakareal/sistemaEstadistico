<?php

namespace App\Http\Controllers;

use App\Models\PuestaDisposicion;
use App\Models\PuestaDisposicionPersona;
use App\Models\PuestaDisposicionVehiculo;
use App\Models\PuestaDisposicionObjeto;
use App\Models\Unidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PuestaDisposicionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $usuario = Auth::user();

            if (!$usuario) {
                abort(403);
            }

            return $next($request);
        });
    }

    private function esSuperadmin($usuario): bool
    {
        return $usuario && $usuario->hasRole('Superadmin');
    }

    private function puedeVerTodasLasUnidades($usuario): bool
    {
        return $this->esSuperadmin($usuario) || (int)($usuario->unidad_id ?? 0) === 3;
    }

    private function puedeSeleccionarUnidadRegistro($usuario): bool
    {
        return $this->esSuperadmin($usuario) || empty($usuario->unidad_id);
    }

    private function queryVisibleByUser($usuario)
    {
        $query = PuestaDisposicion::query()
            ->with(['unidad', 'delegacion', 'destacamento', 'creador']);

        if ($this->puedeVerTodasLasUnidades($usuario)) {
            return $query;
        }

        if ($usuario->unidad_id) {
            $query->where('unidad_id', $usuario->unidad_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if (!is_null($usuario->delegacion_id)) {
            $query->where('delegacion_id', $usuario->delegacion_id);
        }

        if (!is_null($usuario->destacamento_id)) {
            $query->where('destacamento_id', $usuario->destacamento_id);
        }

        return $query;
    }

    private function findVisibleOrFail($id, $usuario): PuestaDisposicion
    {
        return $this->queryVisibleByUser($usuario)
            ->with(['personas', 'vehiculos', 'objetos', 'unidad', 'delegacion', 'destacamento', 'creador', 'actualizador'])
            ->findOrFail($id);
    }

    private function normalizarTextoNullable($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim((string)$valor);

        return $valor === '' ? null : strtoupper($valor);
    }

    private function normalizarTextoRequerido($valor): string
    {
        return strtoupper(trim((string)$valor));
    }

    private function obtenerNombreUnidad(?int $unidadId): string
    {
        if (!$unidadId) {
            return 'SIN ASIGNAR';
        }

        return Unidad::where('id', $unidadId)->value('nombre') ?: 'SIN ASIGNAR';
    }

    private function obtenerUnidadesActivas()
    {
        return Unidad::query()
            ->where('activa', 1)
            ->orderBy('id')
            ->get(['id', 'nombre']);
    }

    private function resolverUnidadRegistro(Request $request, $usuario): int
    {
        $unidadId = $this->puedeSeleccionarUnidadRegistro($usuario)
            ? (int)$request->input('unidad_id')
            : (int)($usuario->unidad_id ?? 0);

        if (!$unidadId || !Unidad::where('id', $unidadId)->where('activa', 1)->exists()) {
            throw ValidationException::withMessages([
                'unidad_id' => 'Seleccione una unidad válida para la puesta a disposición.',
            ]);
        }

        return $unidadId;
    }

    public function index(Request $request)
    {
        $usuario = auth()->user();

        $anioActual = now()->year;
        $anioSeleccionado = $request->get('anio', $anioActual);

        $query = $this->queryVisibleByUser($usuario)
            ->where('anio', $anioSeleccionado)
            ->orderByDesc('numero_puesta');

        if ($request->filled('motivo')) {
            $query->where('motivo', strtoupper(trim($request->motivo)));
        }

        if ($request->filled('tipo_puesta')) {
            $query->where('tipo_puesta', strtoupper(trim($request->tipo_puesta)));
        }

        $puestas = $query->get();

        $anios = $this->queryVisibleByUser($usuario)
            ->select('anio')
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio');

        return view('puestas_disposicion.index', compact(
            'puestas',
            'anios',
            'anioActual',
            'anioSeleccionado'
        ));
    }

    public function create()
    {
        $usuario = auth()->user();
        $anioActual = now()->year;
        $unidades = $this->obtenerUnidadesActivas();
        $puedeSeleccionarUnidad = $this->puedeSeleccionarUnidadRegistro($usuario);

        $unidadSeleccionadaId = $puedeSeleccionarUnidad
            ? (int)old('unidad_id', $usuario->unidad_id ?: optional($unidades->first())->id)
            : (int)$usuario->unidad_id;

        $unidadNombre = $this->obtenerNombreUnidad($unidadSeleccionadaId);

        $ultimoRegistro = PuestaDisposicion::query()
            ->where('anio', $anioActual)
            ->where('unidad_id', $unidadSeleccionadaId)
            ->orderByDesc('numero_puesta')
            ->first();

        $numeroSiguiente = $ultimoRegistro ? ($ultimoRegistro->numero_puesta + 1) : 1;
        $numerosSiguientesPorUnidad = [];

        foreach ($unidades as $unidad) {
            $ultimoPorUnidad = PuestaDisposicion::query()
                ->where('anio', $anioActual)
                ->where('unidad_id', $unidad->id)
                ->max('numero_puesta');

            $numerosSiguientesPorUnidad[(int)$unidad->id] = $ultimoPorUnidad ? ((int)$ultimoPorUnidad + 1) : 1;
        }

        return view('puestas_disposicion.create', compact(
            'numeroSiguiente',
            'unidadNombre',
            'unidades',
            'puedeSeleccionarUnidad',
            'unidadSeleccionadaId',
            'numerosSiguientesPorUnidad'
        ));
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();
        $unidadRegistroId = $this->resolverUnidadRegistro($request, $usuario);

        $request->merge([
            'tipo_puesta'           => $this->normalizarTextoRequerido($request->input('tipo_puesta')),
            'motivo'                => $this->normalizarTextoRequerido($request->input('motivo')),
            'estatus'               => 'ACTIVA',
            'nombre_policia'        => $this->normalizarTextoRequerido($request->input('nombre_policia')),
            'nombre_mp'             => $this->normalizarTextoNullable($request->input('nombre_mp')),
            'autoridad_receptora'   => $this->normalizarTextoNullable($request->input('autoridad_receptora')),
            'area'                  => $this->obtenerNombreUnidad($unidadRegistroId),
            'carpeta_investigacion' => $this->normalizarTextoNullable($request->input('carpeta_investigacion')),
            'oficio'                => $this->normalizarTextoNullable($request->input('oficio')),
            'lugar_puesta'          => $this->normalizarTextoNullable($request->input('lugar_puesta')),
            'narrativa'             => $request->filled('narrativa') ? strtoupper(trim((string)$request->input('narrativa'))) : null,
            'observaciones'         => $request->filled('observaciones') ? strtoupper(trim((string)$request->input('observaciones'))) : null,
        ]);

        $request->validate([
            'tipo_puesta'           => 'required|string|max:100',
            'motivo'                => 'required|string|max:150',
            'estatus'               => 'nullable|string|max:100',
            'nombre_policia'        => 'required|string|max:255',
            'unidad_id'             => $this->puedeSeleccionarUnidadRegistro($usuario) ? 'required|integer|exists:unidades,id' : 'nullable',
            'nombre_mp'             => 'nullable|string|max:255',
            'autoridad_receptora'   => 'nullable|string|max:255',
            'area'                  => 'nullable|string|max:255',
            'carpeta_investigacion' => 'nullable|string|max:255',
            'oficio'                => 'nullable|string|max:255',
            'fecha_puesta'          => 'required|date',
            'hora_puesta'           => 'nullable|date_format:H:i',
            'lugar_puesta'          => 'nullable|string|max:255',
            'narrativa'             => 'nullable|string',
            'observaciones'         => 'nullable|string',
            'archivo_puesta'        => 'nullable|file|mimes:pdf|max:10240',

            'personas'                          => 'nullable|array',
            'personas.*.nombre_completo'        => 'required_with:personas|string|max:255',
            'personas.*.alias'                  => 'nullable|string|max:255',
            'personas.*.edad'                   => 'nullable|integer|min:0|max:150',
            'personas.*.sexo'                   => 'nullable|string|max:20',
            'personas.*.fecha_nacimiento'       => 'nullable|date',
            'personas.*.curp'                   => 'nullable|string|max:50',
            'personas.*.rfc'                    => 'nullable|string|max:30',
            'personas.*.domicilio'              => 'nullable|string',
            'personas.*.calidad'                => 'required_with:personas|string|max:100',
            'personas.*.delito_o_motivo'        => 'nullable|string|max:255',
            'personas.*.orden_aprehension'      => 'nullable|boolean',
            'personas.*.mandamiento_judicial'   => 'nullable|string|max:255',
            'personas.*.observaciones'          => 'nullable|string',

            'vehiculos'                         => 'nullable|array',
            'vehiculos.*.tipo'                  => 'nullable|string|max:100',
            'vehiculos.*.marca'                 => 'nullable|string|max:100',
            'vehiculos.*.submarca'              => 'nullable|string|max:100',
            'vehiculos.*.modelo'                => 'nullable|string|max:20',
            'vehiculos.*.color'                 => 'nullable|string|max:100',
            'vehiculos.*.placas'                => 'nullable|string|max:50',
            'vehiculos.*.serie'                 => 'nullable|string|max:100',
            'vehiculos.*.calidad'               => 'required_with:vehiculos|string|max:100',
            'vehiculos.*.motivo_relacion'       => 'nullable|string|max:255',
            'vehiculos.*.con_reporte_robo'      => 'nullable|boolean',
            'vehiculos.*.numero_reporte_robo'   => 'nullable|string|max:255',
            'vehiculos.*.observaciones'         => 'nullable|string',

            'objetos'                           => 'nullable|array',
            'objetos.*.tipo_objeto'             => 'required_with:objetos|string|max:100',
            'objetos.*.descripcion'             => 'required_with:objetos|string',
            'objetos.*.cantidad'                => 'nullable|numeric|min:0',
            'objetos.*.unidad_medida'           => 'nullable|string|max:50',
            'objetos.*.cadena_custodia'         => 'nullable|string|max:255',
            'objetos.*.observaciones'           => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $archivoPuesta = null;

            if ($request->hasFile('archivo_puesta')) {
                $archivoPuesta = $request->file('archivo_puesta')->store('puestas_disposicion', 'public');
            }

            $anioActual = now()->year;

            $ultimoRegistro = PuestaDisposicion::query()
                ->where('anio', $anioActual)
                ->where('unidad_id', $unidadRegistroId)
                ->orderByDesc('numero_puesta')
                ->lockForUpdate()
                ->first();

            $numeroSiguiente = $ultimoRegistro ? ($ultimoRegistro->numero_puesta + 1) : 1;

            $puesta = PuestaDisposicion::create([
                'numero_puesta'         => $numeroSiguiente,
                'anio'                  => $anioActual,
                'tipo_puesta'           => $request->input('tipo_puesta'),
                'motivo'                => $request->input('motivo'),
                'estatus'               => 'ACTIVA',
                'nombre_policia'        => $request->input('nombre_policia'),
                'nombre_mp'             => $request->input('nombre_mp'),
                'autoridad_receptora'   => $request->input('autoridad_receptora'),
                'area'                  => $this->obtenerNombreUnidad($unidadRegistroId),
                'carpeta_investigacion' => $request->input('carpeta_investigacion'),
                'oficio'                => $request->input('oficio'),
                'fecha_puesta'          => $request->input('fecha_puesta'),
                'hora_puesta'           => $request->input('hora_puesta'),
                'lugar_puesta'          => $request->input('lugar_puesta'),
                'narrativa'             => $request->input('narrativa'),
                'observaciones'         => $request->input('observaciones'),
                'unidad_id'             => $unidadRegistroId,
                'delegacion_id'         => $usuario->delegacion_id,
                'destacamento_id'       => $usuario->destacamento_id,
                'archivo_puesta'        => $archivoPuesta,
                'created_by'            => $usuario->id,
            ]);

            foreach ($request->input('personas', []) as $persona) {
                if (empty(trim((string)($persona['nombre_completo'] ?? '')))) {
                    continue;
                }

                PuestaDisposicionPersona::create([
                    'puesta_disposicion_id' => $puesta->id,
                    'nombre_completo'       => $this->normalizarTextoRequerido($persona['nombre_completo'] ?? ''),
                    'alias'                 => $this->normalizarTextoNullable($persona['alias'] ?? null),
                    'edad'                  => $persona['edad'] ?? null,
                    'sexo'                  => $this->normalizarTextoNullable($persona['sexo'] ?? null),
                    'fecha_nacimiento'      => $persona['fecha_nacimiento'] ?? null,
                    'curp'                  => $this->normalizarTextoNullable($persona['curp'] ?? null),
                    'rfc'                   => $this->normalizarTextoNullable($persona['rfc'] ?? null),
                    'domicilio'             => isset($persona['domicilio']) && trim((string)$persona['domicilio']) !== ''
                                                ? strtoupper(trim((string)$persona['domicilio']))
                                                : null,
                    'calidad'               => $this->normalizarTextoRequerido($persona['calidad'] ?? ''),
                    'delito_o_motivo'       => $this->normalizarTextoNullable($persona['delito_o_motivo'] ?? null),
                    'orden_aprehension'     => !empty($persona['orden_aprehension']),
                    'mandamiento_judicial'  => $this->normalizarTextoNullable($persona['mandamiento_judicial'] ?? null),
                    'observaciones'         => isset($persona['observaciones']) && trim((string)$persona['observaciones']) !== ''
                                                ? strtoupper(trim((string)$persona['observaciones']))
                                                : null,
                ]);
            }

            foreach ($request->input('vehiculos', []) as $vehiculo) {
                if (
                    empty(trim((string)($vehiculo['placas'] ?? ''))) &&
                    empty(trim((string)($vehiculo['serie'] ?? ''))) &&
                    empty(trim((string)($vehiculo['marca'] ?? '')))
                ) {
                    continue;
                }

                PuestaDisposicionVehiculo::create([
                    'puesta_disposicion_id' => $puesta->id,
                    'vehiculo_id'           => null,
                    'tipo'                  => $this->normalizarTextoNullable($vehiculo['tipo'] ?? null),
                    'marca'                 => $this->normalizarTextoNullable($vehiculo['marca'] ?? null),
                    'submarca'              => $this->normalizarTextoNullable($vehiculo['submarca'] ?? null),
                    'modelo'                => $this->normalizarTextoNullable($vehiculo['modelo'] ?? null),
                    'color'                 => $this->normalizarTextoNullable($vehiculo['color'] ?? null),
                    'placas'                => $this->normalizarTextoNullable($vehiculo['placas'] ?? null),
                    'serie'                 => $this->normalizarTextoNullable($vehiculo['serie'] ?? null),
                    'calidad'               => $this->normalizarTextoRequerido($vehiculo['calidad'] ?? ''),
                    'motivo_relacion'       => $this->normalizarTextoNullable($vehiculo['motivo_relacion'] ?? null),
                    'con_reporte_robo'      => !empty($vehiculo['con_reporte_robo']),
                    'numero_reporte_robo'   => $this->normalizarTextoNullable($vehiculo['numero_reporte_robo'] ?? null),
                    'observaciones'         => isset($vehiculo['observaciones']) && trim((string)$vehiculo['observaciones']) !== ''
                                                ? strtoupper(trim((string)$vehiculo['observaciones']))
                                                : null,
                ]);
            }

            foreach ($request->input('objetos', []) as $objeto) {
                if (
                    empty(trim((string)($objeto['tipo_objeto'] ?? ''))) &&
                    empty(trim((string)($objeto['descripcion'] ?? '')))
                ) {
                    continue;
                }

                PuestaDisposicionObjeto::create([
                    'puesta_disposicion_id' => $puesta->id,
                    'tipo_objeto'           => $this->normalizarTextoRequerido($objeto['tipo_objeto'] ?? ''),
                    'descripcion'           => strtoupper(trim((string)($objeto['descripcion'] ?? ''))),
                    'cantidad'              => $objeto['cantidad'] ?? null,
                    'unidad_medida'         => $this->normalizarTextoNullable($objeto['unidad_medida'] ?? null),
                    'cadena_custodia'       => $this->normalizarTextoNullable($objeto['cadena_custodia'] ?? null),
                    'observaciones'         => isset($objeto['observaciones']) && trim((string)$objeto['observaciones']) !== ''
                                                ? strtoupper(trim((string)$objeto['observaciones']))
                                                : null,
                ]);
            }

            DB::commit();

            return redirect()->route('puestas_disposicion.index')
                ->with('success', 'Puesta a disposición creada exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al guardar la puesta a disposición: ' . $e->getMessage());
        }
    }

    public function show(PuestaDisposicion $puestaDisposicion)
    {
        $usuario = auth()->user();

        $puestaDisposicion = $this->findVisibleOrFail($puestaDisposicion->id, $usuario);

        return view('puestas_disposicion.show', compact('puestaDisposicion'));
    }

    public function edit(PuestaDisposicion $puestaDisposicion)
    {
        $usuario = auth()->user();

        $puestaDisposicion = $this->findVisibleOrFail($puestaDisposicion->id, $usuario);

        return view('puestas_disposicion.edit', compact('puestaDisposicion'));
    }

    public function update(Request $request, PuestaDisposicion $puestaDisposicion)
    {
        $usuario = auth()->user();

        $puestaDisposicion = $this->findVisibleOrFail($puestaDisposicion->id, $usuario);

        $request->merge([
            'tipo_puesta'           => $this->normalizarTextoRequerido($request->input('tipo_puesta')),
            'motivo'                => $this->normalizarTextoRequerido($request->input('motivo')),
            'estatus'               => 'ACTIVA',
            'nombre_policia'        => $this->normalizarTextoRequerido($request->input('nombre_policia')),
            'nombre_mp'             => $this->normalizarTextoNullable($request->input('nombre_mp')),
            'autoridad_receptora'   => $this->normalizarTextoNullable($request->input('autoridad_receptora')),
            'area'                  => $this->obtenerNombreUnidad($puestaDisposicion->unidad_id),
            'carpeta_investigacion' => $this->normalizarTextoNullable($request->input('carpeta_investigacion')),
            'oficio'                => $this->normalizarTextoNullable($request->input('oficio')),
            'lugar_puesta'          => $this->normalizarTextoNullable($request->input('lugar_puesta')),
            'narrativa'             => $request->filled('narrativa') ? strtoupper(trim((string)$request->input('narrativa'))) : null,
            'observaciones'         => $request->filled('observaciones') ? strtoupper(trim((string)$request->input('observaciones'))) : null,
        ]);

        $request->validate([
            'numero_puesta'         => 'required|integer',
            'anio'                  => 'required|digits:4',
            'tipo_puesta'           => 'required|string|max:100',
            'motivo'                => 'required|string|max:150',
            'estatus'               => 'nullable|string|max:100',
            'nombre_policia'        => 'required|string|max:255',
            'nombre_mp'             => 'nullable|string|max:255',
            'autoridad_receptora'   => 'nullable|string|max:255',
            'area'                  => 'nullable|string|max:255',
            'carpeta_investigacion' => 'nullable|string|max:255',
            'oficio'                => 'nullable|string|max:255',
            'fecha_puesta'          => 'required|date',
            'hora_puesta'           => 'nullable|date_format:H:i',
            'lugar_puesta'          => 'nullable|string|max:255',
            'narrativa'             => 'nullable|string',
            'observaciones'         => 'nullable|string',
            'archivo_puesta'        => 'nullable|file|mimes:pdf|max:10240',

            'personas'                          => 'nullable|array',
            'personas.*.nombre_completo'        => 'required_with:personas|string|max:255',
            'personas.*.alias'                  => 'nullable|string|max:255',
            'personas.*.edad'                   => 'nullable|integer|min:0|max:150',
            'personas.*.sexo'                   => 'nullable|string|max:20',
            'personas.*.fecha_nacimiento'       => 'nullable|date',
            'personas.*.curp'                   => 'nullable|string|max:50',
            'personas.*.rfc'                    => 'nullable|string|max:30',
            'personas.*.domicilio'              => 'nullable|string',
            'personas.*.calidad'                => 'required_with:personas|string|max:100',
            'personas.*.delito_o_motivo'        => 'nullable|string|max:255',
            'personas.*.orden_aprehension'      => 'nullable|boolean',
            'personas.*.mandamiento_judicial'   => 'nullable|string|max:255',
            'personas.*.observaciones'          => 'nullable|string',

            'vehiculos'                         => 'nullable|array',
            'vehiculos.*.tipo'                  => 'nullable|string|max:100',
            'vehiculos.*.marca'                 => 'nullable|string|max:100',
            'vehiculos.*.submarca'              => 'nullable|string|max:100',
            'vehiculos.*.modelo'                => 'nullable|string|max:20',
            'vehiculos.*.color'                 => 'nullable|string|max:100',
            'vehiculos.*.placas'                => 'nullable|string|max:50',
            'vehiculos.*.serie'                 => 'nullable|string|max:100',
            'vehiculos.*.calidad'               => 'required_with:vehiculos|string|max:100',
            'vehiculos.*.motivo_relacion'       => 'nullable|string|max:255',
            'vehiculos.*.con_reporte_robo'      => 'nullable|boolean',
            'vehiculos.*.numero_reporte_robo'   => 'nullable|string|max:255',
            'vehiculos.*.observaciones'         => 'nullable|string',

            'objetos'                           => 'nullable|array',
            'objetos.*.tipo_objeto'             => 'required_with:objetos|string|max:100',
            'objetos.*.descripcion'             => 'required_with:objetos|string',
            'objetos.*.cantidad'                => 'nullable|numeric|min:0',
            'objetos.*.unidad_medida'           => 'nullable|string|max:50',
            'objetos.*.cadena_custodia'         => 'nullable|string|max:255',
            'objetos.*.observaciones'           => 'nullable|string',
        ]);

        $existeDuplicado = PuestaDisposicion::query()
            ->where('id', '!=', $puestaDisposicion->id)
            ->where('anio', $request->input('anio'))
            ->where('unidad_id', $puestaDisposicion->unidad_id)
            ->where('numero_puesta', $request->input('numero_puesta'))
            ->exists();

        if ($existeDuplicado) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'numero_puesta' => 'El número de puesta ya existe para esta unidad en ese año.'
                ]);
        }

        DB::beginTransaction();

        try {
            $archivoPuesta = $puestaDisposicion->archivo_puesta;

            if ($request->hasFile('archivo_puesta')) {
                if ($archivoPuesta && Storage::disk('public')->exists($archivoPuesta)) {
                    Storage::disk('public')->delete($archivoPuesta);
                }

                $archivoPuesta = $request->file('archivo_puesta')->store('puestas_disposicion', 'public');
            }

            $dataUpdate = [
                'numero_puesta'         => $request->input('numero_puesta'),
                'anio'                  => $request->input('anio'),
                'tipo_puesta'           => $request->input('tipo_puesta'),
                'motivo'                => $request->input('motivo'),
                'estatus'               => 'ACTIVA',
                'nombre_policia'        => $request->input('nombre_policia'),
                'nombre_mp'             => $request->input('nombre_mp'),
                'autoridad_receptora'   => $request->input('autoridad_receptora'),
                'area'                  => $this->obtenerNombreUnidad($puestaDisposicion->unidad_id),
                'carpeta_investigacion' => $request->input('carpeta_investigacion'),
                'oficio'                => $request->input('oficio'),
                'fecha_puesta'          => $request->input('fecha_puesta'),
                'hora_puesta'           => $request->input('hora_puesta'),
                'lugar_puesta'          => $request->input('lugar_puesta'),
                'narrativa'             => $request->input('narrativa'),
                'observaciones'         => $request->input('observaciones'),
                'archivo_puesta'        => $archivoPuesta,
                'updated_by'            => $usuario->id,
            ];

            if ($this->esSuperadmin($usuario)) {
                $dataUpdate['unidad_id']       = $request->input('unidad_id', $puestaDisposicion->unidad_id);
                $dataUpdate['delegacion_id']   = $request->input('delegacion_id', $puestaDisposicion->delegacion_id);
                $dataUpdate['destacamento_id'] = $request->input('destacamento_id', $puestaDisposicion->destacamento_id);
            } else {
                $dataUpdate['unidad_id']       = $puestaDisposicion->unidad_id;
                $dataUpdate['delegacion_id']   = $puestaDisposicion->delegacion_id;
                $dataUpdate['destacamento_id'] = $puestaDisposicion->destacamento_id;
            }

            $puestaDisposicion->update($dataUpdate);

            $puestaDisposicion->personas()->delete();
            $puestaDisposicion->vehiculos()->delete();
            $puestaDisposicion->objetos()->delete();

            foreach ($request->input('personas', []) as $persona) {
                if (empty(trim((string)($persona['nombre_completo'] ?? '')))) {
                    continue;
                }

                PuestaDisposicionPersona::create([
                    'puesta_disposicion_id' => $puestaDisposicion->id,
                    'nombre_completo'       => $this->normalizarTextoRequerido($persona['nombre_completo'] ?? ''),
                    'alias'                 => $this->normalizarTextoNullable($persona['alias'] ?? null),
                    'edad'                  => $persona['edad'] ?? null,
                    'sexo'                  => $this->normalizarTextoNullable($persona['sexo'] ?? null),
                    'fecha_nacimiento'      => $persona['fecha_nacimiento'] ?? null,
                    'curp'                  => $this->normalizarTextoNullable($persona['curp'] ?? null),
                    'rfc'                   => $this->normalizarTextoNullable($persona['rfc'] ?? null),
                    'domicilio'             => isset($persona['domicilio']) && trim((string)$persona['domicilio']) !== ''
                                                ? strtoupper(trim((string)$persona['domicilio']))
                                                : null,
                    'calidad'               => $this->normalizarTextoRequerido($persona['calidad'] ?? ''),
                    'delito_o_motivo'       => $this->normalizarTextoNullable($persona['delito_o_motivo'] ?? null),
                    'orden_aprehension'     => !empty($persona['orden_aprehension']),
                    'mandamiento_judicial'  => $this->normalizarTextoNullable($persona['mandamiento_judicial'] ?? null),
                    'observaciones'         => isset($persona['observaciones']) && trim((string)$persona['observaciones']) !== ''
                                                ? strtoupper(trim((string)$persona['observaciones']))
                                                : null,
                ]);
            }

            foreach ($request->input('vehiculos', []) as $vehiculo) {
                if (
                    empty(trim((string)($vehiculo['placas'] ?? ''))) &&
                    empty(trim((string)($vehiculo['serie'] ?? ''))) &&
                    empty(trim((string)($vehiculo['marca'] ?? '')))
                ) {
                    continue;
                }

                PuestaDisposicionVehiculo::create([
                    'puesta_disposicion_id' => $puestaDisposicion->id,
                    'vehiculo_id'           => null,
                    'tipo'                  => $this->normalizarTextoNullable($vehiculo['tipo'] ?? null),
                    'marca'                 => $this->normalizarTextoNullable($vehiculo['marca'] ?? null),
                    'submarca'              => $this->normalizarTextoNullable($vehiculo['submarca'] ?? null),
                    'modelo'                => $this->normalizarTextoNullable($vehiculo['modelo'] ?? null),
                    'color'                 => $this->normalizarTextoNullable($vehiculo['color'] ?? null),
                    'placas'                => $this->normalizarTextoNullable($vehiculo['placas'] ?? null),
                    'serie'                 => $this->normalizarTextoNullable($vehiculo['serie'] ?? null),
                    'calidad'               => $this->normalizarTextoRequerido($vehiculo['calidad'] ?? ''),
                    'motivo_relacion'       => $this->normalizarTextoNullable($vehiculo['motivo_relacion'] ?? null),
                    'con_reporte_robo'      => !empty($vehiculo['con_reporte_robo']),
                    'numero_reporte_robo'   => $this->normalizarTextoNullable($vehiculo['numero_reporte_robo'] ?? null),
                    'observaciones'         => isset($vehiculo['observaciones']) && trim((string)$vehiculo['observaciones']) !== ''
                                                ? strtoupper(trim((string)$vehiculo['observaciones']))
                                                : null,
                ]);
            }

            foreach ($request->input('objetos', []) as $objeto) {
                if (
                    empty(trim((string)($objeto['tipo_objeto'] ?? ''))) &&
                    empty(trim((string)($objeto['descripcion'] ?? '')))
                ) {
                    continue;
                }

                PuestaDisposicionObjeto::create([
                    'puesta_disposicion_id' => $puestaDisposicion->id,
                    'tipo_objeto'           => $this->normalizarTextoRequerido($objeto['tipo_objeto'] ?? ''),
                    'descripcion'           => strtoupper(trim((string)($objeto['descripcion'] ?? ''))),
                    'cantidad'              => $objeto['cantidad'] ?? null,
                    'unidad_medida'         => $this->normalizarTextoNullable($objeto['unidad_medida'] ?? null),
                    'cadena_custodia'       => $this->normalizarTextoNullable($objeto['cadena_custodia'] ?? null),
                    'observaciones'         => isset($objeto['observaciones']) && trim((string)$objeto['observaciones']) !== ''
                                                ? strtoupper(trim((string)$objeto['observaciones']))
                                                : null,
                ]);
            }

            DB::commit();

            return redirect()->route('puestas_disposicion.index')
                ->with('success', 'Puesta a disposición actualizada exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al actualizar la puesta a disposición: ' . $e->getMessage());
        }
    }

    public function destroy(PuestaDisposicion $puestaDisposicion)
    {
        $usuario = auth()->user();

        $puestaDisposicion = $this->findVisibleOrFail($puestaDisposicion->id, $usuario);

        DB::beginTransaction();

        try {
            if ($puestaDisposicion->archivo_puesta && Storage::disk('public')->exists($puestaDisposicion->archivo_puesta)) {
                Storage::disk('public')->delete($puestaDisposicion->archivo_puesta);
            }

            $puestaDisposicion->personas()->delete();
            $puestaDisposicion->vehiculos()->delete();
            $puestaDisposicion->objetos()->delete();
            $puestaDisposicion->delete();

            DB::commit();

            return redirect()->route('puestas_disposicion.index')
                ->with('success', 'Puesta a disposición eliminada exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('puestas_disposicion.index')
                ->with('error', 'No se pudo eliminar la puesta a disposición: ' . $e->getMessage());
        }
    }
}
