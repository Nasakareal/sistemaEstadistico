<?php

namespace App\Http\Controllers;

use App\Models\PuestaDisposicion;
use App\Models\PuestaDisposicionPersona;
use App\Models\PuestaDisposicionVehiculo;
use App\Models\PuestaDisposicionObjeto;
use App\Models\Unidad;
use App\Models\Delegacion;
use App\Models\Hechos;
use App\Services\DelegacionesWhatsAppAlertService;
use App\Services\Documentos\DocumentoArchivoStorage;
use App\Support\HechoAccess;
use App\Support\PuestaDisposicionRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    private function mensajePuestaDebeSerVinculada(): string
    {
        return 'Selecciona el hecho turnado de Delegaciones para crear esta puesta vinculada.';
    }

    private function backConErrorHechoVinculadoRequerido()
    {
        return redirect()->back()
            ->withInput()
            ->withErrors([
                'hecho_id' => $this->mensajePuestaDebeSerVinculada(),
            ]);
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
            $delegacionIds = $this->delegacionIdsVisibles($usuario);

            if (empty($delegacionIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('delegacion_id', $delegacionIds);
            }
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

    private function delegacionIdsVisibles($usuario): array
    {
        $delegacionId = (int) ($usuario->delegacion_id ?? 0);

        if ($delegacionId <= 0) {
            return [];
        }

        if (!$usuario->hasRole('Delegado')) {
            return [$delegacionId];
        }

        $esRegional = Delegacion::query()
            ->where('id', $delegacionId)
            ->whereNull('delegacion_padre_id')
            ->exists();

        if (!$esRegional) {
            return [$delegacionId];
        }

        return Delegacion::query()
            ->where('id', $delegacionId)
            ->orWhere('delegacion_padre_id', $delegacionId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
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

    private function normalizarMotivoRequest(Request $request): string
    {
        $motivo = $request->input('motivo');

        if ($this->normalizarTextoRequerido($motivo) === PuestaDisposicionRules::MOTIVO_OTRO) {
            $motivo = $request->input('motivo_otro');
        }

        return $this->normalizarTextoRequerido($motivo);
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

    private function findHechoVisibleParaPuesta(int $hechoId, $usuario): Hechos
    {
        $query = Hechos::query()
            ->whereKey($hechoId)
            ->with(['creator', 'vehiculos.conductores', 'puestaDisposicion']);

        HechoAccess::applyVisibilityScope($query, $usuario);

        return $query->firstOrFail();
    }

    private function resolverHechoOrigen(Request $request, $usuario): ?Hechos
    {
        $hechoId = (int)$request->input('hecho_id');

        if ($hechoId <= 0) {
            return null;
        }

        return $this->findHechoVisibleParaPuesta($hechoId, $usuario);
    }

    private function unidadIdDesdeHecho(?Hechos $hecho): ?int
    {
        if (!$hecho) {
            return null;
        }

        $unidadId = (int)($hecho->unidad_org_id ?: optional($hecho->creator)->unidad_id);

        return $unidadId > 0 ? $unidadId : null;
    }

    private function tipoPuestaDesdeHecho(Hechos $hecho): string
    {
        $vehiculosMp = (int)($hecho->vehiculos_mp ?? 0);
        $personasMp = (int)($hecho->personas_mp ?? 0);

        if ($vehiculosMp > 0 && $personasMp > 0) {
            return 'MIXTA';
        }

        if ($vehiculosMp > 0) {
            return 'VEHICULO';
        }

        if ($personasMp > 0) {
            return 'PERSONA';
        }

        return 'MIXTA';
    }

    private function lugarPuestaDesdeHecho(Hechos $hecho): ?string
    {
        $partes = collect([$hecho->calle, $hecho->colonia, $hecho->municipio])
            ->map(fn ($parte) => trim((string)$parte))
            ->filter()
            ->values();

        return $partes->isEmpty() ? null : $partes->implode(', ');
    }

    private function vehiculosHechoPayload(Hechos $hecho): array
    {
        return $hecho->vehiculos->values()->map(function ($vehiculo, int $index) {
            $sourceKey = 'vehiculo:' . (int)$vehiculo->id;
            $linea = $vehiculo->linea ?? $vehiculo->submarca ?? null;
            $label = collect([
                $vehiculo->marca,
                $linea,
                $vehiculo->modelo,
                $vehiculo->placas ? 'PLACAS ' . $vehiculo->placas : null,
                $vehiculo->serie ? 'SERIE ' . $vehiculo->serie : null,
            ])->map(fn ($parte) => trim((string)$parte))->filter()->implode(' / ');

            return [
                'id' => (int)$vehiculo->id,
                'vehiculo_id' => (int)$vehiculo->id,
                'source_key' => $sourceKey,
                'label' => $label ?: 'Vehículo ' . ($index + 1),
                'tipo' => $vehiculo->tipo,
                'marca' => $vehiculo->marca,
                'submarca' => $linea,
                'modelo' => $vehiculo->modelo,
                'color' => $vehiculo->color,
                'placas' => $vehiculo->placas,
                'serie' => $vehiculo->serie,
                'calidad' => 'RELACIONADO',
                'motivo_relacion' => 'HECHO DE TRANSITO TURNADO',
                'con_reporte_robo' => !empty($vehiculo->antecedente_vehiculo),
                'observaciones' => $vehiculo->partes_danadas,
                'conductores' => $vehiculo->conductores->values()->map(function ($conductor, int $conductorIndex) use ($sourceKey, $label) {
                    $conductorSourceKey = $conductor->id
                        ? 'conductor:' . (int)$conductor->id
                        : $sourceKey . ':conductor:' . $conductorIndex;

                    return [
                        'id' => (int)$conductor->id,
                        'source_key' => $conductorSourceKey,
                        'label' => trim((string)$conductor->nombre) !== ''
                            ? $conductor->nombre . ' - ' . $label
                            : $label,
                        'nombre_completo' => $conductor->nombre,
                        'edad' => $conductor->edad,
                        'sexo' => $conductor->sexo,
                        'domicilio' => $conductor->domicilio,
                        'calidad' => 'CONDUCTOR',
                        'delito_o_motivo' => 'HECHO DE TRANSITO TURNADO',
                    ];
                })->all(),
            ];
        })->all();
    }

    private function hechosTurnadosDisponiblesPayload($usuario): array
    {
        $query = Hechos::query()
            ->with(['creator:id,name,unidad_id', 'delegacion:id,nombre,nombre_con_clave,municipio'])
            ->where('situacion', 'TURNADO')
            ->whereDoesntHave('puestaDisposicion');

        HechoAccess::applyVisibilityScope($query, $usuario);
        HechoAccess::applyUnidadScope($query, PuestaDisposicionRules::UNIDAD_DELEGACIONES_ID);

        return $query
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->orderByDesc('id')
            ->limit(500)
            ->get()
            ->map(function (Hechos $hecho) use ($usuario) {
                $fecha = $hecho->fecha ? $hecho->fecha->format('Y-m-d') : '';
                $hora = $hecho->hora ? substr((string)$hecho->hora, 0, 5) : '';
                $delegacion = optional($hecho->delegacion)->nombre_con_clave
                    ?: optional($hecho->delegacion)->nombre
                    ?: optional($hecho->delegacion)->municipio;

                $partesLabel = [
                    '#' . $hecho->id,
                    $fecha,
                    $hora,
                    $hecho->folio_c5i ? 'Folio ' . $hecho->folio_c5i : null,
                    $delegacion,
                    $hecho->tipo_hecho,
                ];

                return [
                    'id' => (int)$hecho->id,
                    'label' => collect($partesLabel)
                        ->map(fn ($parte) => trim((string)$parte))
                        ->filter()
                        ->implode(' · '),
                    'tipo_puesta' => $this->tipoPuestaDesdeHecho($hecho),
                    'fecha_puesta' => $fecha,
                    'hora_puesta' => $hora,
                    'lugar_puesta' => $this->lugarPuestaDesdeHecho($hecho),
                    'nombre_policia' => $hecho->perito ?: ($usuario->name ?? ''),
                    'oficio' => $hecho->folio_c5i,
                ];
            })
            ->all();
    }

    private function resolverVehiculoRelacionadoId(?Hechos $hecho, array $vehiculo): ?int
    {
        if (!$hecho) {
            return null;
        }

        $hecho->loadMissing('vehiculos');
        $vehiculos = $hecho->vehiculos;
        $vehiculoId = (int)($vehiculo['vehiculo_id'] ?? 0);

        if ($vehiculoId > 0 && $vehiculos->contains('id', $vehiculoId)) {
            return $vehiculoId;
        }

        $sourceKey = trim((string)($vehiculo['source_key'] ?? ''));
        if (preg_match('/^vehiculo:(\d+)$/', $sourceKey, $matches)) {
            $sourceVehiculoId = (int)$matches[1];
            if ($vehiculos->contains('id', $sourceVehiculoId)) {
                return $sourceVehiculoId;
            }
        }

        $serie = strtoupper(trim((string)($vehiculo['serie'] ?? '')));
        if ($serie !== '') {
            $match = $vehiculos->first(fn ($item) => strtoupper(trim((string)$item->serie)) === $serie);
            if ($match) {
                return (int)$match->id;
            }
        }

        $placas = strtoupper(trim((string)($vehiculo['placas'] ?? '')));
        if ($placas !== '') {
            $match = $vehiculos->first(fn ($item) => strtoupper(trim((string)$item->placas)) === $placas);
            if ($match) {
                return (int)$match->id;
            }
        }

        return null;
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

    public function create(Request $request)
    {
        $usuario = auth()->user();
        $hechoId = (int)$request->query('hecho_id');
        $hechoOrigen = null;

        if ($hechoId > 0) {
            $hechoOrigen = $this->findHechoVisibleParaPuesta($hechoId, $usuario);

            if ($hechoOrigen->puestaDisposicion) {
                return redirect()->route('puestas_disposicion.show', $hechoOrigen->puestaDisposicion->id)
                    ->with('success', 'Este hecho ya tiene una puesta a disposición vinculada.');
            }
        }

        $anioActual = now()->year;
        $unidades = $this->obtenerUnidadesActivas();
        $puedeSeleccionarUnidad = $this->puedeSeleccionarUnidadRegistro($usuario);
        $unidadOrigenId = $this->unidadIdDesdeHecho($hechoOrigen);

        $unidadSeleccionadaId = (int)old(
            'unidad_id',
            $unidadOrigenId ?: ($usuario->unidad_id ?: optional($unidades->first())->id)
        );

        $unidadNombre = $this->obtenerNombreUnidad($unidadSeleccionadaId);
        $tipoPuestaDefault = $hechoOrigen ? $this->tipoPuestaDesdeHecho($hechoOrigen) : 'PERSONA';
        $motivoDefault = $hechoOrigen ? 'HECHO DE TRANSITO TURNADO' : null;
        $lugarPuestaDefault = $hechoOrigen ? $this->lugarPuestaDesdeHecho($hechoOrigen) : null;
        $nombrePoliciaDefault = $hechoOrigen ? ($hechoOrigen->perito ?: ($usuario->name ?? '')) : ($usuario->name ?? '');
        $oficioDefault = $hechoOrigen ? $hechoOrigen->folio_c5i : null;
        $fechaPuestaDefault = $hechoOrigen && $hechoOrigen->fecha
            ? $hechoOrigen->fecha->format('Y-m-d')
            : now()->toDateString();
        $horaPuestaDefault = $hechoOrigen && $hechoOrigen->hora ? substr((string)$hechoOrigen->hora, 0, 5) : null;
        $vehiculosHechoPuesta = $hechoOrigen ? $this->vehiculosHechoPayload($hechoOrigen) : [];
        $motivosPuestaOptions = PuestaDisposicionRules::motivosCatalogo();
        $hechosTurnadosDisponibles = $hechoOrigen ? [] : $this->hechosTurnadosDisponiblesPayload($usuario);
        $unidadDelegacionesId = PuestaDisposicionRules::UNIDAD_DELEGACIONES_ID;

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
            'numerosSiguientesPorUnidad',
            'hechoOrigen',
            'tipoPuestaDefault',
            'motivoDefault',
            'lugarPuestaDefault',
            'nombrePoliciaDefault',
            'oficioDefault',
            'fechaPuestaDefault',
            'horaPuestaDefault',
            'vehiculosHechoPuesta',
            'motivosPuestaOptions',
            'hechosTurnadosDisponibles',
            'unidadDelegacionesId'
        ));
    }

    public function store(Request $request)
    {
        $usuario = auth()->user();
        $hechoOrigen = $this->resolverHechoOrigen($request, $usuario);
        if ($hechoOrigen && PuestaDisposicion::query()->where('hecho_id', $hechoOrigen->id)->exists()) {
            throw ValidationException::withMessages([
                'hecho_id' => 'Este hecho ya tiene una puesta a disposición vinculada.',
            ]);
        }

        $unidadRegistroId = $this->unidadIdDesdeHecho($hechoOrigen)
            ?: $this->resolverUnidadRegistro($request, $usuario);

        $request->merge([
            'hecho_id'              => $hechoOrigen ? $hechoOrigen->id : null,
            'unidad_id'             => $unidadRegistroId,
            'tipo_puesta'           => $this->normalizarTextoRequerido($request->input('tipo_puesta')),
            'motivo'                => $this->normalizarMotivoRequest($request),
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

        if (PuestaDisposicionRules::requiereHechoVinculadoDelegaciones(
            $unidadRegistroId,
            $request->input('motivo'),
            $hechoOrigen !== null
        )) {
            return $this->backConErrorHechoVinculadoRequerido();
        }

        $request->validate([
            'hecho_id'              => 'nullable|integer|exists:hechos,id',
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
            'archivo_puesta'        => 'nullable|file|mimes:pdf|max:20480',

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
            'vehiculos.*.vehiculo_id'           => 'nullable|integer|exists:vehiculos,id',
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
                $archivoPuesta = $this->documentos()->putUploadedFile($request->file('archivo_puesta'), 'puestas_disposicion');
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
                'hecho_id'              => $hechoOrigen ? $hechoOrigen->id : null,
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
                'delegacion_id'         => $hechoOrigen ? ($hechoOrigen->delegacion_id ?: $usuario->delegacion_id) : $usuario->delegacion_id,
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
                    'vehiculo_id'           => $this->resolverVehiculoRelacionadoId($hechoOrigen, $vehiculo),
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

            app(DelegacionesWhatsAppAlertService::class)->notificarPuestaDisposicion($puesta);

            $redirect = $hechoOrigen
                ? redirect()->route('hechos.show', $hechoOrigen->id)
                : redirect()->route('puestas_disposicion.index');

            return $redirect
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

    public function archivo(PuestaDisposicion $puestaDisposicion)
    {
        $usuario = auth()->user();

        $puestaDisposicion = $this->findVisibleOrFail($puestaDisposicion->id, $usuario);

        abort_unless($puestaDisposicion->archivo_puesta, 404);

        $nombre = 'puesta_disposicion_' . $puestaDisposicion->numero_puesta . '_' . $puestaDisposicion->anio . '.pdf';

        return $this->documentos()->response($puestaDisposicion->archivo_puesta, $nombre);
    }

    public function edit(PuestaDisposicion $puestaDisposicion)
    {
        $usuario = auth()->user();

        $puestaDisposicion = $this->findVisibleOrFail($puestaDisposicion->id, $usuario);
        $motivosPuestaOptions = PuestaDisposicionRules::motivosCatalogo();

        return view('puestas_disposicion.edit', compact('puestaDisposicion', 'motivosPuestaOptions'));
    }

    public function update(Request $request, PuestaDisposicion $puestaDisposicion)
    {
        $usuario = auth()->user();

        $puestaDisposicion = $this->findVisibleOrFail($puestaDisposicion->id, $usuario);
        $hechoOrigen = $puestaDisposicion->hecho_id
            ? $puestaDisposicion->hecho()->with('vehiculos')->first()
            : null;

        $request->merge([
            'tipo_puesta'           => $this->normalizarTextoRequerido($request->input('tipo_puesta')),
            'motivo'                => $this->normalizarMotivoRequest($request),
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
            'archivo_puesta'        => 'nullable|file|mimes:pdf|max:20480',

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
            'vehiculos.*.vehiculo_id'           => 'nullable|integer|exists:vehiculos,id',
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

        $unidadActualizadaId = $this->esSuperadmin($usuario)
            ? (int)$request->input('unidad_id', $puestaDisposicion->unidad_id)
            : (int)$puestaDisposicion->unidad_id;

        if (PuestaDisposicionRules::requiereHechoVinculadoDelegaciones(
            $unidadActualizadaId,
            $request->input('motivo'),
            $hechoOrigen !== null
        )) {
            return $this->backConErrorHechoVinculadoRequerido();
        }

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
            $archivoPuestaAnterior = $archivoPuesta;

            if ($request->hasFile('archivo_puesta')) {
                $archivoPuesta = $this->documentos()->putUploadedFile($request->file('archivo_puesta'), 'puestas_disposicion');
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

            if ($request->hasFile('archivo_puesta')
                && $archivoPuestaAnterior
                && $archivoPuestaAnterior !== $archivoPuesta) {
                $this->documentos()->delete($archivoPuestaAnterior);
            }

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
                    'vehiculo_id'           => $this->resolverVehiculoRelacionadoId($hechoOrigen, $vehiculo),
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

            $redirect = $puestaDisposicion->hecho_id
                ? redirect()->route('hechos.show', $puestaDisposicion->hecho_id)
                : redirect()->route('puestas_disposicion.index');

            return $redirect
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
            $archivo = $puestaDisposicion->archivo_puesta;

            $puestaDisposicion->personas()->delete();
            $puestaDisposicion->vehiculos()->delete();
            $puestaDisposicion->objetos()->delete();
            $puestaDisposicion->delete();

            if ($archivo) {
                $this->documentos()->delete($archivo);
            }

            DB::commit();

            return redirect()->route('puestas_disposicion.index')
                ->with('success', 'Puesta a disposición eliminada exitosamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->route('puestas_disposicion.index')
                ->with('error', 'No se pudo eliminar la puesta a disposición: ' . $e->getMessage());
        }
    }

    private function documentos(): DocumentoArchivoStorage
    {
        return app(DocumentoArchivoStorage::class);
    }
}
