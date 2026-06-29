<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LicenciaPuntoCuenta;
use App\Models\LicenciaPuntoInfraccion;
use App\Services\FomentoCulturaVialDetalleManager;
use App\Services\LicenciaPuntosService;
use App\Services\LicenciaPuntosTurnoAccessService;
use App\Support\LicenciaTipoCatalog;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LicenciaPuntosController extends Controller
{
    public function meta(Request $request)
    {
        $this->autorizarTurnoModulo($request);

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
                'tipos_licencia' => LicenciaTipoCatalog::all(),
                'infracciones' => $this->infraccionesActivasPayload(),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $this->autorizarTurnoModulo($request);

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

    public function catalogoInfracciones(Request $request)
    {
        $this->autorizarTurnoModulo($request);

        return response()->json([
            'ok' => true,
            'data' => $this->infraccionesActivasPayload(),
        ]);
    }

    public function store(Request $request, LicenciaPuntosService $service)
    {
        $this->autorizarRestarPuntos($request);
        $this->mergeIdempotencyKey($request);
        $request->merge([
            'tipo_licencia' => LicenciaTipoCatalog::requestValue($request->input('tipo_licencia')),
        ]);

        $validated = $request->validate([
            'conductor_id' => ['nullable', 'integer', 'exists:conductores,id'],
            'numero_licencia' => ['required', 'string', 'max:80'],
            'tipo_licencia' => ['nullable', Rule::in(LicenciaTipoCatalog::keys())],
            'titular_nombre' => ['nullable', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'infraccion_id' => ['required', 'integer', 'exists:licencia_punto_infracciones,id'],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'hecho_id' => ['nullable', 'integer', 'exists:hechos,id'],
            'descripcion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        $infraccion = LicenciaPuntoInfraccion::findOrFail($validated['infraccion_id']);
        $cuenta = $service->registrarInfraccionDesdeCaptura($validated, $infraccion, $request->user());

        return response()->json([
            'ok' => true,
            'message' => 'Sancion registrada correctamente.',
            'data' => $this->cuentaPayload($cuenta, true),
        ], 201);
    }

    public function show(Request $request, LicenciaPuntoCuenta $cuenta)
    {
        $this->autorizarTurnoModulo($request);

        $cuenta->load(['conductor', 'alertas', 'movimientos.infraccion', 'movimientos.usuario']);

        return response()->json([
            'ok' => true,
            'abilities' => $this->abilitiesPayload(request()),
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function showByNumero(Request $request, string $numeroLicencia, LicenciaPuntosService $service)
    {
        $this->autorizarTurnoModulo($request);

        $numero = $service->normalizarLicencia($numeroLicencia);
        $cuenta = LicenciaPuntoCuenta::where('numero_licencia', $numero)->first();

        if (!$cuenta) {
            return response()->json([
                'ok' => true,
                'abilities' => $this->abilitiesPayload(request()),
                'data' => $this->cuentaNoRegistradaPayload($numero),
            ]);
        }

        $cuenta->load(['conductor', 'alertas', 'movimientos.infraccion', 'movimientos.usuario']);

        return response()->json([
            'ok' => true,
            'abilities' => $this->abilitiesPayload(request()),
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function showPublicByNumero(Request $request, string $numeroLicencia, LicenciaPuntosService $service)
    {
        $numero = $service->normalizarLicencia($numeroLicencia);
        $cuenta = LicenciaPuntoCuenta::where('numero_licencia', $numero)->first();

        if (!$cuenta) {
            return response()->json([
                'ok' => true,
                'data' => $this->cuentaNoRegistradaPayload($numero),
            ]);
        }

        $cuenta->load(['movimientos.infraccion']);

        return response()->json([
            'ok' => true,
            'data' => $this->cuentaPayloadPublic($cuenta, true),
        ]);
    }

    public function registrarInfraccion(Request $request, LicenciaPuntosService $service)
    {
        $this->autorizarRestarPuntos($request);
        $this->mergeIdempotencyKey($request);
        $request->merge([
            'tipo_licencia' => LicenciaTipoCatalog::requestValue($request->input('tipo_licencia')),
        ]);

        $validated = $request->validate([
            'cuenta_id' => ['nullable', 'integer', 'exists:licencia_punto_cuentas,id'],
            'numero_licencia' => ['nullable', 'string', 'max:80'],
            'conductor_id' => ['nullable', 'integer', 'exists:conductores,id'],
            'tipo_licencia' => ['nullable', Rule::in(LicenciaTipoCatalog::keys())],
            'titular_nombre' => ['nullable', 'string', 'max:255'],
            'curp' => ['nullable', 'string', 'max:18'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'infraccion_id' => ['required', 'integer', 'exists:licencia_punto_infracciones,id'],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'hecho_id' => ['nullable', 'integer', 'exists:hechos,id'],
            'descripcion' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
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
            'message' => 'Sancion registrada correctamente.',
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function registrarInfraccionCuenta(Request $request, LicenciaPuntoCuenta $cuenta, LicenciaPuntosService $service)
    {
        $this->autorizarRestarPuntos($request);
        $this->mergeIdempotencyKey($request);

        $validated = $request->validate([
            'infraccion_id' => ['required', 'integer', 'exists:licencia_punto_infracciones,id'],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'hecho_id' => ['nullable', 'integer', 'exists:hechos,id'],
            'descripcion' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        $infraccion = LicenciaPuntoInfraccion::findOrFail($validated['infraccion_id']);
        $cuenta = $service->registrarInfraccion($cuenta, $infraccion, $validated, $request->user());

        return response()->json([
            'ok' => true,
            'message' => 'Sancion registrada correctamente.',
            'data' => $this->cuentaPayload($cuenta, true),
        ]);
    }

    public function acreditarCapacitacion(Request $request, LicenciaPuntoCuenta $cuenta, LicenciaPuntosService $service)
    {
        $this->autorizarSumarPuntos($request);
        $this->mergeIdempotencyKey($request);

        $validated = $request->validate([
            'puntos' => ['required', 'integer', 'min:1', 'max:' . LicenciaPuntoCuenta::SALDO_MAXIMO],
            'fecha_movimiento' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        $cuenta = $service->acreditarCapacitacion($cuenta, $validated, $request->user());

        return response()->json([
            'ok' => true,
            'message' => 'Capacitacion validada y puntos acreditados.',
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
            'tipo_licencia_label' => LicenciaTipoCatalog::label($cuenta->tipo_licencia),
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
                        'articulo' => $movimiento->infraccion->articulo,
                        'fraccion' => $movimiento->infraccion->fraccion,
                        'inciso' => $movimiento->infraccion->inciso,
                        'ambito_vehiculo' => $movimiento->infraccion->ambito_vehiculo,
                        'ambito_vehiculo_texto' => $movimiento->infraccion->ambito_vehiculo_texto,
                        'referencia_legal_corta' => $movimiento->infraccion->referencia_legal_corta,
                        'puntos' => (int) $movimiento->infraccion->puntos,
                        'multa_uma_min' => $movimiento->infraccion->multa_uma_min,
                        'multa_uma_max' => $movimiento->infraccion->multa_uma_max,
                        'multa_uma_texto' => $movimiento->infraccion->multa_uma_texto,
                        'amonestacion' => (bool) $movimiento->infraccion->amonestacion,
                        'arresto_persona' => (bool) $movimiento->infraccion->arresto_persona,
                        'suspension_licencia' => (bool) $movimiento->infraccion->suspension_licencia,
                        'cancelacion_licencia' => (bool) $movimiento->infraccion->cancelacion_licencia,
                        'deposito_si_sin_persona_habilitada' => (bool) $movimiento->infraccion->deposito_si_sin_persona_habilitada,
                        'sancion_persona_texto' => $movimiento->infraccion->sancion_persona_texto,
                        'retencion_vehiculo' => (bool) $movimiento->infraccion->retencion_vehiculo,
                        'resumen_sanciones' => $movimiento->infraccion->resumen_sanciones,
                        'fundamento_legal' => $movimiento->infraccion->fundamento_legal,
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

    private function cuentaPayloadPublic(LicenciaPuntoCuenta $cuenta, bool $includeHistory = false): array
    {
        $payload = $this->cuentaPayload($cuenta, $includeHistory);
        $payload['curp'] = null;
        $payload['telefono'] = null;
        $payload['conductor_id'] = null;
        $payload['expediente_folio'] = null;
        $payload['oficio_folio'] = null;
        $payload['finanzas_notificado_at'] = null;
        $payload['titular_notificado_at'] = null;
        $payload['alertas'] = [];

        return $payload;
    }

    private function cuentaNoRegistradaPayload(string $numero): array
    {
        return [
            'id' => null,
            'numero_licencia' => $numero,
            'tipo_licencia' => null,
            'tipo_licencia_label' => null,
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
        ];
    }

    private function abilitiesPayload(Request $request): array
    {
        $user = $request->user();
        $esFomento = $user
            ? app(FomentoCulturaVialDetalleManager::class)->usuarioEsFomento($user)
            : false;
        $turnoAccess = $user
            ? app(LicenciaPuntosTurnoAccessService::class)->statusForUser($user)
            : [
                'allowed' => false,
                'applies' => false,
                'reason' => 'guest',
                'message' => 'Sesion requerida.',
            ];
        $isSuperadmin = $user ? $user->hasRole('Superadmin') : false;
        $turnoAllowed = $isSuperadmin || (bool)($turnoAccess['allowed'] ?? false);
        $canRestar = $isSuperadmin || ($user ? $user->can('registrar infracciones puntos licencias') : false);
        $canSumar = $isSuperadmin || ($user ? $user->can('acreditar capacitacion puntos licencias') : false);

        return [
            'is_superadmin' => $isSuperadmin,
            'is_fomento_cultura_vial' => $esFomento,
            'module_writes_locked' => !$turnoAllowed || (!$canRestar && !$canSumar),
            'can_restar_puntos' => $turnoAllowed && $canRestar,
            'can_sumar_puntos' => $turnoAllowed && $canSumar,
            'can_recuperar_por_tiempo' => false,
            'can_ver_catalogo_infracciones' => $isSuperadmin || ($user ? $user->can('ver puntos licencias') : false),
            'licencias_puntos_turno' => $turnoAccess,
            'licencias_puntos_turno_permitido' => $turnoAllowed,
        ];
    }

    private function infraccionesActivasPayload()
    {
        return LicenciaPuntoInfraccion::activas()
            ->get()
            ->sortBy(function (LicenciaPuntoInfraccion $infraccion) {
                return implode('|', [
                    $infraccion->articulo ? str_pad((string) $infraccion->articulo, 8, '0', STR_PAD_LEFT) : 'ZZZZZZZZ',
                    $infraccion->fraccion ?: 'ZZZZ',
                    $infraccion->inciso ?: 'ZZZZ',
                    $infraccion->nombre,
                ]);
            })
            ->values()
            ->map(fn (LicenciaPuntoInfraccion $infraccion) => [
                'id' => $infraccion->id,
                'codigo' => $infraccion->codigo,
                'nombre' => $infraccion->nombre,
                'articulo' => $infraccion->articulo,
                'fraccion' => $infraccion->fraccion,
                'inciso' => $infraccion->inciso,
                'ambito_vehiculo' => $infraccion->ambito_vehiculo,
                'ambito_vehiculo_texto' => $infraccion->ambito_vehiculo_texto,
                'referencia_legal_corta' => $infraccion->referencia_legal_corta,
                'puntos' => (int) $infraccion->puntos,
                'multa_uma_min' => $infraccion->multa_uma_min,
                'multa_uma_max' => $infraccion->multa_uma_max,
                'multa_uma_texto' => $infraccion->multa_uma_texto,
                'amonestacion' => (bool) $infraccion->amonestacion,
                'arresto_persona' => (bool) $infraccion->arresto_persona,
                'suspension_licencia' => (bool) $infraccion->suspension_licencia,
                'cancelacion_licencia' => (bool) $infraccion->cancelacion_licencia,
                'deposito_si_sin_persona_habilitada' => (bool) $infraccion->deposito_si_sin_persona_habilitada,
                'sancion_persona_texto' => $infraccion->sancion_persona_texto,
                'retencion_vehiculo' => (bool) $infraccion->retencion_vehiculo,
                'resumen_sanciones' => $infraccion->resumen_sanciones,
                'etiqueta_operativa' => $infraccion->etiqueta_operativa,
                'descripcion' => $infraccion->descripcion,
                'fundamento_legal' => $infraccion->fundamento_legal,
            ]);
    }

    private function autorizarRestarPuntos(Request $request): void
    {
        $this->autorizarTurnoModulo($request);

        abort_unless(
            $request->user()
            && ($request->user()->hasRole('Superadmin') || $request->user()->can('registrar infracciones puntos licencias')),
            403
        );
    }

    private function autorizarSumarPuntos(Request $request): void
    {
        $this->autorizarTurnoModulo($request);

        abort_unless(
            $request->user()
            && ($request->user()->hasRole('Superadmin') || $request->user()->can('acreditar capacitacion puntos licencias')),
            403
        );
    }

    private function autorizarTurnoModulo(Request $request): void
    {
        abort_unless($request->user(), 403);

        if ($request->user()->hasRole('Superadmin')) {
            return;
        }

        $status = app(LicenciaPuntosTurnoAccessService::class)
            ->statusForUser($request->user());

        if ((bool)($status['allowed'] ?? false)) {
            return;
        }

        throw new HttpResponseException(response()->json([
            'ok' => false,
            'message' => $status['message'] ?? 'Acceso bloqueado por turno.',
            'licencias_puntos_turno' => $status,
        ], 403));
    }

    private function mergeIdempotencyKey(Request $request): void
    {
        if ($request->filled('idempotency_key')) {
            return;
        }

        $key = trim((string) (
            $request->header('Idempotency-Key')
            ?: $request->header('X-Idempotency-Key')
            ?: ''
        ));

        if ($key !== '') {
            $request->merge(['idempotency_key' => $key]);
        }
    }
}
