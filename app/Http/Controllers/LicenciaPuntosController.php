<?php

namespace App\Http\Controllers;

use App\Models\Conductor;
use App\Models\LicenciaPuntoCuenta;
use App\Models\LicenciaPuntoInfraccion;
use App\Services\LicenciaPuntosService;
use Illuminate\Http\Request;

class LicenciaPuntosController extends Controller
{
    public function index(Request $request)
    {
        $query = LicenciaPuntoCuenta::query()
            ->with('conductor')
            ->withCount([
                'movimientos',
                'alertas as alertas_abiertas_count' => fn ($q) => $q->whereNull('atendida_at'),
            ])
            ->orderByDesc('id');

        if ($request->filled('buscar')) {
            $buscar = trim((string) $request->query('buscar'));
            $query->where(function ($q) use ($buscar) {
                $q->where('numero_licencia', 'like', "%{$buscar}%")
                    ->orWhere('titular_nombre', 'like', "%{$buscar}%")
                    ->orWhere('curp', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->query('estado'));
        }

        $cuentas = $query->paginate(25)->appends($request->query());
        $infracciones = LicenciaPuntoInfraccion::activas()->orderBy('nombre')->get();
        $conductores = Conductor::query()
            ->whereNotNull('numero_licencia')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $stats = [
            'total' => LicenciaPuntoCuenta::count(),
            'advertencia' => LicenciaPuntoCuenta::whereBetween('saldo_actual', [3, 4])->count(),
            'criticas' => LicenciaPuntoCuenta::whereBetween('saldo_actual', [1, 2])->count(),
            'agotadas' => LicenciaPuntoCuenta::where('saldo_actual', 0)->count(),
        ];

        return view('licencias_puntos.index', compact('cuentas', 'infracciones', 'conductores', 'stats'));
    }

    public function store(Request $request, LicenciaPuntosService $service)
    {
        $this->autorizarPruebaSuperadmin($request);

        $validated = $request->validate([
            'conductor_id' => ['nullable', 'integer', 'exists:conductores,id'],
            'numero_licencia' => ['required', 'string', 'max:80'],
            'tipo_licencia' => ['nullable', 'string', 'max:60'],
            'titular_nombre' => ['nullable', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'infraccion_id' => ['required', 'integer', 'exists:licencia_punto_infracciones,id'],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'hecho_id' => ['nullable', 'integer', 'exists:hechos,id'],
            'descripcion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
        ]);

        $infraccion = LicenciaPuntoInfraccion::findOrFail($validated['infraccion_id']);
        $cuenta = $service->registrarInfraccionDesdeCaptura($validated, $infraccion, $request->user());

        return redirect()
            ->route('licencias_puntos.show', $cuenta)
            ->with('success', 'Infraccion registrada y puntos actualizados.');
    }

    public function show(LicenciaPuntoCuenta $cuenta)
    {
        $cuenta->load(['conductor', 'creador', 'actualizador']);

        $movimientos = $cuenta->movimientos()
            ->with(['infraccion', 'usuario'])
            ->orderByDesc('fecha_movimiento')
            ->orderByDesc('id')
            ->paginate(20);

        $alertas = $cuenta->alertas()
            ->orderByRaw('atendida_at is null desc')
            ->orderByDesc('id')
            ->get();

        $infracciones = LicenciaPuntoInfraccion::activas()->orderBy('nombre')->get();

        return view('licencias_puntos.show', compact('cuenta', 'movimientos', 'alertas', 'infracciones'));
    }

    public function registrarInfraccion(Request $request, LicenciaPuntoCuenta $cuenta, LicenciaPuntosService $service)
    {
        $this->autorizarPruebaSuperadmin($request);

        abort_unless(
            $request->user()->can('registrar infracciones puntos licencias') || $request->user()->can('editar puntos licencias'),
            403
        );

        $validated = $request->validate([
            'infraccion_id' => ['required', 'integer', 'exists:licencia_punto_infracciones,id'],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'hecho_id' => ['nullable', 'integer', 'exists:hechos,id'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $infraccion = LicenciaPuntoInfraccion::findOrFail($validated['infraccion_id']);
        $service->registrarInfraccion($cuenta, $infraccion, $validated, $request->user());

        return redirect()
            ->route('licencias_puntos.show', $cuenta)
            ->with('success', 'Infraccion registrada y puntos actualizados.');
    }

    public function acreditarCapacitacion(Request $request, LicenciaPuntoCuenta $cuenta, LicenciaPuntosService $service)
    {
        $this->autorizarPruebaSuperadmin($request);

        abort_unless($request->user()->can('acreditar capacitacion puntos licencias'), 403);

        $validated = $request->validate([
            'puntos' => ['required', 'integer', 'min:1', 'max:8'],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $service->acreditarCapacitacion($cuenta, $validated, $request->user());

        return redirect()
            ->route('licencias_puntos.show', $cuenta)
            ->with('success', 'Capacitacion validada y puntos acreditados.');
    }

    public function recuperarPorTiempo(Request $request, LicenciaPuntoCuenta $cuenta, LicenciaPuntosService $service)
    {
        $this->autorizarPruebaSuperadmin($request);

        abort_unless($request->user()->can('editar puntos licencias'), 403);

        $recuperada = $service->recuperarPorTiempo($cuenta, null, $request->user());

        if (!$recuperada) {
            return redirect()
                ->route('licencias_puntos.show', $cuenta)
                ->with('error', 'La licencia aun no cumple 18 meses sin infracciones.');
        }

        return redirect()
            ->route('licencias_puntos.show', $cuenta)
            ->with('success', 'Puntos recuperados por tiempo sin infracciones.');
    }

    public function consulta(Request $request, LicenciaPuntosService $service)
    {
        $cuenta = null;
        $movimientos = collect();
        $saldoAsumido = false;
        $numeroConsultado = null;

        if ($request->filled('numero_licencia')) {
            $numero = $service->normalizarLicencia((string) $request->input('numero_licencia'));
            $numeroConsultado = $numero;
            $cuenta = LicenciaPuntoCuenta::where('numero_licencia', $numero)->first();

            if ($cuenta) {
                $movimientos = $cuenta->movimientos()
                    ->with('infraccion')
                    ->orderByDesc('fecha_movimiento')
                    ->limit(50)
                    ->get();
            } else {
                $saldoAsumido = true;
            }
        }

        return view('licencias_puntos.consulta', compact('cuenta', 'movimientos', 'saldoAsumido', 'numeroConsultado'));
    }

    private function autorizarPruebaSuperadmin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->hasRole('Superadmin'), 403);
    }
}
