<?php

namespace App\Http\Controllers;

use App\Models\ConstanciaActivacion;
use App\Models\ConstanciaExamen;
use App\Models\ConstanciaFolio;
use App\Models\ConstanciaManejo;
use App\Models\ConstanciaModulo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConstanciaManejoController extends Controller
{
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

        $constancias = $query->paginate(25);

        return view('constancias_manejo.index', compact('constancias'));
    }

    public function create()
    {
        $modulos = $this->queryModulosDisponibles()->get();

        return view('constancias_manejo.create', compact('modulos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'modulo_id' => ['required', 'exists:constancia_modulos,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $moduloPermitido = $this->queryModulosDisponibles()
            ->where('id', $request->modulo_id)
            ->exists();

        if (!$moduloPermitido) {
            return redirect()->route('constancias_manejo.create')->with('error', 'No tienes permiso para generar constancias en este modulo.');
        }

        $constancias = DB::transaction(function () use ($request) {
            return $this->crearConstancias((int) $request->modulo_id, (int) $request->cantidad);
        });

        return redirect()
            ->route('constancias_manejo.imprimir_lote', ['ids' => implode(',', $constancias)])
            ->with('success', count($constancias) . ' constancias generadas como inactivas.');
    }

    private function crearConstancias(int $moduloId, int $cantidad): array
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

        return view('constancias_manejo.imprimir', [
            'constancias' => $constancias,
            'autoPrint' => true,
        ]);
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

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Solo se puede generar acceso a constancias inactivas.');
        }

        $constancia->update([
            'tipo_examen' => 'LINEA',
            'acceso_examen_token' => Str::random(60),
            'acceso_examen_expira' => Carbon::now('America/Mexico_City')->addMinutes(30),
        ]);

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Acceso temporal generado.');
    }

    public function capturarExamenImpreso(Request $request, ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Solo se puede capturar examen de constancias inactivas.');
        }

        $request->validate([
            'nombre_solicitante' => ['required', 'string', 'max:255'],
            'sexo' => ['required', 'in:HOMBRE,MUJER'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo_licencia' => ['required', 'in:SERVICIO_PUBLICO,AUTOMOVILISTA,CHOFER,MOTOCICLISTA,PERMISO'],
            'calificacion' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'total_preguntas' => ['required', 'integer', 'min:1'],
            'aciertos' => ['required', 'integer', 'min:0'],
            'errores' => ['required', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        if ((int) $request->aciertos + (int) $request->errores !== (int) $request->total_preguntas) {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Los aciertos y errores no coinciden con el total de preguntas.');
        }

        $calificacion = round(((int) $request->aciertos / (int) $request->total_preguntas) * 100, 2);
        $resultado = $calificacion >= 80 ? 'APROBADO' : 'REPROBADO';

        DB::transaction(function () use ($request, $constancia, $resultado, $calificacion) {
            $constancia->update([
                'nombre_solicitante' => mb_strtoupper($request->nombre_solicitante, 'UTF-8'),
                'sexo' => $request->sexo,
                'curp' => $request->curp ? mb_strtoupper($request->curp, 'UTF-8') : null,
                'telefono' => $request->telefono,
                'tipo_licencia' => $request->tipo_licencia,
                'tipo_examen' => 'IMPRESO',
                'acceso_examen_token' => null,
                'acceso_examen_expira' => null,
            ]);

            ConstanciaExamen::updateOrCreate(
                [
                    'constancia_id' => $constancia->id,
                ],
                [
                    'modalidad' => 'IMPRESO',
                    'calificacion' => $calificacion,
                    'total_preguntas' => $request->total_preguntas,
                    'aciertos' => $request->aciertos,
                    'errores' => $request->errores,
                    'resultado' => $resultado,
                    'capturado_por' => auth()->id(),
                    'fecha_examen' => Carbon::now('America/Mexico_City'),
                    'observaciones' => $request->observaciones,
                ]
            );
        });

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Examen impreso capturado.');
    }

    public function activar(ConstanciaManejo $constancia)
    {
        $this->authorizeConstancia($constancia);
        $constancia->load('examen');

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'La constancia no esta inactiva.');
        }

        if (!$constancia->nombre_solicitante || !$constancia->sexo || !$constancia->tipo_licencia || !$constancia->tipo_examen) {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Faltan datos del solicitante, sexo, tipo de licencia o tipo de examen.');
        }

        if (!$constancia->examen || $constancia->examen->resultado !== 'APROBADO') {
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
            'observaciones' => null,
        ]);

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Constancia activada.');
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
            ->whereHas('examen', function ($query) {
                $query->where('resultado', 'APROBADO');
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
}
