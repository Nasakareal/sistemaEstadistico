<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenciaPuntoCuenta;
use App\Models\LicenciaPuntoInfraccion;
use App\Services\FomentoCulturaVialDetalleManager;
use App\Services\LicenciaPuntosService;
use Illuminate\Http\Request;

class LicenciaPuntosController extends Controller
{
    public function meta(Request $request)
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'saldo_inicial' => LicenciaPuntoCuenta::SALDO_INICIAL,
                'saldo_maximo' => LicenciaPuntoCuenta::SALDO_MAXIMO,
                'meses_recuperacion_tiempo' => LicenciaPuntoCuenta::MESES_RECUPERACION_TIEMPO,
                'estados' => [
                    LicenciaPuntoCuenta::ESTADO_VIGENTE => 'Vigente',
                    LicenciaPuntoCuenta::ESTADO_PROCEDIMIENTO => 'Procedimiento administrativo',
                    LicenciaPuntoCuenta::ESTADO_SUSPENDIDA => 'Suspendida',
                    LicenciaPuntoCuenta::ESTADO_CANCELADA => 'Cancelada',
                ],
                'abilities' => $this->abilitiesPayload($request),
                'infracciones' => LicenciaPuntoInfraccion::activas()
                    ->orderBy('nombre')
                    ->get(['id', 'codigo', 'nombre', 'puntos', 'descripcion']),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $query = LicenciaPuntoCuenta::with('conductor')->orderByDesc('id');

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

        $perPage = max(1, min((int) $request->query('per_page', 25), 100));
        $page = $query->paginate($perPage);

