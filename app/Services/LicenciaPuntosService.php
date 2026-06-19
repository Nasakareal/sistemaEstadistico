<?php

namespace App\Services;

use App\Models\LicenciaPuntoAlerta;
use App\Models\LicenciaPuntoCuenta;
use App\Models\LicenciaPuntoInfraccion;
use App\Models\LicenciaPuntoMovimiento;
use App\Models\Conductor;
use App\Models\Oficio;
use App\Models\User;
use App\Support\LicenciaTipoCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LicenciaPuntosService
{
    /** @var LicenciaPuntosWhatsAppNotificationService */
    private $notificadorWhatsApp;

    private const ALERTAS = [
        4 => [
            'tipo' => 'advertencia_4',
            'nivel' => 'advertencia',
            'mensaje' => 'La licencia llego a 4 puntos disponibles. Emitir advertencia preventiva.',
        ],
        2 => [
            'tipo' => 'advertencia_critica_2',
            'nivel' => 'critica',
            'mensaje' => 'La licencia llego a 2 puntos disponibles. Emitir advertencia critica.',
        ],
        0 => [
            'tipo' => 'agotamiento_0',
            'nivel' => 'agotamiento',
            'mensaje' => 'La licencia llego a 0 puntos. Iniciar procedimiento administrativo.',
        ],
    ];

    public function __construct(LicenciaPuntosWhatsAppNotificationService $notificadorWhatsApp)
    {
        $this->notificadorWhatsApp = $notificadorWhatsApp;
    }

    public function registrarInfraccionDesdeCaptura(
        array $data,
        LicenciaPuntoInfraccion $infraccion,
        ?User $actor = null
    ): LicenciaPuntoCuenta
    {
        $numeroLicencia = $this->normalizarLicencia($data['numero_licencia'] ?? '');

        return DB::transaction(function () use ($data, $infraccion, $actor, $numeroLicencia) {
            if ($numeroLicencia === '') {
                throw ValidationException::withMessages([
                    'numero_licencia' => 'Debes capturar el numero de licencia.',
                ]);
            }

            $cuenta = LicenciaPuntoCuenta::where('numero_licencia', $numeroLicencia)
                ->lockForUpdate()
                ->first();

            if (!$cuenta) {
                $cuenta = $this->crearCuentaPorPrimeraInfraccion($data, $numeroLicencia, $actor);
            } else {
                $this->completarCuentaDesdeCaptura($cuenta, $data, $actor);
            }

            return $this->registrarInfraccion($cuenta, $infraccion, $data, $actor);
        });
    }

    public function registrarInfraccion(
        LicenciaPuntoCuenta $cuenta,
        LicenciaPuntoInfraccion $infraccion,
        array $data,
        ?User $actor = null
    ): LicenciaPuntoCuenta {
        return DB::transaction(function () use ($cuenta, $infraccion, $data, $actor) {
            $cuenta = LicenciaPuntoCuenta::whereKey($cuenta->getKey())->lockForUpdate()->firstOrFail();
            $infraccion = LicenciaPuntoInfraccion::whereKey($infraccion->getKey())->lockForUpdate()->firstOrFail();
            $actorId = $actor ? $actor->id : null;

            if (!$infraccion->activa) {
                throw ValidationException::withMessages([
                    'infraccion_id' => 'La penalización seleccionada no esta activa.',
                ]);
            }

            $fecha = $this->fecha($data['fecha_movimiento'] ?? null) ?: Carbon::now('America/Mexico_City');
            $saldoAnterior = (int) $cuenta->saldo_actual;
            $puntosNorma = (int) $infraccion->puntos;
            $puntosAplicados = min($saldoAnterior, $puntosNorma);
            $saldoNuevo = max(0, $saldoAnterior - $puntosNorma);
            $llegoACero = $saldoAnterior > 0 && $saldoNuevo === 0;

            $this->completarCuentaDesdeCaptura($cuenta, $data, $actor);

            $cuenta->fill([
                'saldo_actual' => $saldoNuevo,
                'fecha_ultima_infraccion' => $fecha,
                'updated_by' => $actorId,
            ]);

            if ($llegoACero) {
                $reincidencias = (int) $cuenta->reincidencias_cero + 1;
                $cuenta->reincidencias_cero = $reincidencias;
                $cuenta->estado = LicenciaPuntoCuenta::ESTADO_PROCEDIMIENTO;
                $cuenta->fecha_agotamiento = $fecha;
                $cuenta->expediente_folio = $this->folio('EXP-PTS', $cuenta, $reincidencias);
                $cuenta->oficio_folio = $this->folio('OF-PTS', $cuenta, $reincidencias);
            }

            $cuenta->save();

            $movimiento = $cuenta->movimientos()->create([
                'infraccion_id' => $infraccion->id,
                'hecho_id' => $data['hecho_id'] ?? null,
                'user_id' => $actorId,
                'tipo' => 'infraccion',
                'puntos' => -$puntosAplicados,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'fecha_movimiento' => $fecha,
                'referencia' => $data['referencia'] ?? null,
                'descripcion' => $data['descripcion'] ?? $infraccion->nombre,
                'metadata' => [
                    'codigo_infraccion' => $infraccion->codigo,
                    'fundamento_legal' => $infraccion->fundamento_legal,
                    'puntos_norma' => $puntosNorma,
                    'puntos_aplicados' => $puntosAplicados,
                ],
            ]);

            $this->generarAlertas($cuenta, $movimiento, $saldoAnterior, $saldoNuevo, $fecha, $actor);

            if ($llegoACero) {
                $this->registrarProcedimiento($cuenta, $fecha, $actor);
            }

            if ($puntosAplicados > 0) {
                $this->programarNotificacionesWhatsApp($cuenta, $movimiento, $infraccion, $fecha, $llegoACero);
            }

            return $cuenta->fresh(['movimientos.infraccion', 'alertas', 'conductor']);
        });
    }

    public function acreditarCapacitacion(LicenciaPuntoCuenta $cuenta, array $data, ?User $actor = null): LicenciaPuntoCuenta
    {
        return DB::transaction(function () use ($cuenta, $data, $actor) {
            $cuenta = LicenciaPuntoCuenta::whereKey($cuenta->getKey())->lockForUpdate()->firstOrFail();
            $actorId = $actor ? $actor->id : null;
            $fecha = $this->fecha($data['fecha_movimiento'] ?? null) ?: Carbon::now('America/Mexico_City');
            $saldoAnterior = (int) $cuenta->saldo_actual;
            $puntosSolicitados = max(1, (int) ($data['puntos'] ?? LicenciaPuntoCuenta::SALDO_MAXIMO));
            $saldoNuevo = min(LicenciaPuntoCuenta::SALDO_MAXIMO, $saldoAnterior + $puntosSolicitados);
            $puntosAplicados = $saldoNuevo - $saldoAnterior;

            if ($puntosAplicados <= 0) {
                throw ValidationException::withMessages([
                    'puntos' => 'La licencia ya tiene el saldo maximo.',
                ]);
            }

            $cuenta->fill([
                'saldo_actual' => $saldoNuevo,
                'updated_by' => $actorId,
            ]);

            if ($cuenta->estado === LicenciaPuntoCuenta::ESTADO_PROCEDIMIENTO && $saldoNuevo > 0) {
                $cuenta->estado = LicenciaPuntoCuenta::ESTADO_VIGENTE;
            }

            $cuenta->save();

            $cuenta->movimientos()->create([
                'user_id' => $actorId,
                'tipo' => 'recuperacion_capacitacion',
                'puntos' => $puntosAplicados,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'fecha_movimiento' => $fecha,
                'referencia' => $data['referencia'] ?? null,
                'descripcion' => $data['descripcion'] ?? 'Curso de seguridad vial validado por SSP.',
                'metadata' => [
                    'puntos_solicitados' => $puntosSolicitados,
                    'validado_por' => 'SSP',
                ],
            ]);

            $this->atenderAlertasAbiertas($cuenta, $fecha);

            return $cuenta->fresh(['movimientos.infraccion', 'alertas', 'conductor']);
        });
    }

    public function recuperarPorTiempo(LicenciaPuntoCuenta $cuenta, ?Carbon $fecha = null, ?User $actor = null): ?LicenciaPuntoCuenta
    {
        return DB::transaction(function () use ($cuenta, $fecha, $actor) {
            $fecha = $fecha ?: Carbon::now('America/Mexico_City');
            $cuenta = LicenciaPuntoCuenta::whereKey($cuenta->getKey())->lockForUpdate()->firstOrFail();
            $actorId = $actor ? $actor->id : null;

            if (!$cuenta->puedeRecuperarPorTiempo($fecha)) {
                return null;
            }

            $saldoAnterior = (int) $cuenta->saldo_actual;
            $saldoNuevo = LicenciaPuntoCuenta::SALDO_MAXIMO;
            $puntosAplicados = $saldoNuevo - $saldoAnterior;

            $cuenta->fill([
                'saldo_actual' => $saldoNuevo,
                'estado' => LicenciaPuntoCuenta::ESTADO_VIGENTE,
                'updated_by' => $actorId,
            ]);
            $cuenta->save();

            $cuenta->movimientos()->create([
                'user_id' => $actorId,
                'tipo' => 'recuperacion_tiempo',
                'puntos' => $puntosAplicados,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'fecha_movimiento' => $fecha,
                'referencia' => '18_MESES_SIN_INFRACCION',
                'descripcion' => 'Recuperacion automatica por 18 meses sin penalizaciones.',
                'metadata' => [
                    'fecha_ultima_infraccion' => optional($cuenta->fecha_ultima_infraccion)->toDateTimeString(),
                    'meses_sin_infraccion' => LicenciaPuntoCuenta::MESES_RECUPERACION_TIEMPO,
                ],
            ]);

            $this->atenderAlertasAbiertas($cuenta, $fecha);

            return $cuenta->fresh(['movimientos.infraccion', 'alertas', 'conductor']);
        });
    }

    public function recuperarCuentasElegibles(?Carbon $fecha = null): int
    {
        $fecha = $fecha ?: Carbon::now('America/Mexico_City');
        $cutoff = $fecha->copy()->subMonthsNoOverflow(LicenciaPuntoCuenta::MESES_RECUPERACION_TIEMPO);
        $recuperadas = 0;

        LicenciaPuntoCuenta::query()
            ->where('saldo_actual', '<', LicenciaPuntoCuenta::SALDO_MAXIMO)
            ->whereNotIn('estado', [LicenciaPuntoCuenta::ESTADO_SUSPENDIDA, LicenciaPuntoCuenta::ESTADO_CANCELADA])
            ->where(function ($query) use ($cutoff) {
                $query->where('fecha_ultima_infraccion', '<=', $cutoff)
                    ->orWhere(function ($sinInfracciones) use ($cutoff) {
                        $sinInfracciones->whereNull('fecha_ultima_infraccion')
                            ->where('fecha_emision', '<=', $cutoff->toDateString());
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($cuentas) use ($fecha, &$recuperadas) {
                foreach ($cuentas as $cuenta) {
                    if ($this->recuperarPorTiempo($cuenta, $fecha)) {
                        $recuperadas++;
                    }
                }
            });

        return $recuperadas;
    }

    public function normalizarLicencia(string $numeroLicencia): string
    {
        $numeroLicencia = mb_strtoupper(trim($numeroLicencia), 'UTF-8');

        return preg_replace('/\s+/', '', $numeroLicencia) ?: '';
    }

    private function generarAlertas(
        LicenciaPuntoCuenta $cuenta,
        LicenciaPuntoMovimiento $movimiento,
        int $saldoAnterior,
        int $saldoNuevo,
        Carbon $fecha,
        ?User $actor
    ): void {
        $actorId = $actor ? $actor->id : null;

        foreach (self::ALERTAS as $umbral => $config) {
            if (!($saldoAnterior > $umbral && $saldoNuevo <= $umbral)) {
                continue;
            }

            $alerta = LicenciaPuntoAlerta::create([
                'cuenta_id' => $cuenta->id,
                'movimiento_id' => $movimiento->id,
                'tipo' => $config['tipo'],
                'nivel' => $config['nivel'],
                'saldo_disparador' => $umbral,
                'mensaje' => $config['mensaje'],
                'created_by' => $actorId,
            ]);

            $cuenta->movimientos()->create([
                'user_id' => $actorId,
                'tipo' => 'alerta',
                'puntos' => 0,
                'saldo_anterior' => $saldoNuevo,
                'saldo_nuevo' => $saldoNuevo,
                'fecha_movimiento' => $fecha,
                'referencia' => $alerta->tipo,
                'descripcion' => $alerta->mensaje,
                'metadata' => [
                    'alerta_id' => $alerta->id,
                    'nivel' => $alerta->nivel,
                    'saldo_disparador' => $umbral,
                ],
            ]);
        }
    }

    private function registrarProcedimiento(LicenciaPuntoCuenta $cuenta, Carbon $fecha, ?User $actor): void
    {
        $actorId = $actor ? $actor->id : null;
        $this->crearOficioAgotamiento($cuenta, $fecha, $actor);

        $cuenta->movimientos()->create([
            'user_id' => $actorId,
            'tipo' => 'procedimiento_administrativo',
            'puntos' => 0,
            'saldo_anterior' => 0,
            'saldo_nuevo' => 0,
            'fecha_movimiento' => $fecha,
            'referencia' => $cuenta->expediente_folio,
            'descripcion' => 'Saldo agotado: expediente y oficio generados; notificacion al titular programada.',
            'metadata' => [
                'expediente_folio' => $cuenta->expediente_folio,
                'oficio_folio' => $cuenta->oficio_folio,
                'reincidencias_cero' => (int) $cuenta->reincidencias_cero,
            ],
        ]);
    }

    private function programarNotificacionesWhatsApp(
        LicenciaPuntoCuenta $cuenta,
        LicenciaPuntoMovimiento $movimiento,
        LicenciaPuntoInfraccion $infraccion,
        Carbon $fecha,
        bool $llegoACero
    ): void {
        $cuentaId = $cuenta->id;
        $movimientoId = $movimiento->id;
        $infraccionId = $infraccion->id;
        $fechaMovimiento = $fecha->copy();

        DB::afterCommit(function () use ($cuentaId, $movimientoId, $infraccionId, $fechaMovimiento, $llegoACero) {
            $cuenta = LicenciaPuntoCuenta::find($cuentaId);
            $movimiento = LicenciaPuntoMovimiento::find($movimientoId);
            $infraccion = LicenciaPuntoInfraccion::find($infraccionId);

            if (!$cuenta || !$movimiento || !$infraccion) {
                return;
            }

            $this->notificadorWhatsApp->notificarDescuento($cuenta, $movimiento, $infraccion, $fechaMovimiento);

            if ($llegoACero) {
                $cuenta->refresh();
                $this->notificadorWhatsApp->notificarAgotamiento($cuenta, $movimiento, $infraccion, $fechaMovimiento);
            }
        });
    }

    private function crearOficioAgotamiento(LicenciaPuntoCuenta $cuenta, Carbon $fecha, ?User $actor): void
    {
        if (
            !Schema::hasTable('oficios')
            || !Schema::hasColumn('oficios', 'tipo')
            || !Schema::hasColumn('oficios', 'sentido')
            || !Schema::hasColumn('oficios', 'fecha_documento')
        ) {
            return;
        }

        if (!$cuenta->oficio_folio || Oficio::where('numero_oficio', $cuenta->oficio_folio)->exists()) {
            return;
        }

        try {
            $actorId = $actor ? $actor->id : null;

            Oficio::create([
                'numero_oficio' => $cuenta->oficio_folio,
                'tipo' => 'administrativo',
                'sentido' => 'salida',
                'unidad_id' => $actor ? $actor->unidad_id : null,
                'fecha_documento' => $fecha->toDateString(),
                'remitente' => 'Sistema de puntos de licencia',
                'destinatario' => 'Secretaria de Finanzas / Titular de licencia',
                'asunto' => 'Inicio de procedimiento administrativo por agotamiento de puntos',
                'descripcion' => sprintf(
                    'La licencia %s de %s llego a 0 puntos. Expediente %s.',
                    $cuenta->numero_licencia,
                    $cuenta->titular_nombre,
                    $cuenta->expediente_folio
                ),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function atenderAlertasAbiertas(LicenciaPuntoCuenta $cuenta, Carbon $fecha): void
    {
        $cuenta->alertas()
            ->whereNull('atendida_at')
            ->update(['atendida_at' => $fecha, 'updated_at' => now()]);
    }

    private function folio(string $prefijo, LicenciaPuntoCuenta $cuenta, int $reincidencia): string
    {
        return sprintf('%s-%s-%06d-R%02d', $prefijo, Carbon::now('America/Mexico_City')->format('Y'), $cuenta->id, $reincidencia);
    }

    private function crearCuentaPorPrimeraInfraccion(array $data, string $numeroLicencia, ?User $actor): LicenciaPuntoCuenta
    {
        $conductor = $this->buscarConductor($data['conductor_id'] ?? null);
        $actorId = $actor ? $actor->id : null;
        $titular = $this->normalizarTexto($data['titular_nombre'] ?? null)
            ?: $this->normalizarTexto($conductor ? $conductor->nombre : null)
            ?: 'SIN NOMBRE';

        return LicenciaPuntoCuenta::create([
            'conductor_id' => $conductor ? $conductor->id : null,
            'numero_licencia' => $numeroLicencia,
            'tipo_licencia' => LicenciaTipoCatalog::normalize($data['tipo_licencia'] ?? null)
                ?: LicenciaTipoCatalog::normalize($conductor ? $conductor->tipo_licencia : null),
            'titular_nombre' => $titular,
            'curp' => $this->normalizarTexto($data['curp'] ?? null),
            'telefono' => $this->soloDigitosONull($data['telefono'] ?? null)
                ?: $this->soloDigitosONull($conductor ? $conductor->telefono : null),
            'saldo_actual' => LicenciaPuntoCuenta::SALDO_INICIAL,
            'estado' => LicenciaPuntoCuenta::ESTADO_VIGENTE,
            'token_consulta' => Str::random(48),
            'observaciones' => $data['observaciones'] ?? 'Cuenta creada automaticamente al registrar la primera penalización.',
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    private function completarCuentaDesdeCaptura(LicenciaPuntoCuenta $cuenta, array $data, ?User $actor): void
    {
        $conductor = $this->buscarConductor($data['conductor_id'] ?? null);
        $actorId = $actor ? $actor->id : null;
        $updates = [];

        if (!$cuenta->conductor_id && $conductor) {
            $updates['conductor_id'] = $conductor->id;
        }

        foreach (['tipo_licencia', 'titular_nombre', 'curp'] as $field) {
            $value = $field === 'tipo_licencia'
                ? LicenciaTipoCatalog::normalize($data[$field] ?? null)
                : $this->normalizarTexto($data[$field] ?? null);
            $value = $value ?: $this->valorConductorParaCampo($conductor, $field);
            if (!$cuenta->{$field} && $value) {
                $updates[$field] = $value;
            }
        }

        $telefono = $this->soloDigitosONull($data['telefono'] ?? null)
            ?: $this->soloDigitosONull($conductor ? $conductor->telefono : null);
        if ($telefono && $this->soloDigitosONull($cuenta->telefono) !== $telefono) {
            $updates['telefono'] = $telefono;
        }

        if ($updates) {
            $updates['updated_by'] = $actorId;
            $cuenta->fill($updates)->save();
        }
    }

    private function buscarConductor($conductorId): ?Conductor
    {
        if (!$conductorId) {
            return null;
        }

        return Conductor::find($conductorId);
    }

    private function valorConductorParaCampo(?Conductor $conductor, string $field): ?string
    {
        if (!$conductor) {
            return null;
        }

        if ($field === 'titular_nombre') {
            return $this->normalizarTexto($conductor->nombre);
        }

        if ($field === 'tipo_licencia') {
            return LicenciaTipoCatalog::normalize($conductor->tipo_licencia);
        }

        return null;
    }

    private function fecha($value, bool $dateOnly = false): ?Carbon
    {
        if (!$value) {
            return null;
        }

        $fecha = $value instanceof Carbon
            ? $value->copy()
            : Carbon::parse($value, 'America/Mexico_City');

        return $dateOnly ? $fecha->startOfDay() : $fecha;
    }

    private function normalizarTexto($value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? mb_strtoupper($value, 'UTF-8') : null;
    }

    private function soloDigitosONull($value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            return substr($digits, 3);
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            return substr($digits, 2);
        }

        return $digits;
    }
}
