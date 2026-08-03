<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaActivacion;
use App\Models\ConstanciaExamen;
use App\Models\ConstanciaFolio;
use App\Models\ConstanciaManejo;
use App\Models\ConstanciaModulo;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConstanciaManejoController extends Controller
{
    private const TIPOS_LICENCIA = [
        'SERVICIO_PUBLICO' => 'Servicio publico',
        'AUTOMOVILISTA' => 'Automovilista',
        'CHOFER' => 'Chofer',
        'MOTOCICLISTA' => 'Motociclista',
        'PERMISO' => 'Permiso',
    ];

    private function queryModulosDisponibles()
    {
        $usuario = auth()->user();

        $query = ConstanciaModulo::where('activo', true)->orderBy('nombre');

        if (!$usuario) {
            return $query->whereRaw('1 = 0');
        }

        if ($usuario->hasRole('Superadmin')) {
            return $query;
        }

        $unidadIds = $this->unidadIdsUsuario($usuario);
        $delegacionIds = $this->delegacionIdsUsuario($usuario);
        $puedeSiniestros = in_array(1, $unidadIds, true);
        $puedeDelegacion = in_array(2, $unidadIds, true) && count($delegacionIds) > 0;

        if (!$puedeSiniestros && !$puedeDelegacion) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($puedeSiniestros, $puedeDelegacion, $delegacionIds) {
            if ($puedeSiniestros) {
                $q->orWhere('tipo', 'SINIESTROS');
            }

            if ($puedeDelegacion) {
                $q->orWhere(function ($delegacion) use ($delegacionIds) {
                    $delegacion->where('tipo', 'DELEGACION')
                        ->whereIn('delegacion_id', $delegacionIds);
                });
            }
        });
    }

    private function queryConstanciasDisponibles()
    {
        return ConstanciaManejo::query()
            ->whereIn('modulo_id', $this->queryModulosDisponibles()->pluck('id'));
    }

    private function authorizeConstancia(ConstanciaManejo $constancia): void
    {
        abort_unless(
            $this->queryConstanciasDisponibles()->whereKey($constancia->id)->exists(),
            403,
            'No tienes permiso para ver esta constancia.'
        );
    }

    private function unidadIdsUsuario($usuario): array
    {
        return array_values(array_filter([(int) ($usuario->unidad_id ?? 0)]));
    }

    private function delegacionIdsUsuario($usuario): array
    {
        $ids = [(int) ($usuario->delegacion_id ?? 0)];

        try {
            $ids = array_merge(
                $ids,
                DB::table('delegacion_user')
                    ->where('user_id', $usuario->id)
                    ->pluck('delegacion_id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            );
        } catch (\Throwable $e) {
            // La delegacion principal basta en instalaciones sin pivote sincronizado.
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function index(Request $request)
    {
        $query = $this->queryConstanciasDisponibles()
            ->with(['modulo', 'usuario', 'peritoActivador', 'examen'])
            ->orderByDesc('id');

        $this->aplicarFiltrosModulo($query, $request);

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->estatus);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', "%{$buscar}%")
                    ->orWhere('nombre_solicitante', 'like', "%{$buscar}%")
                    ->orWhere('curp', 'like', "%{$buscar}%");
            });
        }

        $constancias = $query
            ->paginate(25, ['*'], 'pagina_constancias')
            ->appends($request->query());

        $lotesQuery = $this->queryConstanciasDisponibles()
            ->with(['modulo:id,nombre,tipo', 'usuario:id,name'])
            ->whereNotNull('lote_uuid');

        $this->aplicarFiltrosModulo($lotesQuery, $request);

        $lotes = $lotesQuery
            ->selectRaw('MIN(id) as id, lote_uuid, modulo_id, user_id, COUNT(*) as cantidad, MIN(folio) as folio_inicial, MAX(folio) as folio_final, MIN(COALESCE(fecha_impresion, created_at)) as fecha_generacion')
            ->groupBy(['lote_uuid', 'modulo_id', 'user_id'])
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'pagina_lotes')
            ->appends($request->query());

        $modulosFiltro = $this->queryModulosDisponibles()->get(['id', 'nombre', 'tipo']);
        $tipoModulo = in_array($request->query('tipo_modulo'), ['SINIESTROS', 'DELEGACION'], true)
            ? $request->query('tipo_modulo')
            : null;
        $isSuperadmin = (bool) optional(auth()->user())->hasRole('Superadmin');

        return view('constancias_manejo.index', compact(
            'constancias',
            'lotes',
            'modulosFiltro',
            'tipoModulo',
            'isSuperadmin'
        ));
    }

    private function aplicarFiltrosModulo($query, Request $request): void
    {
        $tipoModulo = $request->query('tipo_modulo');

        if (in_array($tipoModulo, ['SINIESTROS', 'DELEGACION'], true)) {
            $query->whereHas('modulo', fn ($modulo) => $modulo->where('tipo', $tipoModulo));
        }

        if ($request->filled('modulo_id')) {
            $query->where('modulo_id', (int) $request->query('modulo_id'));
        }
    }

    public function create()
    {
        $modulos = $this->queryModulosDisponibles()->get();
        $tiposModuloDisponibles = $modulos
            ->pluck('tipo')
            ->unique()
            ->values();

        return view('constancias_manejo.create', compact('modulos', 'tiposModuloDisponibles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tipo_modulo' => ['required', 'in:SINIESTROS,DELEGACION'],
            'modulo_id' => ['required', 'exists:constancia_modulos,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $moduloPermitido = $this->queryModulosDisponibles()
            ->where('id', $request->modulo_id)
            ->first();

        if (!$moduloPermitido) {
            return redirect()
                ->route('constancias_manejo.create')
                ->withInput()
                ->withErrors(['modulo_id' => 'No tienes permiso para generar constancias en este módulo.']);
        }

        if ($moduloPermitido->tipo !== $request->tipo_modulo) {
            return redirect()
                ->route('constancias_manejo.create')
                ->withInput()
                ->withErrors(['modulo_id' => 'El módulo seleccionado no pertenece al origen indicado.']);
        }

        $loteUuid = (string) Str::uuid();
        $constancias = DB::transaction(function () use ($request, $loteUuid) {
            return $this->crearConstancias((int) $request->modulo_id, (int) $request->cantidad, $loteUuid);
        });

        return redirect()
            ->route('constancias_manejo.imprimir_lote', ['ids' => implode(',', $constancias)])
            ->with('success', count($constancias) . ' constancias generadas como inactivas.');
    }

    private function crearConstancias(int $moduloId, int $cantidad, string $loteUuid): array
    {
        $modulo = ConstanciaModulo::findOrFail($moduloId);
        $origen = $modulo->tipo === 'SINIESTROS' ? 'SINIESTROS' : 'DELEGACIONES';
        $prefijo = $modulo->tipo === 'SINIESTROS' ? 'S' : 'D';
        $creadas = [];

        $ultimoNumero = ConstanciaFolio::where('prefijo', $prefijo)
            ->lockForUpdate()
            ->max('numero');

        $numeroInicial = $ultimoNumero ? $ultimoNumero + 1 : 1;

        for ($i = 0; $i < $cantidad; $i++) {
            $numero = $numeroInicial + $i;
            $folio = $prefijo . '-' . str_pad($numero, 4, '0', STR_PAD_LEFT);

            $constancia = ConstanciaManejo::create([
                'folio' => $folio,
                'folio_qr' => $folio,
                'modulo_id' => $modulo->id,
                'delegacion_id' => $modulo->delegacion_id,
                'user_id' => auth()->id(),
                'perito_activador_id' => null,
                'nombre_solicitante' => null,
                'sexo' => null,
                'curp' => null,
                'telefono' => null,
                'tipo_licencia' => null,
                'tipo_examen' => null,
                'estatus' => 'IMPRESA_INACTIVA',
                'fecha_impresion' => Carbon::now('America/Mexico_City'),
                'fecha_activacion' => null,
                'fecha_expiracion' => null,
                'pdf_path' => null,
                'lote_uuid' => $loteUuid,
                'qr_token' => Str::uuid()->toString(),
                'acceso_examen_token' => null,
                'acceso_examen_expira' => null,
            ]);

            ConstanciaFolio::create([
                'prefijo' => $prefijo,
                'numero' => $numero,
                'folio' => $folio,
                'origen' => $origen,
                'modulo_id' => $modulo->id,
                'delegacion_id' => $modulo->delegacion_id,
                'constancia_id' => $constancia->id,
                'estatus' => 'ASIGNADO',
            ]);

            $creadas[] = $constancia->id;
        }

        return $creadas;
    }

    public function show(ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);
        $constancia->load(['modulo', 'usuario', 'peritoActivador', 'examen', 'activaciones.usuario']);

        return view('constancias_manejo.show', compact('constancia'));
    }

    public function edit(ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);

        if ($constancia->estatus === 'ACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'No se puede editar una constancia activa.');
        }

        $modulos = $this->queryModulosDisponibles()->get();

        return view('constancias_manejo.edit', compact('constancia', 'modulos'));
    }

    public function update(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);

        if ($constancia->estatus === 'ACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'No se puede editar una constancia activa.');
        }

        $request->validate([
            'modulo_id' => ['required', 'exists:constancia_modulos,id'],
            'nombre_solicitante' => ['nullable', 'string', 'max:255'],
            'sexo' => ['nullable', 'in:HOMBRE,MUJER'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo_licencia' => ['nullable', 'in:SERVICIO_PUBLICO,AUTOMOVILISTA,CHOFER,MOTOCICLISTA,PERMISO'],
            'tipo_examen' => ['nullable', 'in:LINEA,IMPRESO'],
        ]);

        $moduloPermitido = $this->queryModulosDisponibles()
            ->where('id', $request->modulo_id)
            ->exists();

        if (!$moduloPermitido) {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'No tienes permiso para mover esta constancia a ese modulo.');
        }

        $modulo = ConstanciaModulo::findOrFail($request->modulo_id);

        $constancia->update([
            'modulo_id' => $modulo->id,
            'delegacion_id' => $modulo->delegacion_id,
            'nombre_solicitante' => $request->nombre_solicitante ? mb_strtoupper($request->nombre_solicitante, 'UTF-8') : null,
            'sexo' => $request->sexo,
            'curp' => $request->curp ? mb_strtoupper($request->curp, 'UTF-8') : null,
            'telefono' => $request->telefono,
            'tipo_licencia' => $request->tipo_licencia,
            'tipo_examen' => $request->tipo_examen,
        ]);

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Constancia actualizada.');
    }

    public function destroy(ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);

        if ($constancia->estatus === 'ACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'No se puede eliminar una constancia activa.');
        }

        $constancia->delete();

        return redirect()->route('constancias_manejo.index')->with('success', 'Constancia eliminada.');
    }

    public function imprimir(ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);
        $constancia->load(['modulo', 'examen']);
        $qrBase64 = $constancia->qrDataUri();
        $qrUrl = $constancia->qrUrl();

        return view('constancias_manejo.imprimir', compact('constancia', 'qrBase64', 'qrUrl'));
    }

    public function imprimirLote(Request $request)
    {
        $ids = $this->idsFromRequest($request);
        $constancias = $this->queryConstanciasDisponibles()
            ->with(['modulo', 'examen'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($constancia) => array_search($constancia->id, $ids, true))
            ->values();

        abort_if($constancias->count() !== count($ids), 403, 'No tienes permiso para imprimir una o mas constancias del lote.');

        $loteUuids = $constancias->pluck('lote_uuid')->filter()->unique()->values();

        return view('constancias_manejo.imprimir', [
            'constancias' => $constancias,
            'autoPrint' => true,
            'loteIds' => $ids,
            'loteUuid' => $loteUuids->count() === 1 ? $loteUuids->first() : null,
        ]);
    }

    public function descargarLote(string $lote)
    {
        $constancias = $this->queryConstanciasDisponibles()
            ->with(['modulo', 'examen'])
            ->where('lote_uuid', $lote)
            ->orderBy('id')
            ->get();

        abort_if($constancias->isEmpty(), 404, 'No se encontró el lote o no tienes permiso para descargarlo.');

        $logoDataUri = $this->imagenDataUri(public_path('img/michoacan_vertical.png'));
        $primera = $constancias->first();
        $ultima = $constancias->last();
        $nombreModulo = optional($primera->modulo)->nombre ?: 'modulo';
        $nombreArchivo = sprintf(
            'lote_constancias_%s_%s_a_%s.pdf',
            Str::slug($nombreModulo, '_'),
            Str::slug($primera->folio, '_'),
            Str::slug($ultima->folio, '_')
        );

        return Pdf::loadView('constancias_manejo.lote_pdf', compact('constancias', 'logoDataUri'))
            ->setPaper('letter', 'portrait')
            ->download($nombreArchivo);
    }

    private function imagenDataUri(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = $extension === 'jpg' || $extension === 'jpeg' ? 'image/jpeg' : 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    public function imprimirLoteFirmado(Request $request)
    {
        $ids = $this->idsFromRequest($request);
        $constancias = ConstanciaManejo::with(['modulo', 'examen'])
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($constancia) => array_search($constancia->id, $ids, true))
            ->values();

        abort_if($constancias->count() !== count($ids), 404);

        return view('constancias_manejo.imprimir', [
            'constancias' => $constancias,
            'autoPrint' => true,
            'loteIds' => $ids,
        ]);
    }

    private function idsFromRequest(Request $request): array
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        abort_if(count($ids) === 0, 404, 'No se encontraron constancias para imprimir.');
        abort_if(count($ids) > 100, 422, 'Solo se pueden imprimir hasta 100 constancias por lote.');

        return $ids;
    }

    public function reimprimir(ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);
        $constancia->load(['modulo', 'examen']);
        $qrBase64 = $constancia->qrDataUri();
        $qrUrl = $constancia->qrUrl();

        ConstanciaActivacion::create([
            'constancia_id' => $constancia->id,
            'user_id' => auth()->id(),
            'accion' => 'REIMPRESA',
            'fecha' => Carbon::now('America/Mexico_City'),
            'observaciones' => null,
        ]);

        return view('constancias_manejo.imprimir', compact('constancia', 'qrBase64', 'qrUrl'));
    }

    public function generarAcceso(ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);

        return redirect()
            ->route('constancias_manejo.examenes.create')
            ->with('error', 'Los exámenes se generan aparte y no consumen constancias impresas.');
    }

    public function capturarExamenImpreso(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);

        return redirect()
            ->route('constancias_manejo.examenes.create')
            ->with('error', 'Captura el examen como registro independiente y actívalo después con una constancia impresa.');
    }

    public function activar(ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);
        $constancia->load('examen');

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'La constancia no esta inactiva.');
        }

        if (!$constancia->tieneDatosMinimosActivacion()) {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Faltan datos del solicitante, sexo o tipo de licencia.');
        }

        $examenAprobado = $constancia->tieneExamenAprobado();
        $activacionDirecta = $constancia->puedeActivarDirectamente();
        if (!$examenAprobado && !$activacionDirecta) {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'No se puede activar sin examen aprobado.');
        }

        $ahora = Carbon::now('America/Mexico_City');

        $constancia->update([
            'estatus' => 'ACTIVA',
            'perito_activador_id' => auth()->id(),
            'fecha_activacion' => $ahora,
            'fecha_expiracion' => $ahora->copy()->addDays(10),
            'acceso_examen_token' => null,
            'acceso_examen_expira' => null,
        ]);

        ConstanciaActivacion::create([
            'constancia_id' => $constancia->id,
            'user_id' => auth()->id(),
            'accion' => 'ACTIVADA',
            'fecha' => $ahora,
            'observaciones' => $activacionDirecta ? 'Activada sin examen asociado.' : null,
        ]);

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Constancia activada.');
    }

    public function activarManualForm(Request $request)
    {
        return view('constancias_manejo.activar_manual', [
            'folio' => $request->query('folio'),
            'tiposLicencia' => self::TIPOS_LICENCIA,
            'constanciasLote' => $this->constanciasPermitidasDelLote($request),
        ]);
    }

    public function activarManual(Request $request)
    {
        $request->merge([
            'sexo' => $this->normalizarSexo($request->input('sexo')),
        ]);

        $validated = $request->validate([
            'folio' => ['required', 'string', 'max:50'],
            'nombre_solicitante' => ['required', 'string', 'max:255'],
            'sexo' => ['required', 'in:HOMBRE,MUJER'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo_licencia' => ['required', 'in:' . implode(',', array_keys(self::TIPOS_LICENCIA))],
        ]);

        $folio = $this->normalizarFolio($validated['folio']);
        $constancia = $this->queryConstanciasDisponibles()
            ->with('examen')
            ->where(function ($query) use ($folio) {
                $query->where('folio', $folio)
                    ->orWhere('folio_qr', $folio);
            })
            ->first();

        if (!$constancia) {
            return redirect()
                ->route('constancias_manejo.activar_manual')
                ->withInput()
                ->with('error', 'Constancia no encontrada en tus modulos permitidos.');
        }

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()
                ->route('constancias_manejo.activar_manual', ['folio' => $folio])
                ->withInput()
                ->with('error', 'La constancia no esta inactiva.');
        }

        if (!$constancia->puedeActivarDirectamente()) {
            return redirect()
                ->route('constancias_manejo.activar_manual', ['folio' => $folio])
                ->withInput()
                ->with('error', 'Esta constancia tiene examen o acceso asociado; activala desde Examenes de Manejo.');
        }

        $ahora = Carbon::now('America/Mexico_City');

        DB::transaction(function () use ($constancia, $validated, $ahora) {
            $constancia->update([
                'nombre_solicitante' => mb_strtoupper($validated['nombre_solicitante'], 'UTF-8'),
                'sexo' => $validated['sexo'],
                'curp' => !empty($validated['curp']) ? mb_strtoupper($validated['curp'], 'UTF-8') : null,
                'telefono' => $validated['telefono'] ?? null,
                'tipo_licencia' => $validated['tipo_licencia'],
                'estatus' => 'ACTIVA',
                'perito_activador_id' => auth()->id(),
                'fecha_activacion' => $ahora,
                'fecha_expiracion' => $ahora->copy()->addDays(10),
                'acceso_examen_token' => null,
                'acceso_examen_expira' => null,
            ]);

            ConstanciaActivacion::create([
                'constancia_id' => $constancia->id,
                'user_id' => auth()->id(),
                'accion' => 'ACTIVADA',
                'fecha' => $ahora,
                'observaciones' => 'Activada manualmente sin examen asociado.',
            ]);
        });

        return redirect()
            ->route('constancias_manejo.show', $constancia)
            ->with('success', 'Constancia activada manualmente.');
    }

    public function cancelar(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);

        $request->validate([
            'observaciones' => ['nullable', 'string'],
        ]);

        if ($constancia->estatus === 'CANCELADA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'La constancia ya esta cancelada.');
        }

        $constancia->update([
            'estatus' => 'CANCELADA',
            'acceso_examen_token' => null,
            'acceso_examen_expira' => null,
        ]);

        ConstanciaActivacion::create([
            'constancia_id' => $constancia->id,
            'user_id' => auth()->id(),
            'accion' => 'CANCELADA',
            'fecha' => Carbon::now('America/Mexico_City'),
            'observaciones' => $request->observaciones,
        ]);

        return redirect()->route('constancias_manejo.index')->with('success', 'Constancia cancelada.');
    }

    public function pendientesActivar()
    {
        $constancias = $this->queryConstanciasDisponibles()
            ->with(['modulo', 'examen'])
            ->where('estatus', 'IMPRESA_INACTIVA')
            ->where(function ($query) {
                $query->whereHas('examen', function ($examen) {
                    $examen->where('resultado', 'APROBADO');
                })->orWhere(function ($directa) {
                    $directa->whereDoesntHave('examen')
                        ->whereNull('acceso_examen_token')
                        ->whereNotNull('nombre_solicitante')
                        ->whereNotNull('sexo')
                        ->whereNotNull('tipo_licencia');
                });
            })
            ->orderByDesc('id')
            ->paginate(25);

        return view('constancias_manejo.pendientes_activar', compact('constancias'));
    }

    public function inactivasVencidas()
    {
        $constancias = $this->queryConstanciasDisponibles()
            ->with(['modulo', 'examen'])
            ->where('estatus', 'IMPRESA_INACTIVA')
            ->whereNotNull('acceso_examen_expira')
            ->where('acceso_examen_expira', '<', Carbon::now('America/Mexico_City'))
            ->orderByDesc('id')
            ->paginate(25);

        return view('constancias_manejo.inactivas_vencidas', compact('constancias'));
    }

    private function constanciasPermitidasDelLote(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (count($ids) === 0) {
            return collect();
        }

        return $this->queryConstanciasDisponibles()
            ->with('modulo')
            ->whereIn('id', $ids)
            ->where('estatus', 'IMPRESA_INACTIVA')
            ->get()
            ->sortBy(fn ($constancia) => array_search($constancia->id, $ids, true))
            ->values();
    }

    private function normalizarFolio(string $folio): string
    {
        return mb_strtoupper(trim($folio), 'UTF-8');
    }

    private function normalizarSexo($sexo): ?string
    {
        $value = mb_strtoupper(trim((string) $sexo), 'UTF-8');

        if (in_array($value, ['H', 'HOMBRE', 'MASCULINO'], true)) {
            return 'HOMBRE';
        }

        if (in_array($value, ['M', 'MUJER', 'FEMENINO'], true)) {
            return 'MUJER';
        }

        return $value ?: null;
    }
}