        return response()->json([
            'ok' => true,
            'abilities' => $this->abilitiesPayload($request),
            'data' => $page->getCollection()->map(fn ($cuenta) => $this->cuentaPayload($cuenta))->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function catalogoInfracciones()
    {
        return response()->json([
            'ok' => true,
            'data' => LicenciaPuntoInfraccion::activas()
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre', 'puntos', 'descripcion']),
        ]);
    }

    public function store(Request $request, LicenciaPuntosService $service)
    {
        $this->autorizarRestarPuntos($request);

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

        return response()->json([
            'ok' => true,
            'message' => 'Infraccion registrada y puntos actualizados.',
            'data' => $this->cuentaPayload($cuenta, true),
        ], 201);
    }

    public function show(LicenciaPuntoCuenta $cuenta)
    {
        $cuenta->load(['conductor', 'alertas', 'movimientos.infraccion', 'movimientos.usuario']);

        return response()->json([
            'ok' => true,
            'abilities' => $this->abilitiesPayload(request()),
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function showByNumero(string $numeroLicencia, LicenciaPuntosService $service)
    {
        $numero = $service->normalizarLicencia($numeroLicencia);
        $cuenta = LicenciaPuntoCuenta::where('numero_licencia', $numero)->first();

        if (!$cuenta) {
            return response()->json([
                'ok' => true,
                'abilities' => $this->abilitiesPayload(request()),
                'data' => [
                    'id' => null,
                    'numero_licencia' => $numero,
                    'tipo_licencia' => null,
                    'titular_nombre' => null,
                    'curp' => null,
                    'telefono' => null,
                    'saldo_actual' => LicenciaPuntoCuenta::SALDO_INICIAL,
                    'saldo_maximo' => LicenciaPuntoCuenta::SALDO_MAXIMO,
                    'nivel_saldo' => 'normal',
                    'estado' => LicenciaPuntoCuenta::ESTADO_VIGENTE,
                    'estado_label' => 'Vigente',
                    'fecha_emision' => null,
                    'fecha_vencimiento' => null,
                    'fecha_ultima_infraccion' => null,
                    'fecha_recuperacion' => null,
                    'reincidencias_cero' => 0,
                    'expediente_folio' => null,
                    'oficio_folio' => null,
                    'finanzas_notificado_at' => null,
                    'titular_notificado_at' => null,
                    'conductor_id' => null,
                    'cuenta_registrada' => false,
                    'movimientos' => [],
                    'alertas' => [],
                ],
            ]);
        }

        $cuenta->load(['conductor', 'alertas', 'movimientos.infraccion', 'movimientos.usuario']);

        return response()->json([
            'ok' => true,
            'abilities' => $this->abilitiesPayload(request()),
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function registrarInfraccion(Request $request, LicenciaPuntosService $service)
    {
        $this->autorizarRestarPuntos($request);

        $validated = $request->validate([
            'cuenta_id' => ['nullable', 'integer', 'exists:licencia_punto_cuentas,id'],
            'numero_licencia' => ['nullable', 'string', 'max:80'],
            'conductor_id' => ['nullable', 'integer', 'exists:conductores,id'],
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

        if (empty($validated['cuenta_id']) && empty($validated['numero_licencia'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Debes enviar cuenta_id o numero_licencia.',
            ], 422);
        }

        $infraccion = LicenciaPuntoInfraccion::findOrFail($validated['infraccion_id']);
        if (!empty($validated['cuenta_id'])) {
            $cuenta = LicenciaPuntoCuenta::findOrFail($validated['cuenta_id']);
            $cuenta = $service->registrarInfraccion($cuenta, $infraccion, $validated, $request->user());
        } else {
            $cuenta = $service->registrarInfraccionDesdeCaptura($validated, $infraccion, $request->user());
        }

        return response()->json([
            'ok' => true,
            'message' => 'Infraccion registrada y puntos actualizados.',
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function registrarInfraccionCuenta(Request $request, LicenciaPuntoCuenta $cuenta, LicenciaPuntosService $service)
    {
        $this->autorizarRestarPuntos($request);

        $validated = $request->validate([
            'infraccion_id' => ['required', 'integer', 'exists:licencia_punto_infracciones,id'],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'hecho_id' => ['nullable', 'integer', 'exists:hechos,id'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $infraccion = LicenciaPuntoInfraccion::findOrFail($validated['infraccion_id']);
        $cuenta = $service->registrarInfraccion($cuenta, $infraccion, $validated, $request->user());

        return response()->json([
            'ok' => true,
            'message' => 'Infraccion registrada y puntos actualizados.',
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function acreditarCapacitacion(Request $request, LicenciaPuntoCuenta $cuenta, LicenciaPuntosService $service)
    {
        $this->autorizarSumarPuntos($request);

        $validated = $request->validate([
            'puntos' => ['required', 'integer', 'min:1', 'max:8'],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $cuenta = $service->acreditarCapacitacion($cuenta, $validated, $request->user());

        return response()->json([
            'ok' => true,
            'message' => 'Capacitacion validada y puntos acreditados.',
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function recuperarPorTiempo(Request $request, LicenciaPuntoCuenta $cuenta, LicenciaPuntosService $service)
    {
        abort_unless($request->user() && $request->user()->can('editar puntos licencias'), 403);

        $cuenta = $service->recuperarPorTiempo($cuenta, null, $request->user());

        if (!$cuenta) {
            return response()->json([
                'ok' => false,
                'message' => 'La licencia aun no cumple 18 meses sin infracciones.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Puntos recuperados por tiempo sin infracciones.',
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    private function cuentaPayload(LicenciaPuntoCuenta $cuenta, bool $includeHistory = false): array
    {
        $cuenta->loadMissing('conductor');

        $payload = [
            'id' => $cuenta->id,
            'numero_licencia' => $cuenta->numero_licencia,
            'tipo_licencia' => $cuenta->tipo_licencia,
            'titular_nombre' => $cuenta->titular_nombre,
            'curp' => $cuenta->curp,
            'telefono' => $cuenta->telefono,
            'saldo_actual' => (int) $cuenta->saldo_actual,
            'saldo_maximo' => LicenciaPuntoCuenta::SALDO_MAXIMO,
            'nivel_saldo' => $cuenta->nivel_saldo,
            'estado' => $cuenta->estado,
            'estado_label' => $cuenta->estado_label,
            'fecha_emision' => optional($cuenta->fecha_emision)->toDateString(),
            'fecha_vencimiento' => optional($cuenta->fecha_vencimiento)->toDateString(),
            'fecha_ultima_infraccion' => optional($cuenta->fecha_ultima_infraccion)->toISOString(),
            'fecha_recuperacion' => optional($cuenta->fecha_recuperacion)->toDateString(),
            'reincidencias_cero' => (int) $cuenta->reincidencias_cero,
            'expediente_folio' => $cuenta->expediente_folio,
            'oficio_folio' => $cuenta->oficio_folio,
            'finanzas_notificado_at' => optional($cuenta->finanzas_notificado_at)->toISOString(),
            'titular_notificado_at' => optional($cuenta->titular_notificado_at)->toISOString(),
            'conductor_id' => $cuenta->conductor_id,
            'cuenta_registrada' => true,
        ];

        if ($includeHistory) {
            $cuenta->loadMissing(['movimientos.infraccion', 'movimientos.usuario', 'alertas']);
            $payload['movimientos'] = $cuenta->movimientos
                ->sortByDesc('fecha_movimiento')
                ->values()
                ->map(fn ($movimiento) => [
                    'id' => $movimiento->id,
                    'tipo' => $movimiento->tipo,
                    'puntos' => (int) $movimiento->puntos,
                    'saldo_anterior' => (int) $movimiento->saldo_anterior,
                    'saldo_nuevo' => (int) $movimiento->saldo_nuevo,
                    'fecha_movimiento' => optional($movimiento->fecha_movimiento)->toISOString(),
                    'referencia' => $movimiento->referencia,
                    'descripcion' => $movimiento->descripcion,
                    'infraccion' => $movimiento->infraccion ? [
                        'id' => $movimiento->infraccion->id,
                        'codigo' => $movimiento->infraccion->codigo,
                        'nombre' => $movimiento->infraccion->nombre,
                        'puntos' => (int) $movimiento->infraccion->puntos,
                    ] : null,
                ]);

            $payload['alertas'] = $cuenta->alertas
                ->sortByDesc('id')
                ->values()
                ->map(fn ($alerta) => [
                    'id' => $alerta->id,
                    'tipo' => $alerta->tipo,
                    'nivel' => $alerta->nivel,
                    'saldo_disparador' => (int) $alerta->saldo_disparador,
                    'mensaje' => $alerta->mensaje,
                    'atendida_at' => optional($alerta->atendida_at)->toISOString(),
                ]);
        }

        return $payload;
    }

    private function abilitiesPayload(Request $request): array
    {
        $user = $request->user();
        $esFomento = $user
            ? app(FomentoCulturaVialDetalleManager::class)->usuarioEsFomento($user)
            : false;

        return [
            'is_superadmin' => $user ? $user->hasRole('Superadmin') : false,
            'is_fomento_cultura_vial' => $esFomento,
            'can_restar_puntos' => $user ? $user->can('registrar infracciones puntos licencias') : false,
            'can_sumar_puntos' => $user ? $user->can('acreditar capacitacion puntos licencias') : false,
            'can_recuperar_por_tiempo' => $user ? $user->can('editar puntos licencias') : false,
            'can_ver_catalogo_infracciones' => $user ? $user->can('ver puntos licencias') : false,
        ];
    }

    private function autorizarRestarPuntos(Request $request): void
    {
        abort_unless($request->user() && $request->user()->can('registrar infracciones puntos licencias'), 403);
    }

    private function autorizarSumarPuntos(Request $request): void
    {
        abort_unless($request->user() && $request->user()->can('acreditar capacitacion puntos licencias'), 403);
    }
}
