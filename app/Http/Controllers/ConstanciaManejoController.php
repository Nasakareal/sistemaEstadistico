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
    public function index(Request $request)
    {
        $query = ConstanciaManejo::with(['modulo', 'usuario', 'peritoActivador', 'examen'])->orderByDesc('id');

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
        $modulos = ConstanciaModulo::where('activo', true)->orderBy('nombre')->get();

        return view('constancias_manejo.create', compact('modulos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'modulo_id' => ['required', 'exists:constancia_modulos,id'],
            'nombre_solicitante' => ['required', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo_licencia' => ['required', 'in:SERVICIO_PUBLICO,AUTOMOVILISTA,CHOFER,MOTOCICLISTA,PERMISO'],
            'tipo_examen' => ['required', 'in:LINEA,IMPRESO'],
        ]);

        $constancia = DB::transaction(function () use ($request) {
            $modulo = ConstanciaModulo::findOrFail($request->modulo_id);
            $origen = $modulo->tipo === 'SINIESTROS' ? 'SINIESTROS' : 'DELEGACIONES';
            $prefijo = $modulo->tipo === 'SINIESTROS' ? 'S' : 'D';

            $ultimoNumero = ConstanciaFolio::where('prefijo', $prefijo)->lockForUpdate()->max('numero');
            $numero = $ultimoNumero ? $ultimoNumero + 1 : 1;
            $folio = $prefijo . '-' . str_pad($numero, 4, '0', STR_PAD_LEFT);

            $constancia = ConstanciaManejo::create([
                'folio' => $folio,
                'folio_qr' => $folio,
                'modulo_id' => $modulo->id,
                'delegacion_id' => $modulo->delegacion_id,
                'user_id' => auth()->id(),
                'perito_activador_id' => null,
                'nombre_solicitante' => mb_strtoupper($request->nombre_solicitante, 'UTF-8'),
                'curp' => $request->curp ? mb_strtoupper($request->curp, 'UTF-8') : null,
                'telefono' => $request->telefono,
                'tipo_licencia' => $request->tipo_licencia,
                'tipo_examen' => $request->tipo_examen,
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

            return $constancia;
        });

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Constancia generada como inactiva.');
    }

    public function show(ConstanciaManejo $constancia)
    {
        $constancia->load(['modulo', 'usuario', 'peritoActivador', 'examen', 'activaciones.usuario']);

        return view('constancias_manejo.show', compact('constancia'));
    }

    public function edit(ConstanciaManejo $constancia)
    {
        if ($constancia->estatus === 'ACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'No se puede editar una constancia activa.');
        }

        $modulos = ConstanciaModulo::where('activo', true)->orderBy('nombre')->get();

        return view('constancias_manejo.edit', compact('constancia', 'modulos'));
    }

    public function update(Request $request, ConstanciaManejo $constancia)
    {
        if ($constancia->estatus === 'ACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'No se puede editar una constancia activa.');
        }

        $request->validate([
            'modulo_id' => ['required', 'exists:constancia_modulos,id'],
            'nombre_solicitante' => ['required', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo_licencia' => ['required', 'in:SERVICIO_PUBLICO,AUTOMOVILISTA,CHOFER,MOTOCICLISTA,PERMISO'],
            'tipo_examen' => ['required', 'in:LINEA,IMPRESO'],
        ]);

        $modulo = ConstanciaModulo::findOrFail($request->modulo_id);

        $constancia->update([
            'modulo_id' => $modulo->id,
            'delegacion_id' => $modulo->delegacion_id,
            'nombre_solicitante' => mb_strtoupper($request->nombre_solicitante, 'UTF-8'),
            'curp' => $request->curp ? mb_strtoupper($request->curp, 'UTF-8') : null,
            'telefono' => $request->telefono,
            'tipo_licencia' => $request->tipo_licencia,
            'tipo_examen' => $request->tipo_examen,
        ]);

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Constancia actualizada.');
    }

    public function destroy(ConstanciaManejo $constancia)
    {
        if ($constancia->estatus === 'ACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'No se puede eliminar una constancia activa.');
        }

        $constancia->delete();

        return redirect()->route('constancias_manejo.index')->with('success', 'Constancia eliminada.');
    }

    public function imprimir(ConstanciaManejo $constancia)
    {
        $constancia->load(['modulo', 'examen']);

        return view('constancias_manejo.imprimir', compact('constancia'));
    }

    public function reimprimir(ConstanciaManejo $constancia)
    {
        $constancia->load(['modulo', 'examen']);

        ConstanciaActivacion::create([
            'constancia_id' => $constancia->id,
            'user_id' => auth()->id(),
            'accion' => 'REIMPRESA',
            'fecha' => Carbon::now('America/Mexico_City'),
            'observaciones' => null,
        ]);

        return view('constancias_manejo.imprimir', compact('constancia'));
    }

    public function generarAcceso(ConstanciaManejo $constancia)
    {
        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Solo se puede generar acceso a constancias inactivas.');
        }

        if ($constancia->tipo_examen !== 'LINEA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Esta constancia está marcada como examen impreso.');
        }

        $constancia->update([
            'acceso_examen_token' => Str::random(60),
            'acceso_examen_expira' => Carbon::now('America/Mexico_City')->addMinutes(30),
        ]);

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Acceso temporal generado.');
    }

    public function capturarExamenImpreso(Request $request, ConstanciaManejo $constancia)
    {
        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Solo se puede capturar examen de constancias inactivas.');
        }

        if ($constancia->tipo_examen !== 'IMPRESO') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Esta constancia está marcada como examen en línea.');
        }

        $request->validate([
            'calificacion' => ['required', 'numeric', 'min:0', 'max:100'],
            'total_preguntas' => ['required', 'integer', 'min:1'],
            'aciertos' => ['required', 'integer', 'min:0'],
            'errores' => ['required', 'integer', 'min:0'],
            'observaciones' => ['nullable', 'string'],
        ]);

        if ((int) $request->aciertos + (int) $request->errores !== (int) $request->total_preguntas) {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'Los aciertos y errores no coinciden con el total de preguntas.');
        }

        $resultado = $request->calificacion >= 80 ? 'APROBADO' : 'REPROBADO';

        ConstanciaExamen::updateOrCreate(
            [
                'constancia_id' => $constancia->id,
            ],
            [
                'modalidad' => 'IMPRESO',
                'calificacion' => $request->calificacion,
                'total_preguntas' => $request->total_preguntas,
                'aciertos' => $request->aciertos,
                'errores' => $request->errores,
                'resultado' => $resultado,
                'capturado_por' => auth()->id(),
                'fecha_examen' => Carbon::now('America/Mexico_City'),
                'observaciones' => $request->observaciones,
            ]
        );

        return redirect()->route('constancias_manejo.show', $constancia)->with('success', 'Examen impreso capturado.');
    }

    public function activar(ConstanciaManejo $constancia)
    {
        $constancia->load('examen');

        if ($constancia->estatus !== 'IMPRESA_INACTIVA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'La constancia no está inactiva.');
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
        $request->validate([
            'observaciones' => ['nullable', 'string'],
        ]);

        if ($constancia->estatus === 'CANCELADA') {
            return redirect()->route('constancias_manejo.show', $constancia)->with('error', 'La constancia ya está cancelada.');
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
        $constancias = ConstanciaManejo::with(['modulo', 'examen'])
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
        $constancias = ConstanciaManejo::with(['modulo', 'examen'])
            ->where('estatus', 'IMPRESA_INACTIVA')
            ->whereNotNull('acceso_examen_expira')
            ->where('acceso_examen_expira', '<', Carbon::now('America/Mexico_City'))
            ->orderByDesc('id')
            ->paginate(25);

        return view('constancias_manejo.inactivas_vencidas', compact('constancias'));
    }
}
